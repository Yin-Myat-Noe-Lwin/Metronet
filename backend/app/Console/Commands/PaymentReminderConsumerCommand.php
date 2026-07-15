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

    public function handle()
    {
        Log::info('PaymentReminderConsumerCommand started');

        try {
            $consumer = Kafka::consumer()
                ->withBrokers('kafka:9092')
                ->withConsumerGroupId('payment-reminder-group')
                ->subscribe('payment.reminder')
                ->withHandler(function ($message) {
                    try {
                        Log::info('Payment reminder message received', $message->getBody());

                        (new PaymentReminderConsumer())->handle($message);

                        Log::info('Payment reminder message processed successfully');
                    } catch (Throwable $e) {
                        Log::error('Handler error: ' . $e->getMessage());
                    }
                })
                ->build();

            $consumer->consume();

        } catch (Throwable $e) {
            Log::error('Payment Reminder Consumer Command error: ' . $e->getMessage());
            return 1;
        }
    }
}
