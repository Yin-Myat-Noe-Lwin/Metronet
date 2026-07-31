<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KafkaConsumerService;
use App\Kafka\Consumers\ServiceAreaUpdatedConsumer;
use Illuminate\Support\Facades\Log;

class ServiceAreaUpdatedConsumerCommand extends Command
{
    protected $signature = 'kafka:service-area-updated-consume';

    public function __construct(
        private KafkaConsumerService $kafkaConsumer,
        private ServiceAreaUpdatedConsumer $consumer
    ) {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('Service Area Updated Consumer Command started');

        $this->kafkaConsumer->consume(
            config('kafka.consumers.service_area_updated.group_id'),
            config('kafka.consumers.service_area_updated.topic'),
            function($message) {
                $this->consumer->handle($message);
            }
        );
    }
}
