<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KafkaConsumerService;
use App\Kafka\Consumers\SubscriptionRejectedConsumer;
use Illuminate\Support\Facades\Log;

class SubscriptionRejectedConsumerCommand extends Command
{
    protected $signature = 'kafka:subscription-rejected-consume';

    public function __construct(
        private KafkaConsumerService $kafkaConsumer,
        private SubscriptionRejectedConsumer $consumer
    ) {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('Subscription Rejected Consumer Command started');

        $this->kafkaConsumer->consume(
            config('kafka.consumers.subscription_rejected.group_id'),
            config('kafka.consumers.subscription_rejected.topic'),
            function($message) {
                $this->consumer->handle($message);
            }
        );
    }
}
