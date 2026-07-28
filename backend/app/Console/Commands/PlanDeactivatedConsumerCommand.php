<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\PlanDeactivatedConsumer;
use App\Services\KafkaConsumerService;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlanDeactivatedConsumerCommand extends Command
{
    protected $signature = 'kafka:plan-deactivated-consume';

    public function __construct(
        private KafkaConsumerService $kafkaConsumer,
        private PlanDeactivatedConsumer $consumer
    ) {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('PlanDeactivatedConsumerCommand started');

        $this->kafkaConsumer->consume(
            config('kafka.consumers.plan_deactivated.group_id'),
            config('kafka.consumers.plan_deactivated.topic'),
            function($message) {
                $this->consumer->handle($message);
            }
        );
    }
}
