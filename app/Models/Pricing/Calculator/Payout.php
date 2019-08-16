<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Exception;
use RZP\Models\Pricing;
use RZP\Models\Merchant\Balance\Type;
use RZP\Models\Payout as PayoutModel;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\BankingAccountStatement\Channel;

/**
 * Class Payout
 *
 * @package RZP\Models\Pricing\Calculator
 *
 * @property \RZP\Models\Payout\Entity $entity
 */
class Payout extends Base
{
    public function validateFees($totalFees)
    {
        // For payout, we don't have to check for fees > amount, since
        // the balance check and balance deduction happens almost together.
        return;
    }

    protected function getPricingRule($rules, $method)
    {
        $balanceType = $this->entity->balance->getType();

        $rules = $this->applyProductFilters($rules);

        if ($balanceType === Type::BANKING)
        {
            $rules = $this->applyBankingAccountsFilters($rules);
        }

        //
        // Mode based pricing can only be defined on
        // payouts of method=fund_transfer at the moment.
        //
        if ($method === PayoutModel\Method::FUND_TRANSFER)
        {
            $rules = $this->applyPayoutModeFilters($rules);
        }

        $rule = $this->applyAmountRangeFilterAndReturnOneRule($rules);

        return $rule;
    }

    protected function applyProductFilters(array $rules)
    {
        $balance = $this->entity->balance;
        $type    = $balance->getType();

        $filters = [
            [Pricing\Entity::PRODUCT, $type, false, null],
        ];

        return $this->applyFiltersOnRules($rules, $filters);
    }

    /**
     * Apply rules for banking accounts
     *
     * @param array $rules
     * @return array
     */
    protected function applyBankingAccountsFilters(array $rules)
    {
        $balance = $this->entity->balance;

        $accountType = $balance->getAccountType();
        $channel     = $balance->getChannel();

        $filters = [
            [Pricing\Entity::ACCOUNT_TYPE, $accountType, false, null],
            [Pricing\Entity::CHANNEL,      $channel,     true, null],
        ];

        return $this->applyFiltersOnRules($rules, $filters);
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
