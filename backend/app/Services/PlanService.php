<?php

namespace App\Services;

use App\Models\IspPlan;
use App\Models\Subscription;
use App\Contracts\Repositories\PlanRepositoryInterface;

class PlanService
{
    public function __construct(
        private PlanRepositoryInterface $planRepository
    )
    {
    }

    public function getPlan(int $id): ?IspPlan
    {
        return $this->planRepository
                    ->getPlan($id);
    }

    public function buildUpdateMessage(IspPlan $plan, array $data): string
    {
        return $this->planRepository
                    ->buildUpdateMessage($plan, $data);
    }

    public function processPlanDeactivation(Subscription $subscription, IspPlan $plan): array
    {
       return $this->planRepository
                    ->processPlanDeactivation($subscription, $plan);
    }
}
