<?php

namespace App\Kafka\Consumers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\CustomerService;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Mail\SubscriptionAutoCancelledMail;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutoCancelledConsumer
{
    public function __construct(
        private CustomerService $customerService,
        private EmailService $emailService,
        private NotificationService $notificationService
    ) {}

    public function handle($message): void
    {
        try {
            $data = $message->getBody();

            Log::info('Auto-cancelled event received', [
                'data' => $data
            ]);

            // Get customer
            $customer = $this->customerService->getCustomer($data['customer_id']);

            Log::info('Customer found', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'name' => $customer->name
            ]);

            // Get subscription
            $subscription = Subscription::with(['plan'])
                ->find($data['subscription_id']);

            Log::info('Subscription found', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status
            ]);

            // Get invoice data from Kafka (since invoice might be cancelled)
            $invoiceNumber = $data['invoice_number'] ?? 'N/A';
            $amount = $data['amount'] ?? 0;
            $planName = $data['plan_name'] ?? $subscription->plan?->name ?? 'Unknown';

            // Create invoice object for email
            $invoice = new \stdClass();
            $invoice->invoice_number = $invoiceNumber;
            $invoice->amount = $amount;

            // Send email
            $this->emailService->send(
                $customer,
                new SubscriptionAutoCancelledMail($subscription, $invoice, $customer)
            );

            Log::info('Auto-cancelled email sent', [
                'customer_id' => $customer->id,
                'email' => $customer->email
            ]);

            // Create notification
            $this->notificationService->create([
                'customer_id' => $customer->id,
                'event_type' => 5, // subscription_cancelled
                'channel' => 3,    // in_app
                'title' => '⚠️ Subscription Auto-Cancelled',
                'message' => "Your subscription to '{$planName}' has been automatically cancelled because invoice #{$invoiceNumber} of " .
                             number_format($amount, 2) . " MMK remained unpaid for 7 days. " .
                             "Please contact support to reactivate.",
            ]);

            Log::info('Auto-cancelled notification created', [
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id
            ]);

            Log::info('Auto-cancelled completed', [
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'invoice_number' => $invoiceNumber
            ]);

        } catch (Throwable $e) {
            Log::error('Auto-cancelled consumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
