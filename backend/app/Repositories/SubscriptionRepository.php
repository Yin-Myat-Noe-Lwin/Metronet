<?php

    namespace App\Repositories;

    use App\Models\Subscription;
    use App\Contracts\Repositories\SubscriptionRepositoryInterface;

    class SubscriptionRepository implements SubscriptionRepositoryInterface
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
?>
