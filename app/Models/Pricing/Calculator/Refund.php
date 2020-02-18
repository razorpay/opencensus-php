<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Models\Pricing;
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
}
