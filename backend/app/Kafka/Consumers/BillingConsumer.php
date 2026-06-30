<?php

  use Kafka;
  use Illuminate\Support\Facades\Log;
  use App\Models\Subscription;
  use App\Models\IspPlan;
  use App\Models\Invoice;

  class BillingConsumer {

    public function handle($message) {

        $data = $message->getBody();

        $subscription = Subscription::where('id', $data['subscription_id'])->first();

        if(!$subscription) {
          return;
        }

        $plan = IspPlan::where('id', $subscription->plan_id)->first();

        // invoice data is created
        $invoice = Invoice::create([
                    'invoice_number' => 'INV-'.time(),
                    'subscription_id' => $subscription->id,
                    'amount' => $plan->price,
                    'due_date' => now()->addDays(7),
                    'status' => 0
                    ]);

        // publishing kafka topics
        Kafka::publish()
              ->onTopic('invoice.created')
              ->withBodyKey('invoice_id', $invoice->id)
              ->withBodyKey('subscription_id', $subscription->id)
              ->withBodyKey('customer_id', $subscription->customer_id)
              ->withBodyKey('amount', $invoice->amount)
              ->send();
    }
  }
