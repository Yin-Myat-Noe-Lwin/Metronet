<?php

namespace App\Kafka\Consumers;

use App\Models\Customer;
use App\Models\Subscription;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionSuccessMail;
use Illuminate\Support\Facades\Log;
use Throwable;

class ServiceActivatedConsumer
{
    public function handle($message)
    {
        try {
            $data = $message->getBody();

            Log::info('ServiceActivatedConsumer received', ['data' => $data]);

            // Find subscription
            $subscription = Subscription::with('plan')->find($data['subscription_id']);
            if (!$subscription) {
                Log::error('Subscription not found', ['subscription_id' => $data['subscription_id']]);
                return;
            }

            // Find customer
            $customer = Customer::find($data['customer_id']);
            if (!$customer) {
                Log::error('Customer not found', ['customer_id' => $data['customer_id']]);
                return;
            }

            Log::info('Customer found', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'name' => $customer->name
            ]);

            // SEND EMAIL
            try {
                if ($customer->email) {
                    Mail::to($customer->email)
                        ->send(new SubscriptionSuccessMail($subscription, $customer));

                    Log::info('📧 Subscription success email sent', [
                        'subscription_id' => $subscription->id,
                        'email' => $customer->email
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('Failed to send email', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }

            // CREATE NOTIFICATION
            try {
                Notification::create([
                    'customer_id' => $customer->id,
                    'event_type' => 4, // 4=service_activated
                    'channel' => 1,    // 1=email
                    'title' => 'Service Activated!',
                    'message' => 'Your internet service has been activated successfully! You can now enjoy high-speed internet.',
                    'status' => 1,      // active
                    'is_read' => 0,     // unread
                    'read_at' => null,
                    'scheduled_at' => null,
                    'sent_status' => 1, // sent
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('Notification created for customer: ' . $customer->id);
            } catch (Throwable $e) {
                Log::error('Failed to create notification', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('ServiceActivatedConsumer completed successfully', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id
            ]);

        } catch (Throwable $e) {
            Log::error('ServiceActivatedConsumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
