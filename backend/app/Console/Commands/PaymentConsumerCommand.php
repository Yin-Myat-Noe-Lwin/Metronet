<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\PaymentConsumer;

use MateusJunges\Kafka\Facades\Kafka;

class PaymentConsumerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:payment-consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $consumer = Kafka::consumer()
            ->subscribe('payment.success')
            ->handler(function ($message) {
                (new PaymentConsumer())->handle($message);
            })
            ->build();

        $consumer->consume();
    }
}
