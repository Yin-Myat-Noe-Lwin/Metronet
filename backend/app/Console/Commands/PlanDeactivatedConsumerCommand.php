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

    public function handle()
    {
        Log::info('PlanDeactivatedConsumerCommand started');

        try {
            $consumer = Kafka::consumer()
                ->withBrokers('kafka:9092')
                ->withConsumerGroupId('plan-deactivated-group')
                ->subscribe('plan.deactivated')
                ->withHandler(function ($message) {
                    try {
                        Log::info('Plan deactivated message received', $message->getBody());

                        (new PlanDeactivatedConsumer())->handle($message);

                        Log::info('Plan deactivated message processed successfully');
                    } catch (Throwable $e) {
                        Log::error('Handler error: ' . $e->getMessage());
                    }
                })
                ->build();
            Log::info('Consumer built successfully');

            $consumer->consume();

        } catch (Throwable $e) {
            Log::error('Plan Deactivated Consumer Command error: ' . $e->getMessage());
            return 1;
        }
    }
}
