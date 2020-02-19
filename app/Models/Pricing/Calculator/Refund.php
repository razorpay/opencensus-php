<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Models\Pricing;
use RZP\Models\Admin\Org;
use RZP\Models\Base as BaseModel;

/**
 * Class Refund
 *
 * @package RZP\Models\Pricing\Calculator
 *
 * @property \RZP\Models\Payment\Refund\Entity $entity
 */
class Refund extends Base
{
    public function __construct(BaseModel\PublicEntity $entity, string $product)
    {
        parent::__construct($entity, $product);
    }

    protected function setAmount()
    {
        $this->amount = $this->entity->getBaseAmount();
    }

    protected function getPricingRule($rules, $method)
    {
        $rules = $this->applyRefundModeFilters($rules);

        $rules = $this->applyRefundAmountRangeFilters($rules);

        //
        // In refunds - specifically Instant Refunds we have defined a default pricing plan
        // If merchant specific rules are not found after filtering, we want to apply the default pricing plan
        // instead of failing refund creation.
        //
        // This is possible only with this approach because merchant may have some rules defined, not all.
        // In that scenario to cover all cases default pricing plan will be invoked only if merchant rules are not enough
        //
        // And this default pricing only applies to RZP Org merchants. Instant Refunds is restricted to only these merchants.
        //
        if ((count($rules) === 0) and
            ($this->entity->merchant->getOrgId() === Org\Entity::RAZORPAY_ORG_ID))
        {
            $rules = (new Pricing\Fee)->getInstantRefundsDefaultPricingPlanForMethod($this->entity);

            $rules = $this->applyRefundModeFilters($rules);
        }

        $rule = $this->applyAmountRangeFilterAndReturnOneRule($rules);

        return $rule;
    }

    public function validateFees($totalFees)
    {
        // For refund, we don't have to check for fees > amount, since
        // the balance check and balance deduction happens almost together.
        return;
    }

    protected function applyRefundModeFilters($rules)
    {
        $mode = $this->entity->getModeRequested();

        $filters = [
            [Pricing\Entity::PAYMENT_METHOD_TYPE, $mode, true, null],
        ];

        return $this->applyFiltersOnRules($rules, $filters);
    }

    protected function applyRefundAmountRangeFilters($rules)
    {
        $filters = [
            [Pricing\Entity::AMOUNT_RANGE_ACTIVE, true, true, false]
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $rules;
    }
}
