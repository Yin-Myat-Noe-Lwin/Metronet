<?php

namespace App\Contracts\Repositories;

use App\Models\IspPlan;
use App\Models\Subscription;

interface PlanRepositoryInterface
{
    public function getPlan(int $id);

    public function buildUpdateMessage(IspPlan $plan, array $data);

    public function processPlanDeactivation(Subscription $subscription, IspPlan $plan);
}
