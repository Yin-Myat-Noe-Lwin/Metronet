<?php declare(strict_types=1);

return [
    /*
     | Your kafka brokers url.
     */
    'brokers' => env('KAFKA_BROKERS'),

    'consumer_options' => [
        'session.timeout.ms' => env(
            'KAFKA_CONSUMER_SESSION_TIMEOUT_MS',
            60000
        ),

        'heartbeat.interval.ms' => env(
            'KAFKA_CONSUMER_HEARTBEAT_INTERVAL_MS',
            10000
        ),
    ],

    'consumers' => [
        'service_activated' => [
            'group_id' => env('KAFKA_SERVICE_ACTIVATED_GROUP', 'service-activated-group'),
            'topic' => env('KAFKA_SERVICE_ACTIVATED_TOPIC', 'service.activated'),
        ],
        'subscription_rejected' => [
            'group_id' => env('KAFKA_SUBSCRIPTION_REJECTED_GROUP', 'subscription-rejected-group'),
            'topic' => env('KAFKA_SUBSCRIPTION_REJECTED_TOPIC', 'subscription.rejected'),
        ],
        'invoice_created' => [
            'group_id' => env('KAFKA_INVOICE_GROUP', 'invoice-group'),
            'topic' => env('KAFKA_INVOICE_TOPIC', 'invoice.created'),
        ],
        'service_cancelled' => [
            'group_id' => env('KAFKA_SERVICE_CANCELLED_GROUP', 'service-cancelled-group'),
            'topic' => env('KAFKA_SERVICE_CANCELLED_TOPIC', 'service.cancelled'),
        ],
        'service_auto_cancellation' => [
            'group_id' => env('KAFKA_SERVICE_AUTOCANCELLED_GROUP', 'service-autocancelled-group'),
            'topic' => env('KAFKA_SERVICE_AUTOCANCELLED_TOPIC', 'service.auto.cancelled'),
        ],
        'plan_updated' => [
            'group_id' => env('KAFKA_PLAN_UPDATED_GROUP', 'plan-updated-group'),
            'topic' => env('KAFKA_PLAN_UPDATED_TOPIC', 'plan.updated'),
        ],
        'plan_deactivated' => [
            'group_id' => env('KAFKA_PLAN_DEACTIVATED_GROUP', 'plan-deactivated-group'),
            'topic' => env('KAFKA_PLAN_DEACTIVATED_TOPIC', 'plan.deactivated'),
        ],
        'payment_reminder' => [
            'group_id' => env('KAFKA_PAYMENT_REMINDER_GROUP', 'payment-reminder-group'),
            'topic' => env('KAFKA_PAYMENT_REMINDER_TOPIC', 'payment.reminder'),
        ],
        'payment_completed' => [
            'group_id' => env('KAFKA_PAYMENT_GROUP', 'payment-group'),
            'topic' => env('KAFKA_PAYMENT_TOPIC', 'payment.completed'),
        ],
        'cpe_updated' => [
            'group_id' => env('KAFKA_CPE_GROUP', 'cpe-group'),
            'topic' => env('KAFKA_CPE_TOPIC', 'cpe.updated'),
        ],
        'service_area_updated' => [
            'group_id' => env('KAFKA_SERVICE_AREA_GROUP', 'service-area-group'),
            'topic' => env('KAFKA_SERVICE_AREA_TOPIC', 'service_area.updated'),
        ],
    ],

    /*
     | Default security protocol
     */
    'securityProtocol' => env('KAFKA_SECURITY_PROTOCOL', 'PLAINTEXT'),

    /*
     | After the consumer receives its assignment from the coordinator,
     | it must determine the initial position for each assigned partition.
     | When the group is first created, before any messages have been consumed, the position is set according to a configurable
     | offset reset policy (auto.offset.reset). Typically, consumption starts either at the earliest offset or the latest offset.
     | You can choose between "latest", "earliest" or "none".
     */
    'offset_reset' => env('KAFKA_OFFSET_RESET', 'latest'),

    /*
     | If you set enable.auto.commit (which is the default), then the consumer will automatically commit offsets periodically at the
     | interval set by auto.commit.interval.ms.
     */
    'auto_commit' => env('KAFKA_AUTO_COMMIT', true),

    'sleep_on_error' => env('KAFKA_ERROR_SLEEP', 5),

    /*
     | Choose if debug is enabled or not.
     */
    'debug' => env('KAFKA_DEBUG', false),

    /*
     | The cache driver that will be used
     */
    'cache_driver' => env('KAFKA_CACHE_DRIVER', 'redis'),
];
