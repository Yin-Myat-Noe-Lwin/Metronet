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
        Log::info('ServiceActivatedConsumerCommand started');

        try {
             $consumer = Kafka::consumer()
                ->withBrokers('kafka:9092')
                ->withConsumerGroupId('service-activation-group')
                ->subscribe('service.activated')
                ->withHandler(function ($message) {
                    try {
                        Log::info('Kafka message received', $message->getBody());

                        (new ServiceActivatedConsumer())->handle($message);

                        Log::info('Message processed successfully');
                    } catch (Throwable $e) {
                        Log::error('Handler error: ' . $e->getMessage());
                    }
                })
                ->build();

            Log::info('Consumer built successfully');

            $consumer->consume();

        } catch (Throwable $e) {
            Log::error('Service Activation Consumer Error: ' . $e->getMessage());
            return 1;
        }
    }
}
