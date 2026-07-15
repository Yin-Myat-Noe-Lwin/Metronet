<?php

namespace App\Kafka\Consumers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Subscription;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Notification;
use App\Mail\InvoiceCreatedMail;
use Throwable;

class NotificationConsumer
{
    public function handle($message)
    {
        try {
            Log::info('NotificationConsumer processing started');

            $data = $message->getBody();
            Log::info('Message data:', $data);

            if (!isset($data['invoice_id'])) {
                Log::error('Missing invoice_id in message');
                return;
            }

            $invoice = Invoice::find($data['invoice_id']);

            if (!$invoice) {
                Log::warning('Invoice not found: ' . $data['invoice_id']);
                return;
            }

            $subscription = Subscription::find($invoice->subscription_id);

            if (!$subscription) {
                Log::warning('Subscription not found for invoice: ' . $invoice->id);
                return;
            }

            $customer = Customer::find($subscription->customer_id);

            if (!$customer) {
                Log::warning('Customer not found for subscription: ' . $subscription->id);
                return;
            }

            Log::info('Sending invoice email to: ' . $customer->email);

            // Send email to customer about invoice
            try {
                Mail::to($customer->email)->send(new InvoiceCreatedMail($invoice));
                Log::info('Email sent successfully to: ' . $customer->email);
            } catch (Throwable $e) {
                Log::error('Failed to send email: ' . $e->getMessage());
                Log::error($e->getTraceAsString());
            }

            // Create notification
            try {
                Notification::create([
                    'customer_id' => $customer->id,
                    'event_type' => 1,           // 1=invoice_created
                    'channel' => 1,               // 1=email
                    'title' => 'Invoice Created',
                    'message' => 'Your invoice #' . ($invoice->invoice_number ?? $invoice->id) . ' has been generated for ' . number_format($invoice->amount, 2) . ' MMK.',
                    'status' => 1,                // 1=active
                    'is_read' => 0,               // 0=unread
                    'read_at' => null,
                    'scheduled_at' => null,
                    'sent_status' => 1,           // 1=sent
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                Log::info('Notification created for customer: ' . $customer->id);
            } catch (Throwable $e) {
                Log::error('Failed to create notification: ' . $e->getMessage());
            }

            Log::info('NotificationConsumer completed successfully');

        } catch (Throwable $e) {
            Log::error('NotificationConsumer error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}
