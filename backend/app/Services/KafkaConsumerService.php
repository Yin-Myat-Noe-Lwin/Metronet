<?php

    namespace App\Services;

    use Junges\Kafka\Facades\Kafka;
    use Illuminate\Support\Facades\Log;
    use Throwable;

    class KafkaConsumerService {

        public function consume(
            string $groupId,
            string $topic,
            callable $handler
            ): void {
                Log::info('Starting Kafka Consumer',[
                    'group' => $groupId,
                    'topic' => $topic
                ]);

                try {
                    $consumer = Kafka::consumer()
                                    ->withBrokers(config('kafka.brokers'))
                                    ->withConsumerGroupId($groupId)
                                    ->subscribe($topic)
                                    ->withHandler(function ($message) use ($handler) {
                                        try {

                                            Log::info('Kafka message received', [
                                                'body' => $message->getBody()
                                            ]);

                                            $handler($message);

                                            Log::info('Message processed successfully');

                                        } catch (Throwable $e) {

                                            Log::error('Kafka message processing failed', [
                                                'error' => $e->getMessage()
                                            ]);
                                        }
                                    })->build();

                    $consumer->consume();
                } catch (Throwable $e) {
                    Log::error('Handler error', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
        }
    }
?>
