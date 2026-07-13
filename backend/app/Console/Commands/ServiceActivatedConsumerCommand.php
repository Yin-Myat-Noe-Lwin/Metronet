<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;
use App\Kafka\Consumers\ServiceActivatedConsumer;
use Illuminate\Support\Facades\Log;
use Throwable;

class ServiceActivatedConsumerCommand extends Command
{
    protected $signature = 'kafka:service-activated-consume';
    protected $description = 'Consume service activated events from Kafka';

    public function handle()
    {
        $this->info('Starting Service Activated consumer...');
        Log::info('ServiceActivatedConsumerCommand started');

        try {
            if (!extension_loaded('rdkafka')) {
                $error = 'RDKafka extension is not loaded!';
                $this->error($error);
                Log::error($error);
                return 1;
            }

            $this->info('RDKafka extension loaded');
            Log::info('RDKafka extension loaded');

            // ✅ FIXED: Store consumer in variable before calling consume()
            $consumer = Kafka::consumer()
                ->withBrokers('kafka:9092')
                ->withConsumerGroupId('service-activated-group')
                ->subscribe('service.activated')
                ->withHandler(function ($message) {
                    try {
                        $this->info('Service Activated message received');
                        Log::info('Service Activated message received', $message->getBody());

                        (new ServiceActivatedConsumer())->handle($message);

                        $this->info('Service Activated message processed');
                        Log::info('Service Activated message processed successfully');
                    } catch (Throwable $e) {
                        $this->error('Handler error: ' . $e->getMessage());
                        Log::error('Handler error: ' . $e->getMessage());
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
