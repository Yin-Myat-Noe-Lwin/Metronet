<?php

namespace App\Kafka\Consumers;

use App\Mail\PlanDeactivatedMail;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Services\PlanService;
use App\Services\CustomerService;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlanDeactivatedConsumer
{
    public function __construct(
        private EmailService $emailService,
        private NotificationService $notificationService,
        private PlanService $planService,
        private CustomerService $customerService,
        private SubscriptionService $subscriptionService
    ) {}

    public function handle($message): void
    {
        try {
            $data = $message->getBody();

            Log::info('PlanDeactivatedConsumer received', [
                'data' => $data
            ]);

            // Get plan
            $plan = $this->planService->getPlan($data['plan_id']);

            if (!$plan) {
                Log::error('Plan not found', [
                    'plan_id' => $data['plan_id']
                ]);
                return;
            }

            // Get customers subscribed to this plan
            $customers = $this->customerService
                              ->getCustomersByPlan($plan->id);

            if ($customers->isEmpty()) {
                Log::info('No customers to notify for plan deactivation', [
                    'plan_id' => $plan->id
                ]);
                return;
            }

            foreach ($customers as $customer) {

                try {

                    // Get customer's subscription
                    $subscription = $this->subscriptionService
                                         ->getCustomerSubscription(
                                             $customer->id,
                                             $plan->id
                                         );

                    // Determine subscription status
                    $isPending = $subscription && $subscription->status == 0;
                    $isActive = $subscription && $subscription->status == 1;


                    // Generate notification content
                    $result = $this->planService
                                   ->processPlanDeactivation(
                                       $subscription,
                                       $plan
                                   );


                    if (!$result) {
                        Log::warning('No notification content generated', [
                            'customer_id' => $customer->id,
                            'plan_id' => $plan->id
                        ]);
                        continue;
                    }


                    // Create notification
                    $this->notificationService->create([
                        'customer_id' => $customer->id,
                        'event_type' => 7, // plan deactivated
                        'channel' => 1, // email
                        'title' => $result['title'],
                        'message' => $result['message'],
                        'is_read' => 0,
                        'sent_status' => 0
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

                    Log::error(
                        'Failed to notify customer: ' .
                        $customer->id .
                        ' - ' .
                        $e->getMessage()
                    );
                }
            }


            Log::info('PlanDeactivatedConsumer completed', [
                'plan_id' => $plan->id,
                'customers_notified' => $customers->count()
            ]);


        } catch (Throwable $e) {

            Log::error('PlanDeactivatedConsumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
