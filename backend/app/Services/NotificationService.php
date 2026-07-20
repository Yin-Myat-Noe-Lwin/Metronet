<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    public function create(array $data): void
    {
        try {
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

            Log::info('Notification created', [
                'customer_id' => $data['customer_id'],
                'event_type' => $data['event_type'],
            ]);

        } catch (Throwable $e) {

            Log::error('Notification creation failed', [
                'customer_id' => $data['customer_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
