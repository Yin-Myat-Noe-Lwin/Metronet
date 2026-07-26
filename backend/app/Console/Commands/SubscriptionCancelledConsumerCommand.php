<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KafkaConsumerService;
use App\Kafka\Consumers\SubscriptionCancelledConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubscriptionCancelledConsumerCommand extends Command
{
    protected $signature = 'kafka:subscription-cancelled-consume';

    public function __construct(
        private KafkaConsumerService $kafkaConsumer,
        private SubscriptionCancelledConsumer $consumer
    )
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('SubscriptionCancelledConsumerCommand started');

        $this->kafkaConsumer->consume(
            config('kafka.consumers.service_cancelled.group_id'),
            config('kafka.consumers.service_cancelled.topic'),
            function($message) {
                $this->consumer->handle($message);
            }
        );
    }
}
