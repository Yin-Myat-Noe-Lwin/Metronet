<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\SubscriptionCancelledConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubscriptionCancelledConsumerCommand extends Command
{
    protected $signature = 'kafka:subscription-cancelled-consume';

    public function handle()
    {
        Log::info('SubscriptionCancelledConsumerCommand started');

        try {
             $consumer = Kafka::consumer()
                ->withBrokers('kafka:9092')
                ->withConsumerGroupId('subscription-cancelled-group')
                ->subscribe('subscription.cancelled')
                ->withHandler(function ($message) {
                    try {
                        $body = $message->getBody();
                        Log::info('Subscription cancelled message received', $body);

                        (new SubscriptionCancelledConsumer())->handle($message);

                        Log::info('Subscription cancelled message processed successfully');
                    } catch (Throwable $e) {
                        Log::error('Handler error: ' . $e->getMessage());
                    }
                })
                ->build();

            $consumer->consume();

        } catch (Throwable $e) {
            Log::error('Subscription Cancelled Consumer Command error: ' . $e->getMessage());
            return 1;
        }
    }
}
