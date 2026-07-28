<?php

namespace App\Contracts\Repositories;

interface PlanRepositoryInterface
{
    public function getPlan(int $id);

    public function buildUpdateMessage(IspPlan $plan, array $data);

    public function processPlanDeactivation(Subscription $subscription, IspPlan $plan);
}
