<?php

namespace App\Services;

use App\Models\IspPlan;

class PlanService
{
    public function getPlan(int $id): ?IspPlan
    {
        return IspPlan::find($id);
    }

    public function buildUpdateMessage(IspPlan $plan, array $data): string
    {
        $message = "📋 Plan '{$plan->name}' has been updated.";

        $changes = [];

        if (isset($data['old_price']) && isset($data['new_price']) && $data['old_price'] != $data['new_price']) {
            $changes[] = "Price: " . number_format($data['old_price'], 2) . " MMK → " . number_format($data['new_price'], 2) . " MMK";
        }

        if (isset($data['old_name']) && isset($data['new_name']) && $data['old_name'] != $data['new_name']) {
            $changes[] = "Name: '{$data['old_name']}' → '{$data['new_name']}'";
        }

        if (isset($data['old_download_speed']) && isset($data['new_download_speed']) && $data['old_download_speed'] != $data['new_download_speed']) {
            $changes[] = "Download Speed: {$data['old_download_speed']} Mbps → {$data['new_download_speed']} Mbps";
        }

        if (isset($data['old_upload_speed']) && isset($data['new_upload_speed']) && $data['old_upload_speed'] != $data['new_upload_speed']) {
            $changes[] = "Upload Speed: {$data['old_upload_speed']} Mbps → {$data['new_upload_speed']} Mbps";
        }

        if (isset($data['status_changed']) && $data['status_changed']) {
            $changes[] = "Status has been updated.";
        }

        if (!empty($changes)) {
            $message .= " Changes: " . implode(", ", $changes);
        }
    }
}
