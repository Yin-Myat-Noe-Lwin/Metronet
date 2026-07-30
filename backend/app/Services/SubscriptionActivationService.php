<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\Cpe;
use App\Models\CpeAssignment;
use App\Models\CustomerAddress;
use App\Models\ServiceArea;
use App\Models\IspPlan;
use Exception;

class SubscriptionActivationService
{
    public function activate(Subscription $subscription): Cpe
    {
        // if subscription not pending anymore
        if ($subscription->status != 0) {
            throw new Exception('Subscription is not pending');
        }

        // find the plan
        $plan = IspPlan::findOrFail($subscription->plan_id);

        if($plan->status != 1){
            throw new Exception('Plan inactive');
        }

        // find availabe cpe
        $cpe = Cpe::where('status',0)->firstOrFail();

        // if all conditions ok, update subscription status
        $subscription->update([
            'status'=>1,
            'activated_at'=>now()
        ]);

        // create cpe assignment
        CpeAssignment::create([
            'cpe_id'=>$cpe->id,
            'subscription_id'=>$subscription->id,
            'assigned_at'=>now(),
            'status'=>1
        ]);

        // update cpe status
        $cpe->update([
            'status'=>1
        ]);

        return $cpe;
    }
}
