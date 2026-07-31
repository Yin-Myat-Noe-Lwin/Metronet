<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\AutoCancelledConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutoCancelledConsumerCommand extends Command
{
    protected $signature = 'kafka:auto-cancelled-consume';

    public function __construct(
        private KafkaConsumerService $kafkaConsumer,
        private AutoCancelledConsumer $consumer
    ) {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('Auto Cancelled Consumer Command started');

        $this->kafkaConsumer->consume(

            config('kafka.consumers.service_auto_cancellation.group_id'),
            config('kafka.consumers.service_auto_cancellation.topic'),

            function($message){

                $this->consumer->handle($message);

            }

        );
    }
}
