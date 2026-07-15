<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Junges\Kafka\Facades\Kafka;
use App\Mail\SubscriptionAutoCancelledMail;

class AutoCancelUnpaidSubscriptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        Log::info('🔄 Auto-cancel job started at: ' . now());

        // Get all pending invoices older than 7 days
        $cutoffDate = now()->subDays(7);

        $oldInvoices = Invoice::where('status', 0) // pending
            ->where('created_at', '<=', $cutoffDate)
            ->with(['subscription', 'subscription.customer', 'subscription.plan'])
            ->get();

        Log::info("Found {$oldInvoices->count()} pending invoices older than 7 days");

        if ($oldInvoices->isEmpty()) {
            Log::info('No unpaid invoices older than 7 days found.');
            return;
        }

        $cancelledCount = 0;
        $failedCount = 0;

        foreach ($oldInvoices as $invoice) {
            try {
                // Validate subscription exists
                $subscription = $invoice->subscription;

                if (!$subscription) {
                    Log::warning("⚠️ Subscription not found for invoice #{$invoice->id}");
                    $failedCount++;
                    continue;
                }

                // Skip if already cancelled or expired
                if (in_array($subscription->status, [3, 4])) {
                    Log::info("Skipping subscription #{$subscription->id} (already status: {$subscription->status})");
                    continue;
                }

                $customer = $subscription->customer;

                if (!$customer) {
                    Log::warning("⚠️ Customer not found for subscription #{$subscription->id}");
                    $failedCount++;
                    continue;
                }

                Log::info("Processing subscription #{$subscription->id} for customer: {$customer->name}");

                // Cancel the subscription
                $subscription->update([
                    'status' => 4 // cancelled
                ]);

                // Cancel the invoice
                $invoice->update([
                    'status' => 3 // cancelled
                ]);

                $cancelledCount++;

                Log::info("Cancelled subscription #{$subscription->id} for customer: {$customer->name}");

                // Get plan name safely
                $planName = 'Unknown';
                if ($subscription->plan) {
                    $planName = $subscription->plan->name;
                }

                // Create in-app notification
                $this->createNotification($customer, $invoice, $subscription, $planName);

                // Send email
                $this->sendEmail($customer, $invoice, $subscription);

                // Publish to Kafka
                $this->publishToKafka($subscription, $customer, $invoice, $planName);

            } catch (\Exception $e) {
                Log::error("Error processing invoice #{$invoice->id}: " . $e->getMessage());
                Log::error($e->getTraceAsString());
                $failedCount++;
            }
        }

        Log::info("Auto-cancel completed. Cancelled: {$cancelledCount}, Failed: {$failedCount}");
    }

    /**
     * Create in-app notification
     */
    private function createNotification($customer, $invoice, $subscription, $planName)
    {
        try {
            Notification::create([
                'customer_id' => $customer->id,
                'event_type' => 5, // subscription_cancelled
                'channel' => 1,    // email
                'title' => '⚠️ Subscription Auto-Cancelled',
                'message' => "Your subscription to '{$planName}' has been automatically cancelled because invoice #{$invoice->invoice_number} remained unpaid for 7 days. Please contact support to reactivate.",
                'status' => 1,
                'is_read' => 0,
                'read_at' => null,
                'scheduled_at' => null,
                'sent_status' => 1,
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info("In-app notification created for customer #{$customer->id}");
        } catch (\Exception $e) {
            Log::error("Failed to create notification: " . $e->getMessage());
        }
    }

    /**
     * Send email notification
     */
    private function sendEmail($customer, $invoice, $subscription)
    {
        try {
            if ($customer->email) {
                Mail::to($customer->email)->send(
                    new SubscriptionAutoCancelledMail($subscription, $invoice, $customer)
                );
                Log::info("Email sent to: {$customer->email}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send email: " . $e->getMessage());
        }
    }

    /**
     * Publish to Kafka
     */
    private function publishToKafka($subscription, $customer, $invoice, $planName)
    {
        try {
            Kafka::publish()
                ->onTopic('subscription.auto.cancelled')
                ->withBodyKey('subscription_id', $subscription->id)
                ->withBodyKey('customer_id', $customer->id)
                ->withBodyKey('customer_email', $customer->email)
                ->withBodyKey('customer_name', $customer->name)
                ->withBodyKey('invoice_id', $invoice->id)
                ->withBodyKey('invoice_number', $invoice->invoice_number)
                ->withBodyKey('amount', $invoice->amount)
                ->withBodyKey('due_date', $invoice->due_date ? $invoice->due_date->toDateString() : null)
                ->withBodyKey('plan_name', $planName)
                ->withBodyKey('event_type', 'auto_cancelled')
                ->withBodyKey('reason', 'Payment overdue for 7 days')
                ->send();

            Log::info("Kafka message published to subscription.auto.cancelled for subscription #{$subscription->id}");
        } catch (\Exception $e) {
            Log::error("Failed to publish Kafka message: " . $e->getMessage());
        }
    }
}
