<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\PaymentConsumer;
use Junges\Kafka\Facades\Kafka;

class PaymentConsumerCommand extends Command
{
    protected $signature = 'kafka:payment-consume';
    protected $description = 'Consume payment success events';

    public function handle()
    {
        $consumer = Kafka::consumer()
            ->subscribe('payment.success')
            ->handler(function ($message) {
                (new PaymentConsumer())->handle($message);
            })
            ->build();

        $consumer->consume();
    }
}
