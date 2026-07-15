<?php

namespace App\Kafka\Consumers;

use Illuminate\Support\Facades\Log;
use App\Models\Subscription;
use App\Models\IspPlan;
use App\Models\Invoice;
use Junges\Kafka\Facades\Kafka;
use Throwable;

class BillingConsumer
{
    public function handle($message)
    {
        try {
            Log::info('BillingConsumer processing started');

            $data = $message->getBody();
            Log::info('Message data:', $data);

            if (!isset($data['subscription_id'])) {
                Log::error('Missing subscription_id in message');
                return;
            }

            Log::info('Looking for subscription: ' . $data['subscription_id']);

            // find the subscription
            $subscription = Subscription::find($data['subscription_id']);

            if (!$subscription) {
                Log::warning('Subscription not found: ' . $data['subscription_id']);
                return;
            }

            Log::info('Subscription found: ' . $subscription->id);

            // after subscription is found, find the related isp plan
            $plan = IspPlan::find($subscription->plan_id);

            if (!$plan) {
                Log::warning('Plan not found for subscription: ' . $subscription->id);
                return;
            }

            Log::info('Plan found: ' . $plan->id . ', Price: ' . $plan->price);

            Log::info('Creating invoice...');

            // create an invoice for customer's subscription
            $invoice = Invoice::create([
                'invoice_number' => 'INV-' . time() . '-' . $subscription->id,
                'subscription_id' => $subscription->id,
                'amount' => $plan->price,
                'due_date' => now()->addDays(7),
                'status' => 0
            ]);

            Log::info('Invoice created: ' . $invoice->id);

            Log::info('Publishing to invoice.created...');

            // publish invoice.created topic
            Kafka::publish()
                ->onTopic('invoice.created')
                ->withBodyKey('invoice_id', $invoice->id)
                ->withBodyKey('subscription_id', $subscription->id)
                ->withBodyKey('customer_id', $subscription->customer_id)
                ->withBodyKey('amount', $invoice->amount)
                ->send();

            Log::info('BillingConsumer completed successfully');

        } catch (Throwable $e) {
            Log::error('BillingConsumer error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}
