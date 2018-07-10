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

    public function calculateMinAmountForPlans($bank, $network, $durations)
    {
        $emiPlans = $this->repo->emi_plan->fetchByDurationsAndBankOrNetwork($bank, $network, $durations);

        $min = 0;

        foreach ($emiPlans as $emiPlan)
        {
            $amount = Calculator::calculateMinAmount($emiPlan->getMinAmount(), $emiPlan->getMerchantPayback());

            if ($min < $amount)
            {
                $min = $amount;
            }
        }

        return $min;
    }
}
