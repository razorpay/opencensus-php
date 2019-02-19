<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Models\Base as BaseModel;

class Transfer extends Base
{
    public function __construct(BaseModel\PublicEntity $entity, string $product)
    {
        parent::__construct($entity, $product);

    }

    protected function getPricingRule($rules, $method)
    {
        //
        // Transfer pricing rules are optional -
        // However, if a rule exists, we validate that only one
        // rule is applied per transfer
        //
        $rule = $this->validateAndGetOnePricingRule($rules);

        return $rule;
    }
}
