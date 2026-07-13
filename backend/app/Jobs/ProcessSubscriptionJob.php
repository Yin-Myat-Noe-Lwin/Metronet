<?php

namespace App\Jobs;

use App\Models\Cpe;
use App\Models\CpeAssignment;
use App\Models\CustomerAddress;
use App\Models\IspPlan;
use App\Models\ServiceArea;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Facades\Kafka;

class ProcessSubscriptionJob implements ShouldQueue
{
    use Queueable;

    public int $subscriptionId;

    public function __construct($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    }

    public function handle(): void
    {
        Log::info('🚀 JOB STARTED', ['subscription_id' => $this->subscriptionId]);

        $subscription = Subscription::find($this->subscriptionId);

        // ✅ Check 1: Subscription exists
        if (!$subscription) {
            Log::error('❌ FAILED: Subscription not found', ['subscription_id' => $this->subscriptionId]);
            return;
        }
        Log::info('✅ Subscription found', ['id' => $subscription->id, 'status' => $subscription->status]);

        // ✅ Check 2: Subscription pending
        if ($subscription->status != 0) {
            Log::error('❌ FAILED: Subscription not pending', [
                'subscription_id' => $subscription->id,
                'current_status' => $subscription->status
            ]);
            return;
        }
        Log::info('✅ Subscription is pending');

        // ✅ Check 3: Primary address
        $address = CustomerAddress::where('customer_id', $subscription->customer_id)
            ->where('is_primary', 1)
            ->first();

        if (!$address) {
            Log::error('❌ FAILED: No primary address', [
                'customer_id' => $subscription->customer_id
            ]);
            return;
        }
        Log::info('✅ Primary address found', [
            'address_id' => $address->id,
            'region' => $address->region,
            'city' => $address->city,
            'township' => $address->township
        ]);

        // ✅ Check 4: Service area
        $serviceAreaExists = ServiceArea::where([
            'region' => $address->region,
            'city' => $address->city,
            'township' => $address->township,
            'status' => 1
        ])->exists();

        if (!$serviceAreaExists) {
            Log::error('❌ FAILED: Service area not available', [
                'region' => $address->region,
                'city' => $address->city,
                'township' => $address->township
            ]);
            return;
        }
        Log::info('✅ Service area available');

        // ✅ Check 5: Plan exists and active
        $plan = IspPlan::find($subscription->plan_id);

        if (!$plan) {
            Log::error('❌ FAILED: Plan not found', ['plan_id' => $subscription->plan_id]);
            return;
        }

        if ($plan->status != 1) {
            Log::error('❌ FAILED: Plan not active', [
                'plan_id' => $plan->id,
                'plan_status' => $plan->status
            ]);
            return;
        }
        Log::info('✅ Plan is active', ['plan_id' => $plan->id, 'price' => $plan->price]);

        // ✅ Check 6: CPE available
        $cpe = Cpe::where('status', 0)->first();

        if (!$cpe) {
            Log::error('❌ FAILED: No available CPE found');
            return;
        }
        Log::info('✅ Available CPE found', ['cpe_id' => $cpe->id]);

        // ✅ ALL CHECKS PASSED - Process
        Log::info('🎉 ALL CHECKS PASSED! Processing subscription...');

        // Update subscription
        $subscription->update([
            'status' => 1,
            'activated_at' => now()
        ]);
        Log::info('✅ Subscription activated');

        // Assign CPE
        CpeAssignment::create([
            'cpe_id' => $cpe->id,
            'subscription_id' => $subscription->id,
            'assigned_at' => now(),
            'status' => 1
        ]);
        Log::info('✅ CPE assigned');

        // Update CPE status
        $cpe->update(['status' => 1]);
        Log::info('✅ CPE status updated to assigned');

        // ✅ Publish to Kafka
        try {
            Kafka::publish()
                ->onTopic('service.activated')
                ->withBodyKey('subscription_id', $subscription->id)
                ->withBodyKey('customer_id', $subscription->customer_id)
                ->withBodyKey('plan_id', $subscription->plan_id)
                ->withBodyKey('cpe_id', $cpe->id)
                ->send();

            Log::info('✅ Kafka message published successfully', [
                'subscription_id' => $subscription->id,
                'topic' => 'service.activated'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Kafka publish failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        Log::info('🎉 JOB COMPLETED SUCCESSFULLY', ['subscription_id' => $subscription->id]);
    }
}
