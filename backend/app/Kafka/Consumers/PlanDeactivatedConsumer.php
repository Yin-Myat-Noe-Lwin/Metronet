<?php

namespace App\Kafka\Consumers;

use App\Models\IspPlan;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\PlanDeactivatedMail;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Services\PlanService;
use Throwable;

class PlanDeactivatedConsumer
{
    public function __construct(
        private EmailService $emailService,
        private NotificationService $notificationService,
        private planService $planService
    ) {}

    public function handle($message)
    {
        try {
            $data = $message->getBody();

            Log::info('PlanDeactivatedConsumer received', ['data' => $data]);

            // get isp plan by id
            $plan = $this->planService
                        ->getPlan($data['plan_id']);

            if (!$plan) {
                Log::error('Plan not found', ['plan_id' => $data['plan_id']]);
                return;
            }

            // Get all customers with active OR pending subscription to this plan
            $customers = Customer::whereHas('subscriptions', function ($q) use ($plan) {
                $q->where('plan_id', $plan->id)
                  ->whereIn('status', [0, 1]); // pending (0) or active (1)
            })->with(['subscriptions' => function ($q) use ($plan) {
                $q->where('plan_id', $plan->id)
                  ->whereIn('status', [0, 1]);
            }])->get();

            if ($customers->isEmpty()) {
                Log::info('No customers to notify for plan deactivation', ['plan_id' => $plan->id]);
                return;
            }

            foreach ($customers as $customer) {
                try {
                    // Get the customer's subscription for this plan
                    $subscription = $customer->subscriptions->first();
                    $isPending = $subscription && $subscription->status == 0;
                    $isActive = $subscription && $subscription->status == 1;

                    // Build different messages based on subscription status
                    if ($isPending) {
                        $notificationTitle = 'Subscription Cancelled';
                        $notificationMessage = "⚠️ The plan '{$plan->name}' you applied for has been discontinued. Your subscription request has been cancelled. Please choose a new plan.";
                        $emailSubject = '⚠️ Subscription Cancelled - Plan Discontinued';

                        // Cancel pending subscription
                        $subscription->update([
                            'status' => 4 // cancelled
                        ]);

                        Log::info('Pending subscription cancelled due to plan deactivation', [
                            'subscription_id' => $subscription->id,
                            'customer_id' => $customer->id,
                            'plan_id' => $plan->id
                        ]);

                    } elseif ($isActive) {
                        $notificationTitle = 'Plan Discontinued';
                        $notificationMessage = "⚠️ The plan '{$plan->name}' you are subscribed to has been discontinued. ";
                        $notificationMessage .= "Your service will continue until {$subscription->end_date->format('F d, Y')}. ";
                        $notificationMessage .= "Please choose a new plan before your current subscription ends to avoid service interruption.";
                        $emailSubject = '⚠️ Plan Discontinued - Action Required';

                        // For active subscriptions, keep them active until end date
                        // No immediate change, just notify

                        Log::info('Active subscription notified about plan deactivation', [
                            'subscription_id' => $subscription->id,
                            'customer_id' => $customer->id,
                            'plan_id' => $plan->id,
                            'end_date' => $subscription->end_date
                        ]);
                    }

                    // Create notification
                    $this->notificationService->create([
                        'customer_id' => $customer->id,
                        'event_type' => 7, // plan deleted
                        'channel' => 1, // email channel
                        'title' => $notificationTitle,
                        'message' => $notificationMessage,
                    ]);

                    Log::info('Plan deactivation notification created', [
                        'customer_id' => $customer->id,
                        'plan_id' => $plan->id,
                        'subscription_status' => $subscription?->status,
                        'is_pending' => $isPending,
                        'is_active' => $isActive
                    ]);

                    // Send email
                    if ($customer->email) {
                        // Send email
                        $this->emailService->send(
                            $customer,
                            new PlanDeactivatedMail(
                                $plan,
                                $customer,
                                $subscription,
                                $isPending,
                                $isActive
                            )
                        );

                        Log::info('Plan deactivation email sent', [
                            'customer_id' => $customer->id,
                            'email' => $customer->email,
                            'is_pending' => $isPending,
                            'is_active' => $isActive
                        ]);
                    }

                } catch (Throwable $e) {
                    Log::error('Failed to notify customer: ' . $customer->id . ' - ' . $e->getMessage());
                }
            }

            Log::info('PlanDeactivatedConsumer completed', [
                'plan_id' => $plan->id,
                'customers_notified' => $customers->count()
            ]);

        } catch (Throwable $e) {
            Log::error('PlanDeactivatedConsumer failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }
}
