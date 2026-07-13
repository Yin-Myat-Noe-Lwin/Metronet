<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\PaymentConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;

class PaymentConsumerCommand extends Command
{
    protected $signature = 'kafka:payment-consume';
    protected $description = 'Consume payment success events';

    public function handle()
    {
        $this->info('Starting Payment Consumer...');
        Log::info('Payment consumer started');

        try {
            // ✅ FIXED: Removed getTopic() and getBody() calls
            $consumer = Kafka::consumer()
                ->withBrokers('kafka:9092')
                ->withConsumerGroupId('payment-group')
                ->subscribe('payment.success')
                ->withHandler(function ($message) {
                    try {
                        // ✅ Get the message body correctly
                        $body = $message->getBody();

                        Log::info('📥 Message received from Kafka', [
                            'body' => $body
                        ]);

                        (new PaymentConsumer())->handle($message);

                    } catch (\Exception $e) {
                        Log::error('❌ Handler error', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                })
                ->build();

            $consumer->consume();

        } catch (\Exception $e) {
            Log::error('❌ Payment consumer crashed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
