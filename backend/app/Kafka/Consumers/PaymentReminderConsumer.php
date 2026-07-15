<?php

namespace App\Kafka\Consumers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentReminderMail;
use Throwable;

class PaymentReminderConsumer
{
    public function handle($message)
    {
        try {
            $data = $message->getBody();

            Log::info('PaymentReminderConsumer received', ['data' => $data]);

            if (!isset($data['customer_id']) || !isset($data['invoice_id'])) {
                Log::error('Missing required fields', ['data' => $data]);
                return;
            }

            // Find customer
            $customer = Customer::find($data['customer_id']);
            if (!$customer) {
                Log::error('Customer not found', ['customer_id' => $data['customer_id']]);
                return;
            }

            // Find invoice
            $invoice = Invoice::find($data['invoice_id']);
            if (!$invoice) {
                Log::error('Invoice not found', ['invoice_id' => $data['invoice_id']]);
                return;
            }

            Log::info('Customer found', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'name' => $customer->name
            ]);

            Log::info('📄 Invoice found', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->amount,
                'days_left' => $data['days_left'] ?? 3
            ]);

            // Create notification
            try {
                $daysLeft = $data['days_left'] ?? 3;

                Notification::create([
                    'customer_id' => $customer->id,
                    'event_type' => 1, // invoice_created
                    'channel' => 1,
                    'title' => '⚠️ Payment Reminder',
                    'message' => "Your invoice #{$invoice->invoice_number} of " . number_format($invoice->amount, 2) . " MMK is due. Please pay within {$daysLeft} days to avoid subscription cancellation.",
                    'status' => 1,
                    'is_read' => 0,
                    'read_at' => null,
                    'scheduled_at' => null,
                    'sent_status' => 1,
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('Reminder notification created for customer #' . $customer->id);
            } catch (Throwable $e) {
                Log::error('Failed to create reminder notification: ' . $e->getMessage());
            }

            // Send email reminder
            try {
                if ($customer->email) {
                    Mail::to($customer->email)->send(
                        new PaymentReminderMail($invoice, $customer, $data['days_left'] ?? 3)
                    );
                    Log::info('Reminder email sent to: ' . $customer->email);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send reminder email: ' . $e->getMessage());
            }

            Log::info('PaymentReminderConsumer completed', [
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id
            ]);

        } catch (Throwable $e) {
            Log::error('PaymentReminderConsumer failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }
}
