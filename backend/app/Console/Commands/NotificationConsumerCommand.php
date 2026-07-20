<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KafkaConsumerService;
use App\Kafka\Consumers\NotificationConsumer;
use Illuminate\Support\Facades\Log;

class NotificationConsumerCommand extends Command
{
    protected $signature = 'kafka:notification-consume';

    public function __construct(
        private KafkaConsumerService $kafkaConsumer,
        private NotificationConsumer $consumer
    ) {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('NotificationConsumerCommand started');

        $this->kafkaConsumer->consume(

            config('kafka.consumers.invoice_created.group_id'),
            config('kafka.consumers.invoice_created.topic'),

            function($message){

                $this->consumer->handle($message);

            }

        );
    }
}
