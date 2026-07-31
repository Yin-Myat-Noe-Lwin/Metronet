<?php

namespace App\Services;

use App\Models\Cpe;
use App\Models\CpeAssignment;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CpeService
{
    /**
     * Get CPE by ID
     */
    public function getCpe(int $cpeId): ?Cpe
    {
        return Cpe::with(['currentAssignment.subscription.customer'])->find($cpeId);
    }

    /**
     * Get CPE by serial number
     */
    public function getCpeBySerial(string $serialNumber): ?Cpe
    {
        return Cpe::where('serial_number', $serialNumber)->first();
    }

    /**
     * Get CPE by MAC address
     */
    public function getCpeByMac(string $macAddress): ?Cpe
    {
        return Cpe::where('mac_address', $macAddress)->first();
    }

    /**
     * Get all customers using a specific CPE
     */
    public function getCustomersByCpe(int $cpeId): Collection
    {
        return Customer::whereHas('subscriptions.cpeAssignments', function ($query) use ($cpeId) {
            $query->where('cpe_id', $cpeId)
                  ->whereNull('unassigned_at');
        })->get();
    }

    /**
     * Get active subscription for a customer using specific CPE
     */
    public function getSubscriptionByCpeAndCustomer(int $cpeId, int $customerId): ?CpeAssignment
    {
        return CpeAssignment::with('subscription')
            ->where('cpe_id', $cpeId)
            ->whereHas('subscription', function ($query) use ($customerId) {
                $query->where('customer_id', $customerId)
                      ->whereIn('status', [0, 1]); // pending or active
            })
            ->whereNull('unassigned_at')
            ->first();
    }

    /**
     * Get current assignment for a CPE
     */
    public function getCurrentAssignment(int $cpeId): ?CpeAssignment
    {
        return CpeAssignment::with(['subscription.customer', 'subscription.plan'])
            ->where('cpe_id', $cpeId)
            ->whereNull('unassigned_at')
            ->first();
    }

    /**
     * Build update message for CPE changes
     */
    public function buildUpdateMessage(Cpe $cpe, array $data): string
    {
        $changes = [];

        if ($data['serial_changed'] ?? false) {
            $changes[] = "Serial Number: {$data['old_serial_number']} → {$data['new_serial_number']}";
        }

        if ($data['mac_changed'] ?? false) {
            $changes[] = "MAC Address: {$data['old_mac_address']} → {$data['new_mac_address']}";
        }

        if ($data['status_changed'] ?? false) {
            $changes[] = "Status: {$data['old_status_label']} → {$data['new_status_label']}";
        }

        if (empty($changes)) {
            return "CPE #{$cpe->id} ({$cpe->serial_number}) has been updated.";
        }

        return "CPE #{$cpe->id} ({$cpe->serial_number}) has been updated:\n" . implode("\n", $changes);
    }

    /**
     * Get status label
     */
    public function getStatusLabel(int $status): string
    {
        $labels = [
            0 => 'Available',
            1 => 'Assigned',
            2 => 'Faulty',
            3 => 'Maintenance',
            4 => 'Retired'
        ];

        return $labels[$status] ?? 'Unknown';
    }

    /**
     * Check if CPE update should trigger notifications
     */
    public function shouldNotify(array $data): bool
    {
        // Only notify if status changed to Assigned, Faulty, or Maintenance
        if ($data['status_changed'] ?? false) {
            $importantStatuses = [1, 2, 3]; // Assigned, Faulty, Maintenance
            return in_array($data['new_status'], $importantStatuses);
        }

        // Or if serial/MAC changed and CPE is assigned to a customer
        if (($data['serial_changed'] ?? false) || ($data['mac_changed'] ?? false)) {
            return isset($data['customer_id']) && !empty($data['customer_id']);
        }

        return false;
    }

    /**
     * Get notification title based on changes
     */
    public function getNotificationTitle(array $data): string
    {
        if ($data['status_changed'] ?? false) {
            return "CPE Status Changed to {$data['new_status_label']}";
        }

        if ($data['serial_changed'] ?? false) {
            return "CPE Serial Number Updated";
        }

        if ($data['mac_changed'] ?? false) {
            return "CPE MAC Address Updated";
        }

        return "CPE Updated";
    }

    /**
     * Get notification message for customer
     */
    public function getCustomerNotificationMessage(Cpe $cpe, array $data): string
    {
        $messages = [];

        if ($data['status_changed'] ?? false) {
            $messages[] = "Your device status has been changed from '{$data['old_status_label']}' to '{$data['new_status_label']}'.";
        }

        if ($data['serial_changed'] ?? false) {
            $messages[] = "Your device serial number has been updated from '{$data['old_serial_number']}' to '{$data['new_serial_number']}'.";
        }

        if ($data['mac_changed'] ?? false) {
            $messages[] = "Your device MAC address has been updated from '{$data['old_mac_address']}' to '{$data['new_mac_address']}'.";
        }

        return implode("\n", $messages);
    }
}
