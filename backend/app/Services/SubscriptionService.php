<?php

namespace App\Services;

use App\Models\Subscription;
use App\Contracts\Repositories\SubscriptionRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubscriptionService
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository
    ) {

    }
    public function getSubscription(int $subscriptionId): Subscription
    {
        return $this->subscriptionRepository
                    ->getSubscription($subscriptionId);

    }

    public function getCustomerSubscription(int $customerId, int $planId): ?Subscription
    {
        return $this->subscriptionRepository
                    ->getCustomerSubscription($customerId, $planId);
    }
}
