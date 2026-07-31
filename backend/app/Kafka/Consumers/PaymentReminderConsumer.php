<?php

namespace App\Kafka\Consumers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\CustomerService;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Mail\PaymentReminderMail;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentReminderConsumer
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

            Log::info('Payment reminder event received', [
                'data' => $data
            ]);

            // Get customer
            $customer = $this->customerService->getCustomer($data['customer_id']);

            Log::info('Customer found', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'name' => $customer->name
            ]);

            // Get invoice
            $invoice = Invoice::with(['subscription', 'subscription.plan'])
                                ->find($data['invoice_id']);

            Log::info('Invoice found', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->amount,
                'days_left' => $data['days_left'] ?? 3
            ]);

            $daysLeft = $data['days_left'] ?? 3;

            // Send email
            $this->emailService->send(
                $customer,
                new PaymentReminderMail($invoice, $customer, $daysLeft)
            );

            Log::info('Payment reminder email sent', [
                'customer_id' => $customer->id,
                'email' => $customer->email
            ]);

            // Create notification
            $this->notificationService->create([
                'customer_id' => $customer->id,
                'event_type' => 10, // payment_reminder
                'channel' => 3,     // in_app
                'title' => '⚠️ Payment Reminder',
                'message' => "Your invoice #{$invoice->invoice_number} of " .
                             number_format($invoice->amount, 2) . " MMK is due in {$daysLeft} days. " .
                             "Please pay to avoid service interruption.",
            ]);

            Log::info('Payment reminder notification created', [
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id
            ]);

            Log::info('Payment reminder completed', [
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'days_left' => $daysLeft
            ]);

        } catch (Throwable $e) {
            Log::error('Payment reminder consumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
