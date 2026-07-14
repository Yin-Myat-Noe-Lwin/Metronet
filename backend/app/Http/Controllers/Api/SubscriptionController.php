<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionRequest;
use App\Jobs\ProcessSubscriptionJob;
use App\Models\CustomerAddress;
use App\Models\Notification;
use App\Models\Invoice;
use App\Models\IspPlan;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use PDOException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Junges\Kafka\Facades\Kafka;

class SubscriptionController extends Controller
{
    public function store(SubscriptionRequest $request, $planId): JsonResponse
    {
        try {
            $customer = Auth::user();

            Log::info('Logged in user is: ' . $customer->id);
            Log::info('Plan ID: ' . $planId);

            $plan = IspPlan::where('id', $planId)
                ->where('status', 1)
                ->first();

            if (!$plan) {
                return response()->json([
                    'error' => 'Plan not found or inactive.'
                ], 404);
            }

            Log::info('Plan found: ' . $plan->name);

            // Check if customer has ACTIVE (1) or PENDING (0) subscription
            // This allows resubscription if previous was cancelled (4) or expired (3)
            $existing = Subscription::where('customer_id', $customer->id)
                ->where('plan_id', $planId)
                ->whereIn('status', [0, 1])
                ->first();

            if ($existing) {
                Log::info('Existing active/pending subscription found', [
                    'subscription_id' => $existing->id,
                    'status' => $existing->status
                ]);
                return response()->json([
                    'error' => 'You already have an active or pending subscription to this plan.',
                    'data' => $existing
                ], 409);
            }

            // Check if there's a cancelled subscription (for logging only)
            $cancelled = Subscription::where('customer_id', $customer->id)
                ->where('plan_id', $planId)
                ->where('status', 4)
                ->first();

            if ($cancelled) {
                Log::info('Previous cancelled subscription found, allowing new subscription', [
                    'cancelled_subscription_id' => $cancelled->id,
                    'plan_id' => $planId
                ]);
            }

            $address = CustomerAddress::where('customer_id', $customer->id)
                ->where('is_primary', 1)
                ->first();

            if (!$address) {
                return response()->json([
                    'error' => 'Please set a primary installation address before subscribing.'
                ], 400);
            }

            // Create NEW subscription
            $subscription = Subscription::create([
                'customer_id' => $customer->id,
                'plan_id' => $planId,
                'status' => 0,
                'start_date' => now(),
                'end_date' => now()->addMonths((int)$request->duration_months)
            ]);

            Log::info('New subscription created', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id,
                'plan_id' => $planId,
                'status' => $subscription->status
            ]);

            ProcessSubscriptionJob::dispatch($subscription->id);

            return response()->json([
                'message' => 'Subscription Successful. Please wait approval from ISP.',
                'data' => $subscription
            ], 201);

        } catch (PDOException $e) {
            Log::error('PDOException in store: ' . $e->getMessage());
            return response()->json([
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        } catch (QueryException $e) {
            Log::error('QueryException in store: ' . $e->getMessage());
            return response()->json([
                'message' => 'Database query error: ' . $e->getMessage()
            ], 500);
        } catch (ModelNotFoundException $e) {
            Log::error('ModelNotFoundException in store: ' . $e->getMessage());
            return response()->json([
                'message' => 'Resource not found: ' . $e->getMessage()
            ], 404);
        } catch (AuthenticationException $e) {
            Log::error('AuthenticationException in store: ' . $e->getMessage());
            return response()->json([
                'message' => 'Authentication required: ' . $e->getMessage()
            ], 401);
        } catch (AuthorizationException $e) {
            Log::error('AuthorizationException in store: ' . $e->getMessage());
            return response()->json([
                'message' => 'Unauthorized access: ' . $e->getMessage()
            ], 403);
        } catch (Exception $e) {
            Log::error('Exception in store: ' . $e->getMessage());
            return response()->json([
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
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
        try {
            $subscriptions = Cache::remember('admin_subscriptions', 60, function () {
                return Subscription::with([
                    'customer',
                    'plan',
                    'cpeAssignments.cpe'
                ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($subscription) {
                    // Map status integer to string
                    $statusMap = [
                        0 => 'pending',
                        1 => 'active',
                        2 => 'suspended',
                        3 => 'expired',
                        4 => 'cancelled'
                    ];
                    $subscription->status_text = $statusMap[$subscription->status] ?? 'unknown';

                    // ✅ Add formatted dates
                    $subscription->formatted_created_at = $subscription->created_at?->format('M d, Y');
                    $subscription->formatted_start_date = $subscription->start_date?->format('M d, Y');
                    $subscription->formatted_end_date = $subscription->end_date?->format('M d, Y');

                    return $subscription;
                });
            });

            return response()->json([
                'data' => $subscriptions
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

    public function viewSubscriptions(): JsonResponse
    {
        try{
            $customer = Auth::user();

            $subscriptions = $customer->subscriptions()
                                        ->with('plan')
                                        ->orderBy('created_at', 'desc')
                                        ->get();

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

    public function destroy($id): JsonResponse
    {
        try {
            $customer = Auth::user();

            $subscription = $customer->subscriptions()
                ->with('plan')
                ->where('id', $id)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'error' => 'Subscription not found.'
                ], 404);
            }

            // Check if THIS subscription is already cancelled
            if ($subscription->status == 4) {
                return response()->json([
                    'message' => 'This subscription is already cancelled.',
                    'data' => $subscription
                ], 400);
            }

            // Check if THIS subscription is already expired
            if ($subscription->status == 3) {
                return response()->json([
                    'error' => 'This subscription is already expired.'
                ], 400);
            }

            // Check if THIS subscription is in a cancellable state
            if ($subscription->status != 0 && $subscription->status != 1) {
                return response()->json([
                    'error' => 'Subscription cannot be cancelled in its current state.'
                ], 400);
            }

            // Check if ANY payment has been made for THIS subscription
            $hasPaid = Invoice::where('subscription_id', $subscription->id)
                ->whereHas('payment', function ($q) {
                    $q->where('status', 1);
                })
                ->exists();

            if ($hasPaid) {
                return response()->json([
                    'error' => 'Cannot cancel. This subscription has already been paid for. Please contact support.'
                ], 400);
            }

            // Cancel any pending invoices for THIS subscription
            Invoice::where('subscription_id', $subscription->id)
                ->where('status', 0)
                ->update(['status' => 3]);

            // Cancel THIS subscription
            $subscription->update([
                'status' => 4
            ]);

            Log::info('Subscription cancelled by customer', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id,
                'status_after' => 4
            ]);

            // Publish to Kafka (with error handling)
            try {
                $plan = $subscription->plan; // Get plan from subscription

                Kafka::publish()
                    ->onTopic('subscription.cancelled')
                    ->withBodyKey('subscription_id', $subscription->id)
                    ->withBodyKey('customer_id', $customer->id)
                    ->withBodyKey('plan_name', $plan->name ?? 'N/A')
                    ->send();

                Log::info('Kafka message published', [
                    'subscription_id' => $subscription->id
                ]);
            } catch (\Exception $e) {
                Log::error('Kafka publish failed: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Subscription cancelled successfully.',
                'data' => $subscription
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
            Log::error('Cancel subscription error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
}
