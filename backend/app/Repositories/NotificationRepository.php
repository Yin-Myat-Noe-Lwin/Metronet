<?php

    namespace App\Repositories;
    use App\Contracts\Repositories\NotificationRepositoryInterface;

    use App\Models\Notification;

    class NotificationRepository implements NotificationRepositoryInterface
    {
        public function create(array $data): void
        {
            Notification::create([
                'customer_id' => $data['customer_id'],
                'event_type' => $data['event_type'],
                'channel' => $data['channel'],
                'title' => $data['title'],
                'message' => $data['message'],
                'is_read' => 0,
                'read_at' => null,
                'scheduled_at' => null,
                'sent_status' => 1,
                'sent_at' => now(),
            ]);
        }
    }
?>
