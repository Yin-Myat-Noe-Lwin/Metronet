<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IspPlan;
use App\Models\PlanDiscount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PDOException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use App\Http\Requests\IspPlanUpdateRequest;
use App\Http\Requests\IspPlanRequest;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Facades\Kafka;
use App\Services\KafkaProducerService;
use Illuminate\Validation\ValidationException;

class IspPlanController extends Controller
{
    public function __construct(
        private KafkaProducerService $kafkaProducer
    ) {}

    public function index(): JsonResponse
    {
        try {
            // get active plans
            $plans = Cache::remember('all_isp_plans', now()->addHours(6), function() {
                Log::info('Cache miss - fetching plans from database');
                return IspPlan::all();
            });

            Log::info('Plans fetched', ['plan' => $plans->toArray()]);

            // Get all active discounts
            $discounts = Cache::remember('active_plan_discounts', now()->addHours(6), function() {
                Log::info('Cache miss - fetching discounts from database');
                return PlanDiscount::where('is_active', 1)->get();
            });

            Log::info('Discounts fetched', ['discounts' => $discounts->toArray()]);

            // Group discounts by plan_id with duration
            $discountsByPlan = [];
            foreach ($discounts as $discount) {
                $planId = $discount->plan_id;
                if (!isset($discountsByPlan[$planId])) {
                    $discountsByPlan[$planId] = [];
                }
                $discountsByPlan[$planId][$discount->duration_months] = $discount->discount_percentage;
            }

            Log::info('Discounts grouped by plan', ['plans_with_discounts' => $discountsByPlan]);

            $result = [];

            foreach ($plans as $plan) {
                $planDiscounts = $discountsByPlan[$plan->id] ?? [];
                // gt the discount for the default validity_months (usually 1 month)
                // If the plan has a discount for 1 month, use it, otherwise 0
                $defaultDiscount = $planDiscounts[$plan->validity_months] ?? 0;
                // Get best discount (for display purposes, but don't apply to price)
                $bestDiscount = !empty($planDiscounts) ? max($planDiscounts) : 0;
                // Calculate discounted price ONLY if there's a discount for the default duration
                $discountedPrice = $defaultDiscount > 0 ? $plan->price * (1 - $defaultDiscount / 100) : $plan->price;

                $result[] = [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price' => (float) $plan->price,
                    'upload_speed' => $plan->upload_speed,
                    'download_speed' => $plan->download_speed,
                    'validity_months' => $plan->validity_months,
                    'status' => $plan->status,
                    'discounts' => $planDiscounts,
                    'has_discount' => $defaultDiscount > 0,
                    'best_discount' => (float) $bestDiscount,
                    'default_discount' => (float) $defaultDiscount,
                    'discounted_price' => (float) $discountedPrice,
                ];
            }

            Log::info('Final data', ['plans_with_discounts' => $result]);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 401);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ], 500);
        }
    }

    public function store(IspPlanRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $plan = IspPlan::create([
                ...$validated,
                'status' => 1
            ]);

            Cache::forget('all_isp_plans');

            return response()->json([
                'message' => 'Plan created successfully',
                'data' => $plan
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (PDOException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 401);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(IspPlanUpdateRequest $request, $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            $plan = IspPlan::find($id);

            if (!$plan) {
                return response()->json([
                    'message' => 'Plan not found'
                ], 404);
            }

            // Get old data before update
            $oldData = [
                'name' => $plan->name,
                'price' => $plan->price,
                'download_speed' => $plan->download_speed,
                'upload_speed' => $plan->upload_speed,
                'status' => $plan->status
            ];

            $plan->update($validated);

            Cache::forget('all_isp_plans');

            // Check what changed
            $priceChanged = $oldData['price'] != $plan->price;
            $nameChanged = $oldData['name'] != $plan->name;
            $downloadSpeedChanged = $oldData['download_speed'] != $plan->download_speed;
            $uploadSpeedChanged = $oldData['upload_speed'] != $plan->upload_speed;
            $statusChanged = $oldData['status'] != $plan->status;

            $anyChange = $priceChanged || $nameChanged || $downloadSpeedChanged || $uploadSpeedChanged || $statusChanged;

            // Send notification via Kafka if any changes
            if ($anyChange) {
                try {
                    $this->kafkaProducer->publish(
                        config('kafka.consumers.plan_updated.topic'),
                        [
                            'plan_id' => $plan->id,
                            'plan_name' => $plan->name ?? 'N/A',
                            'old_price' => $oldData['price'],
                            'new_price' => $plan->price,
                            'old_name' => $oldData['name'],
                            'new_name' => $plan->name,
                            'old_download_speed' => $oldData['download_speed'],
                            'new_download_speed' => $plan->download_speed,
                            'old_upload_speed' => $oldData['upload_speed'],
                            'new_upload_speed' => $plan->upload_speed,
                            'status_changed' => $statusChanged
                        ]
                    );

                    Log::info('Plan update notification published to Kafka', [
                        'plan_id' => $plan->id,
                        'changes' => [
                            'price' => $priceChanged,
                            'name' => $nameChanged,
                            'download_speed' => $downloadSpeedChanged,
                            'upload_speed' => $uploadSpeedChanged,
                            'status' => $statusChanged
                        ]
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to publish plan update: ' . $e->getMessage());
                }
            }

            return response()->json([
                'message' => 'Plan updated successfully',
                'data' => $plan
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (PDOException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 401);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $plan = IspPlan::find($id);

            if (!$plan) {
                return response()->json([
                    'message' => 'Plan not found'
                ], 404);
            }

            if ($plan->status == 0) {
                return response()->json([
                    'message' => 'Plan already inactive'
                ], 400);
            }

            $plan->update([
                'status' => 0
            ]);

            Cache::forget('active_isp_plans');

            // Notify customers about plan deactivation
            try {
                Kafka::publish()
                    ->onTopic('plan.deactivated')
                    ->withBodyKey('plan_id', $plan->id)
                    ->withBodyKey('plan_name', $plan->name)
                    ->send();

                Log::info('Plan deactivation notification published', [
                    'plan_id' => $plan->id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to publish plan deactivation: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Plan deactivated successfully'
            ]);

        } catch (PDOException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 401);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ], 500);
        }
    }
}
