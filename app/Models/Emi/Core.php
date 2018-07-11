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

    public function calculateMinAmountForPlans(array $durations = [], string $bank = null, string $network = null)
    {
        $emiPlans = $this->repo->emi_plan->fetchByDurationsAndBankOrNetwork($durations, $bank, $network);

        $minForEachPlan = array_map(function($emiPlan) {
                              return Calculator::calculateMinAmount($emiPlan['min_amount'], $emiPlan['merchant_payback']);
                              }, $emiPlans->toArray());

        return max($minForEachPlan);
    }
}
