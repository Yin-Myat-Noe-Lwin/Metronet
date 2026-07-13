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
    protected $description = 'Consume subscription cancelled events from Kafka';

    public function handle()
    {
        $this->info('Starting Subscription Cancelled Consumer...');
        Log::info('SubscriptionCancelledConsumerCommand started');

        try {
            if (!extension_loaded('rdkafka')) {
                $error = 'RDKafka extension is not loaded!';
                $this->error($error);
                Log::error($error);
                return 1;
            }

            $consumer = Kafka::consumer()
                ->withBrokers('kafka:9092')
                ->withConsumerGroupId('subscription-cancelled-group')
                ->subscribe('subscription.cancelled')
                ->withHandler(function ($message) {
                    try {
                        $body = $message->getBody();
                        Log::info('📥 Subscription cancelled message received', $body);

                        (new SubscriptionCancelledConsumer())->handle($message);

                        Log::info('✅ Subscription cancelled message processed successfully');
                    } catch (Throwable $e) {
                        Log::error('❌ Handler error: ' . $e->getMessage());
                        Log::error($e->getTraceAsString());
                    }
                })
                ->build();

            $this->info('Consumer built, starting to consume...');
            $consumer->consume();

        } catch (Throwable $e) {
            $this->error('Fatal error: ' . $e->getMessage());
            Log::error('Fatal consumer error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return 1;
        }
    }
}
