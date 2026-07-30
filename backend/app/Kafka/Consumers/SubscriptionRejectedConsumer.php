<?php

namespace App\Kafka\Consumers;

use App\Services\SubscriptionService;
use App\Services\CustomerService;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Mail\SubscriptionRejectedMail;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubscriptionRejectedConsumer
{
    public function __construct(
        private CustomerService $customerService,
        private SubscriptionService $subscriptionService,
        private EmailService $emailService,
        private NotificationService $notificationService
    ) {}

    public function handle($message): void
    {
        try {
            // Get the message body
            $data = json_decode($message->getBody(), true);

            Log::info('Subscription rejected event received', [
                'data' => $data
            ]);

            // Validate required data
            if (!isset($data['subscription_id']) || !isset($data['customer_id'])) {
                Log::error('Missing required data in message', [
                    'data' => $data
                ]);
                return;
            }

            // Get subscription
            $subscription = $this->subscriptionService->getSubscription(
                $data['subscription_id']
            );

            Log::info('Subscription found', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
            ]);

            // Get customer
            $customer = $this->customerService->getCustomer(
                $data['customer_id']
            );

            Log::info('Customer found', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
            ]);

            // Extract data
            $reason = $data['reason'] ?? 'No reason provided';
            $planName = $data['plan_name'] ?? $subscription->plan->name ?? 'N/A';
            $sendEmail = $data['send_email'] ?? true;

            // Send rejection email
            if ($sendEmail) {
                $this->emailService->send(
                    $customer,
                    new SubscriptionRejectedMail(
                        $subscription,
                        $customer,
                        $reason
                    )
                );

                Log::info('Rejection email sent', [
                    'customer_id' => $customer->id,
                    'email' => $customer->email
                ]);
            }

            // Create notification
            $this->notificationService->create([
                'customer_id' => $customer->id,
                'event_type' => 7, // subscription_rejected
                'channel' => 3, // in_app
                'title' => 'Subscription Rejected',
                'message' => "Your subscription to {$planName} has been rejected. Reason: {$reason}",
                'is_read' => 0,
                'sent_status' => 0
            ]);

            Log::info('Notification created successfully', [
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id
            ]);

            Log::info('Subscription rejection completed', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id,
                'reason' => $reason
            ]);

        } catch (Throwable $e) {
            Log::error('Subscription rejection consumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
