<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubscriptionService
{
    public function getSubscription(int $subscriptionId): Subscription
    {
        return Subscription::with('plan')
                            ->findOrFail($subscriptionId);
    }
}
