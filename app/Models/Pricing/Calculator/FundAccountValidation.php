<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Models\Base as BaseModel;

class FundAccountValidation extends Base
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

    protected function validateFees($totalFees, $amount)
    {
        // Can't use fee credits for fund account validation, so only balance matters
        return;
    }

    protected function isFeeBearerCustomer()
    {
        // Customer fee bearer is not supported for Fund account validation
        return false;
    }
}
