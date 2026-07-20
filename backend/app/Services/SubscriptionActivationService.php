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

        // get customer address
        $address = CustomerAddress::where('customer_id',$subscription->customer_id)
            ->where('is_primary',1)
            ->firstOrFail();

        // verify customer address with service area
        $serviceArea = ServiceArea::where([
            'region'=>$address->region,
            'city'=>$address->city,
            'township'=>$address->township,
            'status'=>1
        ])->exists();

        if(!$serviceArea){
            throw new Exception('Service area unavailable');
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
