<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\NotificationConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationConsumerCommand extends Command
{
    protected $signature = 'kafka:notification-consume';

    public function handle()
    {
        Log::info('NotificationConsumerCommand started');

        try {
             $consumer = Kafka::consumer()
                ->withBrokers('kafka:9092')
                ->withConsumerGroupId('notification-group')
                ->subscribe('invoice.created')
                ->withHandler(function ($message) {
                    try {
                        Log::info('Kafka message received', $message->getBody());

                        (new NotificationConsumer())->handle($message);

                        Log::info('Message processed successfully');
                    } catch (Throwable $e) {
                        Log::error('Handler error: ' . $e->getMessage());
                    }
                })
                ->build();

            Log::info('Consumer built successfully');

            $consumer->consume();

        } catch (Throwable $e) {
            Log::error('Fatal consumer error: ' . $e->getMessage());
            return 1;
        }
    }
}
