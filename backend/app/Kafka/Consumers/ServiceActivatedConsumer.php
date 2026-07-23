<?php

namespace App\Kafka\Consumers;

use App\Services\SubscriptionService;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Mail\SubscriptionSuccessMail;
use Illuminate\Support\Facades\Log;
use Throwable;

class ServiceActivatedConsumer
{

    public function __construct(
        private SubscriptionService $subscriptionService,
        private EmailService $emailService,
        private NotificationService $notificationService
    ) {}

    public function handle($message): void
    {
        try {

            $data = $message->getBody();

            Log::info('Service activated event received', [
                'data' => $data
            ]);

            // Get data
            $subscription = $this->subscriptionService
                ->getSubscription(
                    $data['subscription_id']
                );

            Log::info('Subscription found', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
            ]);

            $customer = $this->subscriptionService
                                ->getCustomer(
                                    $data['customer_id']
                                );

            Log::info('Customer found', [
                'customer id' => $customer->id,
                'email' => $customer->email,
            ]);

            // Send email
            $this->emailService->send(
                $customer,
                new SubscriptionSuccessMail(
                    $subscription,
                    $customer
                )
            );

            // Create notification
            $this->notificationService->create([
                'customer_id' => $customer->id,
                'event_type' => 4, // service activated
                'channel' => 1, // email channel
                'title' => 'Service Activated!',
                'message' => 'Your internet service has been activated successfully! You can now enjoy high-speed internet.',
            ]);

            Log::info('Service activation completed', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id
            ]);

        } catch (Throwable $e) {
            Log::error('Service activation consumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
