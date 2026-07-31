<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\PaymentReminderConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentReminderConsumerCommand extends Command
{
    protected $signature = 'kafka:payment-reminder-consume';

    public function __construct(
        private KafkaConsumerService $kafkaConsumer,
        private PaymentReminderConsumer $consumer
    ) {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('Payment Reminder Consumer Command started');

        $this->kafkaConsumer->consume(
            config('kafka.consumers.payment_reminder.group_id'),
            config('kafka.consumers.payment_reminder.topic'),
            function($message) {
                $this->consumer->handle($message);
            }
        );
    }
}
