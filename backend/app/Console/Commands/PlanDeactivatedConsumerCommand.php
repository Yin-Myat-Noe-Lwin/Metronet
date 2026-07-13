<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\PlanDeactivatedConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlanDeactivatedConsumerCommand extends Command
{
    protected $signature = 'kafka:plan-deactivated-consume';
    protected $description = 'Consume plan deactivated events from Kafka';

    public function handle()
    {
        $this->info('Starting Plan Deactivated Consumer...');
        Log::info('PlanDeactivatedConsumerCommand started');

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
                ->withConsumerGroupId('plan-deactivated-group')
                ->subscribe('plan.deactivated')
                ->withHandler(function ($message) {
                    try {
                        $this->info('Plan deactivated message received');
                        Log::info('Plan deactivated message received', $message->getBody());

                        (new PlanDeactivatedConsumer())->handle($message);

                        $this->info('Plan deactivated message processed successfully');
                        Log::info('Plan deactivated message processed successfully');
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
