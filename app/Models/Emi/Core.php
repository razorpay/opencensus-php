<?php

namespace RZP\Models\Emi;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function addEmiPlan($input)
    {
        $emiPlan = (new Entity)->build($input);

        $this->repo->merchant->findOrFailPublic($emiPlan->getMerchantId());

        $emiPlan->getValidator()->validateExistingEmiPlan();

        $emiPlan->generateId();

        $this->repo->saveOrFail($emiPlan);

        return $emiPlan;
    }

    /**
     * @param array $durations
     * @param string|null $bank
     * @param string|null $network
     * @param string|null $type | payment method type (credit, debit)
     * @return int | minAmount
     */
    public function calculateMinAmountForPlans(array $durations = [], string $bank = null, string $network = null, string $type = null): int
    {
        $emiPlans = $this->repo->emi_plan->fetchByParams($durations, $bank, $network, $type);

        $minAmounts =  $emiPlans->map(function($emiPlan) {
            return Calculator::calculateMinAmount($emiPlan->getMinAmount(), $emiPlan->getMerchantPayback());
        });

        // Here we are doing max because we want maximum
        // of the minimum amounts needed for applicabe emi plans
        return $minAmounts->max() ?? 0;
    }
}
