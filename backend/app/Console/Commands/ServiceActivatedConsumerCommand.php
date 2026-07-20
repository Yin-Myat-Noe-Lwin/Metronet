<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KafkaConsumerService;
use App\Kafka\Consumers\ServiceActivatedConsumer;
use Illuminate\Support\Facades\Log;

class ServiceActivatedConsumerCommand extends Command
{
    protected $signature = 'kafka:service-activated-consume';

    public function __construct(
        private KafkaConsumerService $kafkaConsumer,
        private ServiceActivatedConsumer $consumer
    ) {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('Service Activated Consumer Command started');

        $this->kafkaConsumer->consume(
            config('kafka.consumers.service_activated.group_id'),
            config('kafka.consumers.service_activated.topic'),
            function($message) {
                $this->consumer->handle($message);
            }
        );
    }
}
