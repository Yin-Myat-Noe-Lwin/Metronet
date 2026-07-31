<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\KafkaProducerService;

class AutoCancelUnpaidSubscriptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private KafkaProducerService $kafkaProducer
    ) {}

    public function handle(): void
    {
        Log::info('Auto-cancel job started at: ' . now());

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
                    Log::warning("Customer not found for subscription #{$subscription->id}");
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

                // Publish to Kafka
                $this->kafkaProducer->publish(
                    config('kafka.consumers.service_auto_cancellation.topic'),
                    [
                        'event_type' => 'auto_cancelled',
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'customer_email' => $customer->email,
                        'customer_phone' => $customer->phone_num,
                        'subscription_id' => $subscription->id,
                        'subscription_status' => $subscription->status,
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->amount,
                        'due_date' => $invoice->due_date?->toDateString(),
                        'plan_id' => $subscription->plan_id,
                        'plan_name' => $subscription->plan?->name ?? 'Unknown',
                        'plan_price' => $subscription->plan?->price ?? 0,
                        'reason' => 'Payment overdue for 7 days',
                        'cancelled_at' => now()->toIso8601String(),
                        'timestamp' => now()->toIso8601String()
                    ]
                );

                Log::info("Kafka message published for subscription #{$subscription->id}");

            } catch (\Exception $e) {
                Log::error("Error processing invoice #{$invoice->id}: " . $e->getMessage());
                Log::error($e->getTraceAsString());
                $failedCount++;
            }
        }

        Log::info("Auto-cancel completed. Cancelled: {$cancelledCount}, Failed: {$failedCount}");
    }
}
