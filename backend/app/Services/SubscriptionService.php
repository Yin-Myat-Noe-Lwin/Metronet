<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubscriptionService
{
    public function getSubscription(int $subscriptionId): Subscription
    {
        return Subscription::with('plan')
            ->findOrFail($subscriptionId);
    }


    public function getCustomer(int $customerId): Customer
    {
        return Customer::findOrFail($customerId);
    }
}
