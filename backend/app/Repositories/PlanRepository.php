<?php

    namespace App\Repositories;
    use App\Contracts\Repositories\PlanRepositoryInterface;

    use App\Models\IspPlan;
    use App\Models\Subscription;
    use Illuminate\Support\Facades\Log;

    class PlanRepository implements PlanRepositoryInterface
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

            return $message;
        }

        public function processPlanDeactivation(Subscription $subscription, IspPlan $plan): array
        {
            $isPending = $subscription && $subscription->status == 0;

            $isActive = $subscription && $subscription->status == 1;

            if ($isPending) {
                // Cancel pending subscription
                $subscription->update([
                    'status' => 4 // cancelled
                ]);

                Log::info('Pending subscription cancelled due to plan deactivation', [
                    'subscription_id' => $subscription->id,
                    'customer_id' => $subscription->customer_id,
                    'plan_id' => $plan->id
                ]);

                return [
                    'title' => 'Subscription Cancelled',
                    'message' => "⚠️ The plan '{$plan->name}' you applied for has been discontinued. Your subscription request has been cancelled. Please choose a new plan.",
                    'subject' => '⚠️ Subscription Cancelled - Plan Discontinued'
                ];

            } elseif ($isActive) {
                // For active subscriptions, keep them active until end date
                // No immediate change, just notify
                Log::info('Active subscription notified about plan deactivation', [
                    'subscription_id' => $subscription->id,
                    'customer_id' => $subscription->customer_id,
                    'plan_id' => $plan->id,
                    'end_date' => $subscription->end_date
                ]);

                return[
                    'title' => 'Plan Discontinued',
                    'message' => "⚠️ The plan '{$plan->name}' you are subscribed to has been discontinued. ".
                                "Your service will continue until {$subscription->end_date->format('F d, Y')}. ".
                                "Please choose a new plan before your current subscription ends to avoid service interruption.",
                    'subject' => '⚠️ Plan Discontinued - Action Required'
                ];
            }
        }
    }
?>
