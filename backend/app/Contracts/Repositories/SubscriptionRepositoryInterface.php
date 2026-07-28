<?php

    namespace App\Contracts\Repositories;

    interface SubscriptionRepositoryInterface
    {
      public function getSubscription(int $subscriptionId);

      public function getCustomerSubscription(int $customerId, int $planId);
    }
?>
