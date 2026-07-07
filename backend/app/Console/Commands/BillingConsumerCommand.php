<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Consumers\BillingConsumer;
use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;

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
            ->withBrokers('kafka:9092')
            ->withConsumerGroupId('billing-group')
            ->subscribe('service.activated')
            ->withHandler(function ($message) {

                Log::info('Kafka received', $message->getBody());

                (new BillingConsumer())->handle($message);

            })
            ->build();

        $consumer->consume();
    }
}
