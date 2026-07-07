<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;
use App\Kafka\Consumers\ServiceActivatedConsumer;

class ServiceActivatedConsumerCommand extends Command
{
    protected $signature = 'kafka:service-activated-consume';

    public function handle()
    {
        Kafka::consumer()
            ->subscribe('service.activated')
            ->handler(function ($message) {
                (new ServiceActivatedConsumer())->handle($message);
            })
            ->build()
            ->consume();
    }
}
