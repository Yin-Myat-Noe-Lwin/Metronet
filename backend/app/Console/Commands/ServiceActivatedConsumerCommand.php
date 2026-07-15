<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;
use App\Kafka\Consumers\ServiceActivatedConsumer;
use Illuminate\Support\Facades\Log;
use Throwable;

class ServiceActivatedConsumerCommand extends Command
{
    public function handle()
    {
        Log::info('ServiceActivatedConsumerCommand started');

        try {
            $consumer = Kafka::consumer()
                ->withBrokers('kafka:9092')
                ->withConsumerGroupId('service-activated-group')
                ->subscribe('service.activated')
                ->withHandler(function ($message) {
                    try {
                        Log::info('Service Activated message received', $message->getBody());

                        (new ServiceActivatedConsumer())->handle($message);

                        Log::info('Service Activated message processed successfully');
                    } catch (Throwable $e) {
                        Log::error('Handler error: ' . $e->getMessage());
                    }
                })
                ->build();

            $consumer->consume();

        } catch (Throwable $e) {
            Log::error('Service Activated Consumer Command error: ' . $e->getMessage());
            return 1;
        }
    }
}
