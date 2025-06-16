<?php

namespace RZP\Models\Emi;

use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base;
use RZP\Services\AffordabilityService;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function addEmiPlan($input)
    {

        if (isset($input[Entity::SOURCE_CHANNEL]) && $input[Entity::SOURCE_CHANNEL] === Entity::SOURCE_CHANNEL_IN_PERSON)
        {
            $affordabilityService = $this->app->make(AffordabilityService::class);
            return $affordabilityService->addOfflineEmiPlan($input);
        }

        $emiPlan = (new Entity)->build($input);

        $this->repo->merchant->findOrFailPublic($emiPlan->getMerchantId());

        $emiPlan->getValidator()->validateExistingEmiPlan();

        $emiPlan->generateId();

        try {
            $this->repo->transaction(
                function () use (&$emiPlan,$input)
                {

                    $this->repo->saveOrFail($emiPlan);

                    (new Migration)->handleDualWrite(Migration::CREATE, $emiPlan,'',$input);
                }
            );
        } catch (\Exception $e) {

            $this->trace->traceException($e, Trace::ERROR, TraceCode::EMI_PLANS_CREATION_FAILED, [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $emiPlan;
    }

    public function deleteEmiPlan($id)
    {
        $emiPlan = $this->repo->emi_plan->findOrFail($id);

        try {
            $this->repo->transaction(
                function () use ($emiPlan, $id) {
                    $this->repo->emi_plan->deleteOrFail($emiPlan);

                    (new Migration)->handleDualWrite(Migration::DELETE, $emiPlan, $id);

                }
            );
        } catch (\Exception $e) {

            $this->trace->traceException($e, Trace::ERROR, TraceCode::EMI_PLANS_DELETION_FAILED, [
                'error' => $e->getMessage(),
            ]);

            throw $e;
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

