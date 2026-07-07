<?php

namespace App\Kafka\Consumers;

use App\Models\Payment;
use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PaymentSuccessMail;

class PaymentConsumer
{
    public function handle($message)
    {
        $data = $message->getBody();

        Log::info('PaymentConsumer executed', $data);

        $payment = Payment::find($data['payment_id']);
        if (!$payment) {
            return;
        }

        $customer = Customer::find($data['customer_id']);
        if (!$customer) {
            return;
        }

        // Send email
        Mail::to($customer->email)
            ->send(new PaymentSuccessMail($payment));

        // Save notification
        Notification::create([
            'customer_id' => $customer->id,
            'type' => 2, // payment successful
            'title' => 'Payment Successful',
            'message' => 'Your payment has been received successfully.',
            'status' => 1,
            'is_read' => 0,
            'sent_status' => 1,
            'sent_at' => now(),
        ]);
    }
}
