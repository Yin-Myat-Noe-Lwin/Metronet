<?php

    use Illuminate\Support\Facades\Log;
    use App\Models\Invoice;
    use App\Models\Subscription;
    use Junges\Kafka\Facades\Kafka;

    class PaymentConsumer
    {
        public function handle($message)
        {
            $data = $message->getBody();

            $invoice = Invoice::where('id', $data['invoice_id'])->first();

            if (!$invoice) {
                return;
            }

            // mark subscription active or extend
            // $subscription = Subscription::where('id', $invoice->subscription_id)->first();

            // if ($subscription) {
            //     $subscription->update([
            //         'status' => 1,
            //         'end_date' => now()->addMonth()
            //     ]);
            // }

            // trigger notification event
            Kafka::publish()
                ->onTopic('payment.success')
                ->withBodyKey('invoice_id', $invoice->id)
                ->withBodyKey('customer_id', $subscription->customer_id)
                ->send();
        }
    }
