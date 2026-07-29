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
use App\Services\CustomerService;
use App\Services\SubscriptionService;
use Throwable;

class PlanDeactivatedConsumer
{
    public function __construct(
        private EmailService $emailService,
        private NotificationService $notificationService,
        private planService $planService,
        private customerService $customerService,
        private subscriptionService $subscriptionService
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
            $customers = $this->customerService
                            ->getCustomersByPlan($plan->id);

            if ($customers->isEmpty()) {
                Log::info('No customers to notify for plan deactivation', ['plan_id' => $plan->id]);
                return;
            }

            foreach ($customers as $customer) {
                try {
                    // Get the customer's subscription for this plan
                    $subscription = $this->subscriptionService
                                        ->getCustomerSubscription($customer->id, $plan->id);

                    // Build different messages based on subscription status
                    $result = $this->subscriptionService
                                    ->processPlanDeactivation($subscription, $plan);

                    $notificationTitle = $result['title'];
                    $notificationMessage = $result['message'];

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
