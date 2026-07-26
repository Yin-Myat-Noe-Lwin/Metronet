<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\PaymentConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;
use App\Services\KafkaConsumerService;

class PaymentConsumerCommand extends Command
{
    protected $signature = 'kafka:payment-consume';

    public function __construct(
        private KafkaConsumerService $kafkaConsumer,
        private PaymentConsumer $consumer
    ) {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('Payment consumer started');

        $this->kafkaConsumer->consume(
            config('kafka.consumers.payment_completed.group_id'),
            config('kafka.consumers.payment_completed.topic'),
            function($message) {
                $this->consumer->handle($message);
            }
        );
    }
}
