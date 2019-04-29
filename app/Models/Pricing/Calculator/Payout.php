<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Models\Pricing;
use RZP\Models\Base as BaseModel;
use RZP\Models\Payout as PayoutModel;

/**
 * Class Payout
 *
 * @package RZP\Models\Pricing\Calculator
 *
 * @property \RZP\Models\Payout\Entity $entity
 */
class Payout extends Base
{
    public function __construct(BaseModel\PublicEntity $entity, string $product)
    {
        parent::__construct($entity, $product);
    }

    protected function getPricingRule($rules, $method)
    {
        //
        // Mode based pricing can only be defined on payouts of method=fund_transfer
        // at the moment.
        //
        if ($method === PayoutModel\Method::FUND_TRANSFER)
        {
            $rules = $this->applyPayoutModeFilters($rules);
        }

        $rule = $this->applyAmountRangeFilterAndReturnOneRule($rules);

        return $rule;
    }

    public function validateFees($totalFees)
    {
        // For payout, we don't have to check for fees > amount, since
        // the balance check and balance deduction happens almost together.
        return;
    }

    protected function applyPayoutModeFilters($rules)
    {
        $mode = $this->entity->getMode();

        $filters = [
            [Pricing\Entity::PAYMENT_METHOD_TYPE, $mode, true, null],
        ];

        return $this->applyFiltersOnRules($rules, $filters);
    }
}
