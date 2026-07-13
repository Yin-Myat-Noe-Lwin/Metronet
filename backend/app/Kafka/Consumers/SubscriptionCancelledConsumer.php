<?php

namespace App\Kafka\Consumers;

use App\Models\Subscription;
use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionCancelledMail;
use Illuminate\Support\Facades\Log;

class SubscriptionCancelledConsumer
{
    public function handle($message)
    {
        try {
            $data = $message->getBody();

            Log::info('📥 SubscriptionCancelledConsumer received', ['data' => $data]);

            // Find subscription
            $subscription = Subscription::with('plan')->find($data['subscription_id']);
            if (!$subscription) {
                Log::error('❌ Subscription not found', ['subscription_id' => $data['subscription_id']]);
                return;
            }

            // Find customer
            $customer = Customer::find($data['customer_id']);
            if (!$customer) {
                Log::error('❌ Customer not found', ['customer_id' => $data['customer_id']]);
                return;
            }

            Log::info('👤 Customer found', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'name' => $customer->name
            ]);

            // ✅ CREATE IN-APP NOTIFICATION
            try {
                Notification::create([
                    'customer_id' => $customer->id,
                    'event_type' => 5, // subscription_cancelled
                    'channel' => 1,    // email
                    'title' => 'Subscription Cancelled',
                    'message' => "Your subscription to '{$data['plan_name']}' has been cancelled successfully. If this was a mistake, please contact support.",
                    'status' => 1,
                    'is_read' => 0,
                    'read_at' => null,
                    'scheduled_at' => null,
                    'sent_status' => 1,
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('✅ Cancellation notification created', [
                    'subscription_id' => $subscription->id,
                    'customer_id' => $customer->id
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Failed to create notification: ' . $e->getMessage());
            }

            // ✅ SEND EMAIL
            try {
                if ($customer->email) {
                    Mail::to($customer->email)
                        ->send(new SubscriptionCancelledMail($subscription, $customer));

                    Log::info('📧 Cancellation email sent', [
                        'subscription_id' => $subscription->id,
                        'email' => $customer->email
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('❌ Failed to send email: ' . $e->getMessage());
            }

            Log::info('✅ SubscriptionCancelledConsumer completed successfully', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id
            ]);

        } catch (\Exception $e) {
            Log::error('❌ SubscriptionCancelledConsumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
