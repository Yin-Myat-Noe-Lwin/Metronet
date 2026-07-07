<?php

namespace App\Kafka\Consumers;

use App\Models\Customer;
use App\Models\Subscription;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionSuccessMail;

class ServiceActivatedConsumer
{
    public function handle($message)
    {
        $data = $message->getBody();

        $subscription = Subscription::find($data['subscription_id']);
        if (!$subscription) return;

        $customer = Customer::find($data['customer_id']);
        if (!$customer) return;

        // send mail
        Mail::to($customer->email)
            ->send(new SubscriptionSuccessMail($subscription, $customer));

        // create notification
        Notification::create([
            'customer_id' => $customer->id,
            'type' => 1,
            'title' => 'Service Activated',
            'message' => 'Your internet service is now active',
            'status' => 1,
            'sent_status' => 1,
            'sent_at' => now()
        ]);

        Log::info('Service activated notification sent', [
            'subscription_id' => $subscription->id
        ]);
    }
}
