<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Models\Base as BaseModel;

class Payout extends Base
{
    public function __construct(BaseModel\PublicEntity $entity, string $product)
    {
        parent::__construct($entity, $product);
    }

    protected function getPricingRule($rules, $method)
    {
        $rule = $this->applyAmountRangeFilterAndReturnOneRule($rules);

        return $rule;
    }

    public function validateFees($totalFees)
    {
        // For payout, we don't have to check for fees > amount, since
        // the balance check and balance deduction happens almost together.
        return;
    }
}
