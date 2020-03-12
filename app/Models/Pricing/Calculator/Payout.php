<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Models\Pricing;
use RZP\Models\Merchant\Balance\Type;
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

    /**
     * While we do not need to calculate the fees and tax (are being passed as arguments), we still need to create the
     * feesSplit. The getFees function retains the createFeesBreakup logic so that we don't have to maintain this code.
     * Any changes made in the feesSplit creation logic will automatically reflect here too.
     *
     * @param $fees
     * @param $tax
     * @param $pricingRuleId
     *
     * @return \RZP\Models\Base\PublicCollection
     */
    public function getFeeBreakupFromData($fees, $tax, $pricingRuleId)
    {
        $pricingRule = $this->repo->pricing->getPricingFromPricingId($pricingRuleId, true);

        $this->pricingRules = [$pricingRule];

        $this->getFees();

        return $this->feesSplit;
    }
}
