<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionRequest;
use App\Jobs\ProcessSubscriptionJob;
use App\Models\CustomerAddress;
use App\Models\IspPlan;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PDOException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class SubscriptionController extends Controller
{
    public function store(SubscriptionRequest $request, $planId): JsonResponse
    {
        try{
            $customer = Auth::user();

            Log::info('Logged in user is:'.$customer->id);

            Log::info('plan id'.$planId);

            $plan = IspPlan::where('id',$planId)
                                    ->where('status',1)
                                    ->first();

            if(!$plan) {
                return response()->json([
                    'message' => 'Plan not found or inactive.'
                ]);
            }

            Log::info('plan'.$plan);

            // check if customer already subscribes or not
            $existing = Subscription::where('customer_id', $customer->id)
                                    ->where('plan_id', $planId)
                                    ->whereIn('status', [0, 1])
                                    ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'Already subscribed or pending approval.'
                ]);
            }

            $address = CustomerAddress::where('customer_id', $customer->id)
                                        ->where('is_primary', 1)
                                        ->first();

            if(!$address) {
                return response()->json([
                    'message' => 'Please set a primary installation address before subscribing.'
                ]);
            }

            $subscription = Subscription::create([
                            'customer_id' => $customer->id,
                            'plan_id' => $planId,
                            'status' => 0,
                            'start_date' => now(),
                            'end_date' => now()->addMonths((int)$request->duration_months)
                            ]);

            return response()->json([
                'message' => 'Subscription Successful. Please wait approval from ISP.',
                'data' => $subscription
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

    public function status(): JsonResponse
    {
        try{
            $customer = Auth::user();

            $subscription = $customer->subscriptions()
                                    ->latest()
                                    ->first();

            if (!$subscription) {
                return response()->json([
                    'status' => 'NO_SUBSCRIPTION.'
                ]);
            }

            // if waiting for approval
            if ($subscription->status == 0) {
                return response()->json([
                    'status' => 'PENDING_APPROVAL'
                ]);
            }

            // if subscription expires
            if ($subscription->end_date < now()) {
                return response()->json([
                    'status' => 'EXPIRED'
                ]);
            }

            $payment = Payment::where('invoice_id', function ($q) use ($subscription) {
                                $q->select('id')
                                ->from('invoices')
                                ->where('subscription_id', $subscription->id);
                            })
                            ->where('status', 1)
                            ->first();

            if (!$payment) {
                return response()->json([
                    'status' => 'PAYMENT_PENDING'
                ]);
            }

            $cpe = CpeAssignment::where('subscription_id', $subscription->id)->first();

            if (!$cpe) {
                return  response()->json([
                    'status' => 'NOT_PROVISIONED' // not assigned yet
                ]);
            }

            return response()->json([
                'status' => 'ACTIVE',
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan_id
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

    public function index(): JsonResponse
    {
        try{
            $subscriptions = Cache::remember('admin_subscriptions', 60, function () {
                                return Subscription::with([
                                    'customer',
                                    'plan',
                                    'cpeAssignments.cpe'
                                ])
                                ->orderBy('created_at', 'desc')
                                ->get();
                            });

            return response()->json([
                'data' => $subscriptions
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
