<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use App\Contracts\Repositories\NotificationRepositoryInterface;
use Throwable;

class NotificationService
{
    public function __construct(
        NotificationepositoryInterface $notificationRepository
    ) {

    }

    public function create(array $data): void
    {
        try {
            $this->notificationepository
                ->create($data);

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
