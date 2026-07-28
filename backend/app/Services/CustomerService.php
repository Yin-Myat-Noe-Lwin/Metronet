<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CustomerService
{
    public function getCustomer(int $customerId): Customer
    {
        return Customer::findOrFail($customerId);
    }

    public function getCustomersByPlan(int $planId)
    {
        return Customer::whereHas('subscriptions', function ($q) use ($planId) {
            $q->where('plan_id', $planId)
              ->whereIn('status', [0,1]);
        })->with('subscriptions')->get();
    }
}
