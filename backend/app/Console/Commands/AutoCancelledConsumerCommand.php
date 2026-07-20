<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\AutoCancelledConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutoCancelledConsumerCommand extends Command
{
    protected $signature = 'kafka:auto-cancelled-consume';

    public function handle()
    {
        Log::info('AutoCancelledConsumerCommand started');

        try {
            $consumer = Kafka::consumer()
                ->withBrokers(config('kakfka.brokers'))
                ->withConsumerGroupId(config('kafka.consumers.service_auto_cancellation.group_id'))
                ->subscribe(config('kafka.consumers.service_auto_cancellation.topic'))
                ->withHandler(function ($message) {
                    try {
                        Log::info('Auto-cancelled message received', $message->getBody());

                        (new AutoCancelledConsumer())->handle($message);

                        Log::info('Auto-cancelled message processed successfully');
                    } catch (Throwable $e) {
                        Log::error('Handler error: ' . $e->getMessage());
                    }
                })
                ->build();

            $this->info('Consumer built, starting to consume...');
            $consumer->consume();

        } catch (Throwable $e) {
            Log::error('Auto cancelled consumer error: ' . $e->getMessage());
            return 1;
        }
    }
}
