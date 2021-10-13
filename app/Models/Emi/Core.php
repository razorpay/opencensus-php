<?php

namespace RZP\Models\Emi;

use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function addEmiPlan($input)
    {
        $emiPlan = (new Entity)->build($input);

        $this->repo->merchant->findOrFailPublic($emiPlan->getMerchantId());

        $emiPlan->getValidator()->validateExistingEmiPlan();

        $emiPlan->generateId();

        (new Migration)->handleMigration(Migration::CREATE, $emiPlan);

        try
        {
            $this->repo->saveOrFail($emiPlan);
        }
        catch (\Exception $ex)
        {
            //If there is an error saving on api, this should delete the emi_plan on cps aswell.
            (new Migration)->handleMigration(Migration::DELETE, $emiPlan);

            throw $ex;
        }

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
        // of the minimum amounts needed for applicable emi plans
        return $minAmounts->max() ?? 0;
    }
}
