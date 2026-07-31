<?php

namespace App\Services;

use App\Models\ServiceArea;
use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ServiceAreaService
{
    /**
     * Get service area by ID
     */
    public function getServiceArea(int $id): ?ServiceArea
    {
        return ServiceArea::find($id);
    }

    /**
     * Get customers with active subscriptions in a service area
     */
    public function getCustomersByServiceArea(ServiceArea $serviceArea): Collection
    {
        return Customer::whereHas('subscriptions', function ($query) use ($serviceArea) {
            $query->where('status', 1) // Active subscriptions only
                ->whereHas('installationAddress', function ($q) use ($serviceArea) {
                    $q->where('region', $serviceArea->region)
                        ->where('city', $serviceArea->city)
                        ->where('township', $serviceArea->township);
                });
        })->with(['subscriptions' => function ($query) use ($serviceArea) {
            $query->where('status', 1)
                ->whereHas('installationAddress', function ($q) use ($serviceArea) {
                    $q->where('region', $serviceArea->region)
                        ->where('city', $serviceArea->city)
                        ->where('township', $serviceArea->township);
                });
        }])->get();
    }

    /**
     * Get customer's subscription in a service area
     */
    public function getCustomerSubscription(Customer $customer, ServiceArea $serviceArea): ?Subscription
    {
        return Subscription::where('customer_id', $customer->id)
            ->where('status', 1) // Active
            ->whereHas('installationAddress', function ($query) use ($serviceArea) {
                $query->where('region', $serviceArea->region)
                    ->where('city', $serviceArea->city)
                    ->where('township', $serviceArea->township);
            })
            ->first();
    }

    /**
     * Build deactivation message for notification
     */
    public function buildDeactivationMessage(
        ServiceArea $serviceArea,
        Customer $customer,
        ?Subscription $subscription,
        array $data
    ): string {
        $planName = $subscription?->plan?->name ?? 'your current plan';
        $subscriptionId = $subscription?->id ?? 'N/A';
        $companyName = config('app.name', 'MetroNet');
        $supportEmail = config('app.support_email', 'support@metronet.com');

        $changes = [];
        if ($data['region_changed'] ?? false) {
            $changes[] = "Region: {$data['old_region']} → {$data['new_region']}";
        }
        if ($data['city_changed'] ?? false) {
            $changes[] = "City: {$data['old_city']} → {$data['new_city']}";
        }
        if ($data['township_changed'] ?? false) {
            $changes[] = "Township: {$data['old_township']} → {$data['new_township']}";
        }

        $message = "Dear {$customer->name},\n\n";
        $message .= "We regret to inform you that our service in {$serviceArea->township}, ";
        $message .= "{$serviceArea->city}, {$serviceArea->region} is no longer available.\n\n";

        if (!empty($changes)) {
            $message .= "The following changes have been made:\n";
            $message .= implode("\n", $changes) . "\n\n";
        }

        $message .= "Your subscription (#{$subscriptionId}) for '{$planName}' will be affected.\n\n";
        $message .= "Please contact our support team at {$supportEmail} ";
        $message .= "for assistance with alternative options or billing adjustments.\n\n";
        $message .= "We apologize for any inconvenience this may cause.\n\n";
        $message .= "Best regards,\n";
        $message .= $companyName . " Team";

        return $message;
    }

    /**
     * Get status label
     */
    public function getStatusLabel(int $status): string
    {
        $labels = [
            0 => 'Inactive',
            1 => 'Active'
        ];

        return $labels[$status] ?? 'Unknown';
    }
}
