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
use App\Services\KafkaProducerService;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function __construct(
        private KafkaProducerService $kafkaProducer
    ) {}

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

            // Save address and subscription together
            $result = DB::transaction(function () use ($customer, $planId, $request) {

                // CREATE ADDRESS from request data
                $address = CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'address' => $request->address,
                    'region' => $request->region,
                    'city' => $request->city,
                    'township' => $request->township,
                    'address_type' => $request->address_type ?? 1, // 1 = Installation
                ]);

                Log::info('Address created:', [
                    'address_id' => $address->id,
                    'customer_id' => $customer->id
                ]);

                // CREATE SUBSCRIPTION with the new address
                $subscription = Subscription::create([
                    'customer_id' => $customer->id,
                    'plan_id' => $planId,
                    'installation_address_id' => $address->id,
                    'status' => 0, // pending
                    'duration_months' => (int)$request->duration_months,
                    'billing_cycle' => $request->billing_cycle ?? 1,
                    'auto_renew' => 0
                ]);

                Log::info('New subscription created', [
                    'subscription_id' => $subscription->id,
                    'customer_id' => $customer->id,
                    'plan_id' => $planId,
                    'installation_address_id' => $address->id,
                    'status' => $subscription->status,
                    'duration_months' => $subscription->duration_months
                ]);

                return [
                    'subscription' => $subscription,
                    'address' => $address
                ];
            });

            return response()->json([
                'message' => 'Subscription Successful. Please wait approval from ISP.',
                'data' => [
                    'subscription' => $result['subscription'],
                    'address' => $result['address']
                ]
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

                    // Add formatted dates
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
                                        ->with(['plan', 'installationAddress'])
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

    // to accept customer subscription
    public function accept($id): JsonResponse
    {
        try {
            $subscription = Subscription::with(['customer', 'plan', 'installationAddress'])
                                        ->findOrFail($id);

            if ($subscription->status !== 0) {
                return response()->json([
                    'error' => 'Only pending subscriptions can be accepted.'
                ], 400);
            }

            if (!$subscription->installationAddress) {
                return response()->json([
                    'error' => 'No installation address found for this subscription.'
                ], 400);
            }

            // Begin transaction
            DB::transaction(function () use ($subscription) {
                $subscription->update([
                    'status' => 1,
                    'start_date' => now(),
                    'end_date' => now()->addMonths($subscription->duration_months)
                ]);
            });

            // Dispatch the job after the transaction is committed
            ProcessSubscriptionJob::dispatch($subscription->id);

            Log::info('Subscription accepted and job dispatched', [
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
                'job_dispatched' => true
            ]);

            return response()->json([
                'message' => 'Subscription accepted successfully.',
                'data' => $subscription
            ]);

        } catch (\Exception $e) {
            Log::error('Error accepting subscription: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to accept subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    // reject customer subscription
    public function reject(Request $request, $id): JsonResponse
    {
        try {
            $subscription = Subscription::with(['customer', 'plan'])
                                        ->findOrFail($id);

            // Check if subscription is pending
            if ($subscription->status !== 0) {
                return response()->json([
                    'error' => 'Only pending subscriptions can be rejected.'
                ], 400);
            }

            $reason = $request->reason ?? 'No reason provided';
            $sendEmail = $subscription->customer->email;

            DB::transaction(function () use ($subscription, $reason, $sendEmail) {
                $subscription->update([
                    'status' => 5, // Rejected
                ]);

                $this->kafkaProducer->publish(
                    config('kafka.consumers.subscription_rejected.topic'),
                    [
                        'subscription_id' => $subscription->id,
                        'customer_id' => $subscription->customer_id,
                        'customer_name' => $subscription->customer->name ?? 'N/A',
                        'customer_email' => $subscription->customer->email ?? 'N/A',
                        'plan_id' => $subscription->plan_id,
                        'plan_name' => $subscription->plan->name ?? 'N/A',
                        'reason' => $reason,
                        'send_email' => $sendEmail,
                        'rejected_at' => now()->toISOString(),
                    ]
                );

                Log::info('Subscription rejected and event published to Kafka', [
                    'subscription_id' => $subscription->id,
                    'customer_id' => $subscription->customer_id,
                    'reason' => $reason
                ]);
            });

            return response()->json([
                'message' => 'Subscription rejected successfully.',
                'data' => $subscription
            ]);

        } catch (\Exception $e) {
            Log::error('Error rejecting subscription: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to reject subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $subscription = Subscription::with([
                'customer',
                'plan',
                'installationAddress',
                'cpeAssignments.cpe'
            ])->findOrFail($id);

            return response()->json([
                'data' => $subscription
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Subscription not found'
            ], 404);
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


            // Find and delete the CPE assignment for this subscription
            $cpeAssignment = CpeAssignment::where('subscription_id', $subscription->id)
                                            ->whereNull('unassigned_at')
                                            ->first();

            if ($cpeAssignment) {
                // Update the CPE status back to Available (0)
                Cpe::where('id', $cpeAssignment->cpe_id)
                    ->update(['status' => 0]);

                // Mark the assignment as unassigned
                $cpeAssignment->update([
                    'unassigned_at' => now(),
                    'status' => 0 // or whatever status indicates "unassigned"
                ]);
            }

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

                $this->kafkaProducer->publish(
                    config('kafka.consumers.service_cancelled.topic'),
                    [
                        'subscription_id' => $subscription->id,
                        'customer_id' => $customer->id,
                        'plan_name' => $plan->name ?? 'N/A',
                    ]
                );

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
