<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IspPlan;
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

class IspPlanController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $plans = Cache::remember('active_isp_plans', 60, function() {
                return IspPlan::where('status', 1)->get();
            });

            return response()->json([
                'data' => $plans
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }

    public function store(IspPlanRequest $request): JsonResponse
    {
        try{
            $plan = IspPlan::create([
                ...$request->validated(),
                'status' => 1
            ]);

            Cache::forget('active_isp_plans');

            return response()->json([
                'message' => 'Plan created',
                'data' => $plan
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }

    public function update(IspPlanUpdateRequest $request, $id): JsonResponse
    {
        try{
            $plan = IspPlan::find($id);

            if (!$plan) {
                return response()->json([
                    'message' => 'Not found'
                ]);
            }

            // Get old data before update
            $oldData = [
                'name' => $plan->name,
                'price' => $plan->price,
                'download_speed' => $plan->download_speed,
                'upload_speed' => $plan->upload_speed,
                'status' => $plan->status
            ];

            $plan->update($request->validated());

            Cache::forget('active_isp_plans');

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
                    Kafka::publish()
                        ->onTopic('plan.updated')
                        ->withBodyKey('plan_id', $plan->id)
                        ->withBodyKey('plan_name', $plan->name)
                        ->withBodyKey('old_price', $oldData['price'])
                        ->withBodyKey('new_price', $plan->price)
                        ->withBodyKey('old_name', $oldData['name'])
                        ->withBodyKey('new_name', $plan->name)
                        ->withBodyKey('old_download_speed', $oldData['download_speed'])
                        ->withBodyKey('new_download_speed', $plan->download_speed)
                        ->withBodyKey('old_upload_speed', $oldData['upload_speed'])
                        ->withBodyKey('new_upload_speed', $plan->upload_speed)
                        ->withBodyKey('status_changed', $statusChanged)
                        ->send();

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
                'message' => 'Plan updated',
                'data' => $plan
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }

    public function destroy($id): JsonResponse
    {
        try{
            $plan = IspPlan::find($id);

            if (!$plan) {
                return response()->json([
                    'message' => 'Plan not found'
                ]);
            }

            if ($plan->status == 0) {
                return response()->json([
                    'message' => 'Plan already inactive'
                ]);
            }

            $plan->update([
                'status' => 0
            ]);

            Cache::forget('active_isp_plans');

            // ✅ Notify customers about plan deactivation
            try {
                Kafka::publish()
                    ->onTopic('plan.deactivated')
                    ->withBodyKey('plan_id', $plan->id)
                    ->withBodyKey('plan_name', $plan->name)
                    ->send();

                Log::info('✅ Plan deactivation notification published', [
                    'plan_id' => $plan->id
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Failed to publish plan deactivation: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Plan deactivated successfully'
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }
}
