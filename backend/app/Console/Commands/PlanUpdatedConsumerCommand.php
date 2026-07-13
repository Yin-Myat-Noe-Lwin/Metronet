<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\PlanUpdatedConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;

class PlanUpdatedConsumerCommand extends Command
{
    protected $signature = 'kafka:plan-updated-consume';
    protected $description = 'Consume plan updated events';

    public function handle()
    {
        $this->info('Starting Plan Updated Consumer...');
        Log::info('PlanUpdatedConsumer started');

        try {
            $consumer = Kafka::consumer()
                ->withBrokers('kafka:9092')
                ->withConsumerGroupId('plan-updated-group')
                ->subscribe('plan.updated')
                ->withHandler(function ($message) {
                    try {
                        Log::info('📥 Plan updated message received', $message->getBody());
                        (new PlanUpdatedConsumer())->handle($message);
                    } catch (\Exception $e) {
                        Log::error('❌ Handler error: ' . $e->getMessage());
                    }
                })
                ->build();

            $consumer->consume();

        } catch (\Exception $e) {
            Log::error('❌ PlanUpdatedConsumer crashed: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
