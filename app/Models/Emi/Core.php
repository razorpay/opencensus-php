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

        $minAmounts =  $emiPlans->map(function($emiPlan, $key) {
            return Calculator::calculateMinAmount($emiPlan->getMinAmount(), $emiPlan->getMerchantPayback());
        });

        return $minAmounts->max();
    }
}
