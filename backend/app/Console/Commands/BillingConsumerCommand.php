<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\BillingConsumer;

use Junges\Kafka\Facades\Kafka;

class BillingConsumerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:billing-consume';

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
            ->subscribe('service.activated')
            ->handler(function ($message) {

                $data = $message->getBody();

                (new BillingConsumer())->handle($message);
            })
            ->build();

        $consumer->consume();

    }
}
