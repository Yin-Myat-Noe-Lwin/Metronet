<?php

namespace App\Kafka\Consumers;

use App\Models\IspPlan;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\PlanUpdatedMail;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Services\SubscriptionService;
use App\Services\CustomerService;
use App\Services\PlanService;
use Throwable;

class PlanUpdatedConsumer
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private EmailService $emailService,
        private NotificationService $notificationService,
        private CustomerService $customerService,
        private PlanService $planService
    ) {}

    public function handle($message)
    {
        try {
            $data = $message->getBody();

            Log::info('PlanUpdatedConsumer received', ['data' => $data]);

            $plan = $this->planService
                        ->getPlan($data['plan_id']);

            if (!$plan) {
                Log::error('Plan not found', ['plan_id' => $data['plan_id']]);
                return;
            }

            // Get all customers with active subscription to this plan
            $customers = $this->customerService
                                ->getCustomersByPlan($plan->id);

            if ($customers->isEmpty()) {
                Log::info('No customers to notify for plan update', ['plan_id' => $plan->id]);
                return;
            }

            // create plan update message
            $message = $this->planService
                            ->buildUpdateMessage($plan, $data);

            // Create notification for each customer
            foreach ($customers as $customer) {
                try {
                    $subscription = $this->subscriptionService
                                        ->getSubscription($customer->id, $plan->id);

                    $this->emailService->send(
                        $customer,
                        new PlanUpdatedMail(
                            $plan,
                            $customer,
                            $data,
                            $subscription?->end_date
                        )
                    );

                    Log::info('Plan update email sent', [
                        'customer_id' => $customer->id,
                        'email' => $customer->email
                    ]);

                    $this->notificationService->create([
                        'customer_id' => $customer->id,
                        'event_type' => 6, // plan updated
                        'channel' => 1, // email channel
                        'title' => 'Plan Updated',
                        'message' => $message,
                    ]);

                    Log::info('Plan update notification created', [
                        'customer_id' => $customer->id,
                        'plan_id' => $plan->id
                    ]);

                } catch (Throwable $e) {
                    Log::error('Failed to notify customer: ' . $customer->id . ' - ' . $e->getMessage());
                }
            }

            Log::info('PlanUpdatedConsumer completed', [
                'plan_id' => $plan->id,
                'customers_notified' => $customers->count()
            ]);

        } catch (Throwable $e) {
            Log::error('PlanUpdatedConsumer failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }
}
