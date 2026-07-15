<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Junges\Kafka\Facades\Kafka;
use App\Mail\PaymentReminderMail;

class SendPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('Payment reminder job started at: ' . now());

        // 3-DAY REMINDER (3 days left before cancellation)
        $this->sendReminder(4, '⚠️ Payment Reminder: 3 Days Left', 'Your invoice is due in 3 days. Please pay to avoid service interruption.', 3);

        // 1-DAY REMINDER (1 day left before cancellation)
        $this->sendReminder(6, '⚠️ URGENT: Payment Due Tomorrow', 'Your invoice is due tomorrow! Please pay immediately to avoid service cancellation.', 1);

        Log::info('Payment reminder job completed');
    }

    /**
     * Send reminder for invoices at specific day
     */
    private function sendReminder(int $daysOld, string $title, string $message, int $daysLeft): void
    {
        // Get not overdues invoices
        $reminderDate = now()->subDays($daysOld);
        $nextDay = now()->subDays($daysOld - 1);

        $invoices = Invoice::where('status', 0) // pending invoices
            ->where('created_at', '>=', $reminderDate->startOfDay())
            ->where('created_at', '<', $nextDay->startOfDay())
            ->with(['subscription', 'subscription.customer', 'subscription.plan'])
            ->get();

        Log::info("{$invoices->count()} invoices for {$daysOld}-day reminder");

        foreach ($invoices as $invoice) {
            try {
                $subscription = $invoice->subscription;
                if (!$subscription) continue;

                // Skip if already cancelled or expired (subscription status 3= cancelled, 4 = expired)
                if (in_array($subscription->status, [3, 4])) continue;

                $customer = $subscription->customer;
                if (!$customer) continue;

                Log::info("Sending reminder to customer #{$customer->id} for invoice #{$invoice->invoice_number}");

                // Create notification
                $this->createNotification($customer, $invoice, $subscription, $title, $message, $daysLeft);

                // Send email
                $this->sendEmail($customer, $invoice, $subscription, $title, $daysLeft);

                // Publish to Kafka
                $this->publishToKafka($customer, $invoice, $subscription, $daysLeft);

            } catch (\Exception $e) {
                Log::error("Failed to send reminder for invoice #{$invoice->id}: " . $e->getMessage());
            }
        }
    }

    private function createNotification($customer, $invoice, $subscription, $title, $message, $daysLeft)
    {
        $urgency = $daysLeft <= 1 ? '🔴 URGENT: ' : '';

        Notification::create([
            'customer_id' => $customer->id,
            'event_type' => 1, // notification status =1 ,invoice_created
            'channel' => 1,    // email
            'title' => $urgency . $title,
            'message' => $message . " Invoice #{$invoice->invoice_number}: " . number_format($invoice->amount, 2) . " MMK. Days left: {$daysLeft}.",
            'status' => 1,
            'is_read' => 0,
            'read_at' => null,
            'scheduled_at' => null,
            'sent_status' => 1,
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Log::info("reminder sent to customer #{$customer->id}");
    }

    private function sendEmail($customer, $invoice, $subscription, $title, $daysLeft)
    {
        try {
            if ($customer->email) {
                Mail::to($customer->email)->send(
                    new PaymentReminderMail($invoice, $subscription, $customer, $daysLeft)
                );
                Log::info("Email sent to: {$customer->email}");
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function publishToKafka($customer, $invoice, $subscription, $daysLeft)
    {
        try {
            Kafka::publish()
                ->onTopic('payment.reminder')
                ->withBodyKey('customer_id', $customer->id)
                ->withBodyKey('customer_email', $customer->email)
                ->withBodyKey('customer_name', $customer->name)
                ->withBodyKey('invoice_id', $invoice->id)
                ->withBodyKey('invoice_number', $invoice->invoice_number)
                ->withBodyKey('amount', $invoice->amount)
                ->withBodyKey('due_date', $invoice->due_date?->toDateString())
                ->withBodyKey('plan_name', $subscription->plan?->name ?? 'Unknown')
                ->withBodyKey('days_left', $daysLeft)
                ->withBodyKey('event_type', $daysLeft <= 1 ? 'urgent_reminder' : 'reminder')
                ->send();

            Log::info("Kafka reminder published for invoice #{$invoice->id}");
        } catch (\Exception $e) {
            Log::error("Failed to publish Kafka: " . $e->getMessage());
        }
    }
}
