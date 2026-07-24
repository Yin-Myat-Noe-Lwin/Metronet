<?php

namespace App\Jobs;

use App\Services\SubscriptionActivationService;
use App\Services\KafkaProducerService;
use App\Models\Cpe;
use App\Models\CpeAssignment;
use App\Models\CustomerAddress;
use App\Models\IspPlan;
use App\Models\ServiceArea;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Facades\Kafka;

class ProcessSubscriptionJob implements ShouldQueue
{
    use Queueable;

    public int $subscriptionId;

    public function __construct($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    }

    public function handle(
        SubscriptionActivationService $service,
        KafkaProducerService $kafkaProducer
    ): void {
        Log::info('JOB STARTED', ['subscription_id' => $this->subscriptionId]);

        $subscription = Subscription::findOrFail(
            $this->subscriptionId
        );

        // get cpe data
        $cpe = $service->activate($subscription);

        $topic = config('kafka.consumers.service_activated.topic');

        Log::info('Kafka topic debug', [
            'topic' => $topic
        ]);

        // Publish to Kafka
        try {
            $kafkaProducer->publish(
                config('kafka.consumers.service_activated.topic'),
                [
                    'subscription_id' => $subscription->id,
                    'customer_id' => $subscription->customer_id,
                    'plan_id' => $subscription->plan_id,
                    'cpe_id' => $cpe->id,
                ]
            );

            Log::info('Kafka message published successfully', [
                'subscription_id' => $subscription->id,
                'topic' => 'service.activated'
            ]);

        } catch (\Exception $e) {
            Log::error('Kafka publish failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        Log::info('Process Subscription Job COMPLETED SUCCESSFULLY', ['subscription_id' => $subscription->id]);
    }
}
