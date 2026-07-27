<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubscriptionService
{
    public function getCustomer(int $customerId): Customer
    {
        return Customer::findOrFail($customerId);
    }
}
