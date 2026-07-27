<?php

namespace App\Kafka\Consumers;

use App\Models\Payment;
use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Mail\PaymentSuccessMail;
use App\Services\PaymentService;
use App\Services\CustomerService;
use App\Services\SubscriptionService;
use App\Services\EmailService;
use App\Services\NotificationService;
use Throwable;

class PaymentConsumer
{
    public function __construct(
        private CustomerService $customerService,
        private PaymentService $paymentService,
        private SubscriptionService $subscriptionService,
        private EmailService $emailService,
        private NotificationService $notificationService
    ) {}

    public function handle($message)
    {
        try {
            $data = $message->getBody();

            Log::info('PaymentConsumer received', ['data' => $data]);

            $payment = $this->paymentService
                                ->getPayment(
                                    $data['payment_id']
                                );

            Log::info('Payment found', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'customer_id' => $payment->customer_id
            ]);

            $customer = $this->customerService
                                ->getCustomer(
                                    $data['customer_id']
                                );

            Log::info('Customer found', [
                'customer id' => $customer->id,
                'email' => $customer->email,
            ]);

            // Send email
            $this->emailService->send(
                $customer,
                new PaymentSuccessMail(
                    $payment,
                    $customer
                )
            );

            Log::info('Creating notification...');

            // Create notification
            $this->notificationService->create([
                'customer_id' => $customer->id,
                'event_type' => 2, // payment success
                'channel' => 1, // email channel
                'title' => 'Payment Successful',
                'message' => 'Your payment of ' . number_format($payment->amount, 2) . ' MMK has been received successfully. Transaction ID: ' . ($payment->transaction_ref ?? 'N/A')
            ]);

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
