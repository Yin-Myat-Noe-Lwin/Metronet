<?php

namespace App\Kafka\Consumers;

use App\Models\Subscription;
use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionAutoCancelledMail;
use Throwable;

class AutoCancelledConsumer
{
    public function handle($message)
    {
        try {
            $data = $message->getBody();

            Log::info('AutoCancelledConsumer received', ['data' => $data]);

            // check if subscription id and customer id exist in data
            if (!isset($data['subscription_id']) || !isset($data['customer_id'])) {
                Log::error('Missing required fields', ['data' => $data]);
                return;
            }

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

            // Get plan name
            $planName = 'Unknown';
            if ($subscription->plan) {
                $planName = $subscription->plan->name;
            } elseif (isset($data['plan_name'])) {
                $planName = $data['plan_name'];
            }

            // Create notification
            try {
                Notification::create([
                    'customer_id' => $customer->id,
                    'event_type' => 5,
                    'channel' => 1,
                    'title' => '⚠️ Subscription Auto-Cancelled',
                    'message' => "Your subscription to '{$planName}' has been automatically cancelled due to unpaid invoice for 7 days. Please contact support to reactivate.",
                    'is_read' => 0,
                    'read_at' => null,
                    'scheduled_at' => null,
                    'sent_status' => 1,
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('Auto-cancel notification created for customer #' . $customer->id);
            } catch (Throwable $e) {
                Log::error('Failed to create notification: ' . $e->getMessage());
            }

            // Send email
            try {
                if ($customer->email) {
                    // Create invoice data for email
                    $invoice = null;
                    if (isset($data['invoice_number'])) {
                        $invoice = new \stdClass();
                        $invoice->invoice_number = $data['invoice_number'];
                        $invoice->amount = $data['amount'] ?? 0;
                    }

                    Mail::to($customer->email)->send(
                        new SubscriptionAutoCancelledMail($subscription, $invoice, $customer)
                    );
                    Log::info('Auto-cancel email sent to: ' . $customer->email);
                }
            } catch (Throwable $e) {
                Log::error('Failed to send email: ' . $e->getMessage());
            }

            Log::info('AutoCancelledConsumer completed', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id
            ]);

        } catch (Throwable $e) {
            Log::error('AutoCancelledConsumer failed: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}
