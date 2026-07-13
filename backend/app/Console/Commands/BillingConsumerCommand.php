<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\BillingConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;
use Throwable;

class BillingConsumerCommand extends Command
{
    protected $signature = 'kafka:billing-consume';
    protected $description = 'Consume billing events from Kafka';

    public function handle()
    {
        $this->info('Starting Kafka billing consumer...');
        Log::info('BillingConsumerCommand started');

        try {
            if (!extension_loaded('rdkafka')) {
                $error = 'RDKafka extension is not loaded!';
                $this->error($error);
                Log::error($error);
                return 1;
            }

            $this->info('RDKafka extension loaded');
            Log::info('RDKafka extension loaded');

            $consumer = Kafka::consumer()
                ->withBrokers('kafka:9092')
                ->withConsumerGroupId('billing-group')
                ->subscribe('service.activated')
                ->withHandler(function ($message) {
                    try {
                        $this->info('Message received');
                        Log::info('Kafka message received', $message->getBody());

                        (new BillingConsumer())->handle($message);

                        $this->info('Message processed successfully');
                        Log::info('Message processed successfully');
                    } catch (Throwable $e) {
                        $this->error('Handler error: ' . $e->getMessage());
                        Log::error('Handler error: ' . $e->getMessage());
                        Log::error($e->getTraceAsString());
                    }
                })
                ->build();

            $this->info('Consumer built, starting to consume...');
            Log::info('Consumer built successfully');

            $consumer->consume();

        } catch (Throwable $e) {
            $this->error('Fatal error: ' . $e->getMessage());
            Log::error('Fatal consumer error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return 1;
        }
    }
}
