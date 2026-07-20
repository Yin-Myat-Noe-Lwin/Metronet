<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\PaymentConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;

class PaymentConsumerCommand extends Command
{
    protected $signature = 'kafka:payment-consume';

    public function handle()
    {
        Log::info('Payment consumer started');

        try {
            $consumer = Kafka::consumer()
                ->withBrokers(config('kafka.brokers'))
                ->withConsumerGroupId(config('kafka.consumers.payment_completed.group_id'))
                ->subscribe(config('kafka.consumers.payment_completed.topic'))
                ->withHandler(function ($message) {
                    try {
                        // Get the message body
                        $body = $message->getBody();

                        Log::info('Message received from Kafka', [
                            'body' => $body
                        ]);

                        (new PaymentConsumer())->handle($message);

                    } catch (Throwable $e) {
                        Log::error('Handler error: ' . $e->getMessage());
                    }
                })
                ->build();

            $consumer->consume();

        } catch (Throwable $e) {
            Log::error('Payment Consumer Command error: ' . $e->getMessage());
        }
    }
}
