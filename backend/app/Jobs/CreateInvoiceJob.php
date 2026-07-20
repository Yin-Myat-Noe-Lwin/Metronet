<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Services\KafkaProducerService;

class CreateInvoiceJob implements ShouldQueue
{
    use Queueable;

    public function handle(KafkaProducerService $kafkaProducer): void
    {
        Log::info("Invoice creation job started");

        // get active subscriptions
        $subscriptions = Subscription::where('status',1)
                                        ->get();

        foreach($subscriptions as $subscription){

            // check invoice already exists
            $exists = Invoice::where('subscription_id',$subscription->id)
                                ->exists();

            if($exists){
                Log::info(
                    "Invoice already exists",
                    [
                        'subscription'=>$subscription->id
                    ]
                );
                continue;
            }

            $invoice = Invoice::create([
                'invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . $subscription->id,
                'subscription_id' => $subscription->id,
                'amount' => $subscription->plan->price,
                'status' => 0,
                'due_date' => now()->addDays(7)
            ]);

            Log::info(
                "Invoice created",
                [
                    'invoice'=>$invoice->id
                ]
            );

            Log::info('Invoice topic', [
                'topic' => config('kafka.consumers.invoice_created.topic')
            ]);

            // Kafka event
            $kafkaProducer->publish(
                config('kafka.consumers.invoice_created.topic'),
                [
                    'invoice_id'=>$invoice->id,
                    'customer_id'=>$subscription->customer_id,
                    'subscription_id'=>$subscription->id
                ]
            );
        }
    }
}
