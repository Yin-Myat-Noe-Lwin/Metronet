<?php

namespace App\Kafka\Consumers;

use App\Models\IspPlan;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\PlanUpdatedMail;
use Throwable;

class PlanUpdatedConsumer
{
    public function handle($message)
    {
        try {
            $data = $message->getBody();

            Log::info('PlanUpdatedConsumer received', ['data' => $data]);

            $plan = IspPlan::find($data['plan_id']);
            if (!$plan) {
                Log::error('Plan not found', ['plan_id' => $data['plan_id']]);
                return;
            }

            // Get all customers with active subscription to this plan
            $customers = Customer::whereHas('subscriptions', function ($q) use ($plan) {
                $q->where('plan_id', $plan->id)
                  ->whereIn('status', [0, 1]);
            })->get();

            if ($customers->isEmpty()) {
                Log::info('No customers to notify for plan update', ['plan_id' => $plan->id]);
                return;
            }

            // create notification message in email
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

            // Create notification for each customer
            foreach ($customers as $customer) {
                try {
                    // Get subscription end date
                    $subscription = Subscription::where('customer_id', $customer->id)
                        ->where('plan_id', $plan->id)
                        ->whereIn('status', [0, 1])
                        ->first();

                    Notification::create([
                        'customer_id' => $customer->id,
                        'event_type' => 6,
                        'channel' => 1,
                        'title' => 'Plan Updated',
                        'message' => $message,
                        'is_read' => 0,
                        'read_at' => null,
                        'scheduled_at' => null,
                        'sent_status' => 1,
                        'sent_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    Log::info('Plan update notification created', [
                        'customer_id' => $customer->id,
                        'plan_id' => $plan->id
                    ]);

                    Mail::to($customer->email)->send(
                        new PlanUpdatedMail($plan, $customer, $data, $subscription?->end_date)
                    );

                    Log::info('Plan update email sent', [
                        'customer_id' => $customer->id,
                        'email' => $customer->email
                    ]);

                } catch (Throwable $e) {
                    Log::error('Failed to notify customer: ' . $customer->id . ' - ' . $e->getMessage());
                }
            }

            Log::info('PlanUpdatedConsumer completed', [
                'plan_id' => $plan->id,
                'customers_notified' => $customers->count()
            ]);

        } catch (Throwable $e) {
            Log::error('PlanUpdatedConsumer failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }
}
