<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Models\Pricing;
use RZP\Models\Merchant\Balance\Type;
use RZP\Models\Payout as PayoutModel;

/**
 * Class SettlementOndemandPayout
 *
 * @package RZP\Models\Pricing\Calculator
 *
 * @property \RZP\Models\Settlement\OndemandPayout\Entity $entity
 */
class SettlementOndemandPayout extends Base
{
    protected function setAmount()
    {
        $this->amount = $this->entity->getBaseAmount();
    }

    public function validateFees($totalFees)
    {
        return;
    }

    protected function getPricingRule($rules, $method)
    {
        $rule = $rules = $this->applyOndemandPayoutMethodFilters($rules, $method);

        $rule = $this->validateAndGetOnePricingRule($rules);

        return $rule;
    }

    public function getRelevantPricingRule(Pricing\Plan $pricing)
    {
        $entityName = Pricing\Feature::SETTLEMENT_ONDEMAND;

        if($this->entity->scheduled == true)
        {
            $entityName = Pricing\Feature::ESAUTOMATIC_RESTRICTED;
        }

        $this->getBasicPricingRule($pricing, $entityName);

        $this->traceAllRules($this->pricingRules);
    }

    public function getFeeBreakupFromData($fees, $tax, $pricingRuleId)
    {
        $pricingRule = $this->repo->pricing->getPricingFromPricingId($pricingRuleId, true);

        $this->pricingRules = [$pricingRule];

        $this->getFees();

        return $this->feesSplit;
    }

    protected function applyOndemandPayoutMethodFilters($rules, $method)
    {
        $filters = [
            [Pricing\Entity::PAYMENT_METHOD_TYPE, $method, true, null],
        ];

        return $this->applyFiltersOnRules($rules, $filters);
    }
}
