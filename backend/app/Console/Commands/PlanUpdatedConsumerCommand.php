<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\PlanUpdatedConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;
use App\Services\KafkaConsumerService;

class PlanUpdatedConsumerCommand extends Command
{
    protected $signature = 'kafka:plan-updated-consume';

    public function __construct(
        private KafkaConsumerService $kafkaConsumer,
        private PlanUpdatedConsumer $consumer
    ) {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('PlanUpdatedConsumer started');

        $this->kafkaConsumer->consume(
            config('kafka.consumers.plan_updated.group_id'),
            config('kafka.consumers.plan_updated.topic'),
            function($message) {
                $this->consumer->handle($message);
            }
        );
    }
}
