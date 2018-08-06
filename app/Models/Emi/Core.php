<?php

namespace RZP\Models\Emi;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function addEmiPlan($input)
    {
        $emiPlan = (new Entity)->build($input);

        $emiPlan->generateId();

        $this->repo->saveOrFail($emiPlan);

        return $emiPlan;
    }

    public function calculateMinAmountForPlans(array $durations = [], string $bank = null, string $network = null): int
    {
        $emiPlans = $this->repo->emi_plan->fetchByDurationsAndBankOrNetwork($durations, $bank, $network);

        $minAmounts =  $emiPlans->map(function($emiPlan) {
            return Calculator::calculateMinAmount($emiPlan->getMinAmount(), $emiPlan->getMerchantPayback());
        });

        // Here we are doing max because we want maximum
        // of the minimum amounts needed for applicabe emi plans
        return $minAmounts->max() ?? 0;
    }
}
