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

class SendPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private KafkaProducerService $kafkaProducer
    ) {}

    public function handle(): void
    {
        Log::info('Payment reminder job started at: ' . now());

        // 3-DAY REMINDER (3 days left before due date)
        $this->sendReminder(3, 'reminder');

        // 1-DAY REMINDER (1 day left before due date)
        $this->sendReminder(1, 'urgent_reminder');

        Log::info('Payment reminder job completed');
    }

    /**
     * Send reminder for invoices with specific days left
     */
    private function sendReminder(int $daysLeft, string $eventType): void
    {
        // Find invoices where due_date is exactly X days from now
        $targetDate = now()->addDays($daysLeft)->startOfDay();

        $invoices = Invoice::where('status', 0) // pending invoices
                        ->whereDate('due_date', $targetDate->toDateString())
                        ->with(['subscription', 'subscription.customer', 'subscription.plan'])
                        ->get();

        Log::info("Found {$invoices->count()} invoices with {$daysLeft} days left");

        foreach ($invoices as $invoice) {
            try {
                $subscription = $invoice->subscription;
                if (!$subscription) continue;

                // Skip if already expired (3) or cancelled (4)
                if (in_array($subscription->status, [3, 4])) continue;

                $customer = $subscription->customer;
                if (!$customer) continue;

                Log::info("Publishing reminder for customer #{$customer->id}, invoice #{$invoice->invoice_number}");

                // Publish to Kafka
                $this->kafkaProducer->publish(
                    config('kafka.consumers.payment_reminder.topic', 'payment.reminder'),
                    [
                        'event_type' => $eventType,
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'customer_email' => $customer->email,
                        'customer_phone' => $customer->phone_num,
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->amount,
                        'due_date' => $invoice->due_date?->toDateString(),
                        'subscription_id' => $subscription->id,
                        'plan_id' => $subscription->plan_id,
                        'plan_name' => $subscription->plan?->name ?? 'Unknown',
                        'plan_price' => $subscription->plan?->price ?? 0,
                        'days_left' => $daysLeft,
                        'timestamp' => now()->toIso8601String()
                    ]
                );

                Log::info("Kafka reminder published for invoice #{$invoice->id}");

            } catch (\Exception $e) {
                Log::error("Failed to publish reminder for invoice #{$invoice->id}: " . $e->getMessage());
            }
        }
    }
}
