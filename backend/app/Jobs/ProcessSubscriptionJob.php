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

    /**
     * Create a new job instance.
     */
    public function __construct($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $subscription = Subscription::find($this->subscriptionId);

        // check if subscription status is not pending
        if(!$subscription || $subscription->status !=0) {
            return;
        }

        $address = CustomerAddress::where(
                    'customer_id', $subscription->customer_id)
                    ->where('is_primary',1)
                    ->first();

        // check if customer has primary address
        if(!$address) {
            return;
        }

        $serviceAreaExists = ServiceArea::where([
                            'region' => $address->region,
                            'city'   => $address->city,
                            'township' => $address->township,
                            'status' => 1
                        ])->exists();

        // check service area is available
        if(!$serviceAreaExists) {
            return;
        }

        $plan = IspPlan::find($subscription->plan_id);

        // check chosen plan exists and is available
        if(!$plan || $plan->status != 1){
            return;
        }

        $cpe = Cpe::where('status', 0)->first();

        // check if cpe is available
        if(!$cpe) {
            return;
        }

        // if all conditions fine then subscription status updated
        $subscription->update([
            'status' => 1
        ]);

        // assign in a cpe
        CpeAssignment::create([
            'cpe_id' => $cpe->id,
            'subscription_id' => $subscription->id,
            'assigned_at' => now(),
            'status' => 1
        ]);

        // if cpe assignment ok, then update cpe status to 1
        $cpe->update([
            'status' => 1
        ]);

        //publish kafka event
        Kafka::publish()
                ->onTopic('service.activated')
                ->withBodyKey('subscription_id', $subscription->id)
                ->withBodyKey('customer_id', $subscription->customer_id)
                ->withBodyKey('plan_id', $subscription->plan_id)
                ->withBodyKey('cpe_id', $cpe->id)
                ->send();

        Log::info('Kafka msg sent successfully'.$subscription->id);
    }
}
