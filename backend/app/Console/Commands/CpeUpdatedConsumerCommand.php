<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KafkaConsumerService;
use App\Kafka\Consumers\CpeUpdatedConsumer;
use Illuminate\Support\Facades\Log;

class NotificationConsumerCommand extends Command
{
    protected $signature = 'kafka:cpe-updated-consume';

    public function __construct(
        private KafkaConsumerService $kafkaConsumer,
        private CpeUpdatedConsumer $consumer
    ) {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('Cpe updated Consumer Command started');

        $this->kafkaConsumer->consume(

            config('kafka.consumers.cpe_updated.group_id'),
            config('kafka.consumers.cpe_updated.topic'),

            function($message){

                $this->consumer->handle($message);

            }

        );
    }
}
