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

    public function getCustomerSubscription(int $customerId, int $planId): ?Subscription
    {
        return Subscription::where('customer_id', $customerId)
                            ->where('plan_id', $planId)
                            ->whereIn('status', [0, 1])
                            ->first();
    }
}
