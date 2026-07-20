<?php

namespace App\Kafka\Consumers;

use App\Models\Payment;
use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Mail\PaymentSuccessMail;
use Throwable;

class PaymentConsumer
{
    public function handle($message)
    {
        try {
            $data = $message->getBody();

            Log::info('PaymentConsumer received', ['data' => $data]);

            // Validate required fields
            if (!isset($data['payment_id']) || !isset($data['customer_id'])) {
                Log::error('Missing required fields', ['data' => $data]);
                return;
            }

            // Prevent duplicate processing
            $lockKey = 'payment_processed_' . $data['payment_id'];
            if (Cache::has($lockKey)) {
                Log::info('Duplicate payment message skipped', ['payment_id' => $data['payment_id']]);
                return;
            }

            // Find payment
            Log::info('Looking for payment', ['payment_id' => $data['payment_id']]);

            $payment = Payment::find($data['payment_id']);

            if (!$payment) {
                Log::error('Payment NOT FOUND in database', [
                    'payment_id' => $data['payment_id'],
                    'all_payments' => Payment::pluck('id')->toArray() // Log all payment IDs
                ]);
                return;
            }

            Log::info('Payment found', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'customer_id' => $payment->customer_id
            ]);

            // Find customer
            $customer = Customer::find($data['customer_id']);
            if (!$customer) {
                Log::error('Customer NOT FOUND', [
                    'customer_id' => $data['customer_id']
                ]);
                return;
            }

            Log::info('Customer found', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'name' => $customer->name
            ]);

            // SEND EMAIL to customer about payment
            try {
                if ($customer->email) {
                    Log::info('Attempting to send email to: ' . $customer->email);

                    Mail::to($customer->email)->send(new PaymentSuccessMail($payment));

                    Log::info('Payment success email sent successfully', [
                        'payment_id' => $payment->id,
                        'email' => $customer->email
                    ]);
                } else {
                    Log::warning('No email address for customer', [
                        'customer_id' => $customer->id
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('Failed to send payment email', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // CREATE NOTIFICATION
            try {
                $notification = Notification::create([
                    'customer_id' => $customer->id,
                    'event_type' => 2,
                    'channel' => 1,
                    'title' => 'Payment Successful',
                    'message' => 'Your payment of ' . number_format($payment->amount, 2) . ' MMK has been received successfully. Transaction ID: ' . ($payment->transaction_ref ?? 'N/A'),
                    'is_read' => 0,
                    'read_at' => null,
                    'scheduled_at' => null,
                    'sent_status' => 1,
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('Payment notification created', [
                    'payment_id' => $payment->id,
                    'notification_id' => $notification->id,
                    'customer_id' => $customer->id
                ]);
            } catch (Exception $e) {
                Log::error('Failed to create notification', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Mark as processed
            Cache::put($lockKey, true, 86400);

            Log::info('PaymentConsumer finished successfully', [
                'payment_id' => $payment->id
            ]);

        } catch (Throwable $e) {
            Log::error('PaymentConsumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $message->getBody() ?? null
            ]);

            throw $e;
        }
    }
}
