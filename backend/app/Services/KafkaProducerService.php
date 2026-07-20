<?php

namespace App\Services;

use Junges\Kafka\Facades\Kafka;
use Illuminate\Support\Facades\Log;
use Throwable;

class KafkaProducerService
{
    public function publish(
        string $topic,
        array $data
    ): void {
        try {
            Log::info('Publishing Kafka message', [
                'topic' => $topic,
                'data' => $data
            ]);

            $message = Kafka::publish()
                ->onTopic($topic);

            foreach ($data as $key => $value) {
                $message->withBodyKey($key, $value);
            }

            $message->send();

            Log::info('Kafka message published successfully', [
                'topic' => $topic
            ]);
        } catch (Throwable $e) {
            Log::error('Kafka publish failed', [
                'topic' => $topic,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
