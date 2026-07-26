<?php

namespace App\Kafka\Consumers;

use App\Models\Subscription;
use App\Models\Customer;
use App\Models\Notification;
use App\Services\SubscriptionService;
use App\Services\EmailService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionCancelledMail;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubscriptionCancelledConsumer
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private EmailService $emailService,
        private NotificationService $notificationService
    )
    {}

    public function handle($message)
    {
        try {
            $data = $message->getBody();

            Log::info('SubscriptionCancelledConsumer received', ['data' => $data]);

            // Find subscription
            $subscription = $this->subscriptionService->getSubscription($data['subscription_id']);

            Log::info('Subscription found', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
            ]);

            // Find customer
            $customer = $this->subscriptionService->getCustomer($data['customer_id']);

            Log::info('Customer found', [
                'customer id' => $customer->id,
                'customer email' => $customer->email,
            ]);

            // Send email
            $this->emailService->send(
                $customer,
                new SubscriptionCancelledMail(
                    $subscription,
                    $customer
                )
            );

            // create notification
            $this->notificationService->create([
                'customer_id' => $customer->id,
                'event_type' => 5, // subscription cancelled
                'channel' => 1, // email channel
                'title' => 'Subscription Cancelled',
                'message' => "Your subscription to '{$data['plan_name']}' has been cancelled successfully. If this was a mistake, please contact support.",
            ]);

            Log::info('SubscriptionCancelledConsumer completed successfully', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id
            ]);

        } catch (Throwable $e) {
            Log::error('SubscriptionCancelledConsumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
