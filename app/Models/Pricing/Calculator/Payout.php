<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Models\Pricing;
use RZP\Http\BasicAuth;
use RZP\Models\Settlement\Channel;
use RZP\Models\Merchant\Balance\Type;
use RZP\Models\Payout as PayoutModel;
use RZP\Models\Merchant\Balance\Entity;
use RZP\Models\PayoutSource as PayoutSource;

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
        /** @var Entity $balance */
        $balance = $this->entity->balance;

        $accountType   = $balance->getAccountType();
        $channel       = $balance->getChannel();
        $authType      = $this->getAuthForPayout();
        $payoutsFilter = $this->getFreePayoutsFilter();

        $payoutSourceDetails = $this->entity->getSourceDetailsAttribute();
        $payoutSourceDetails = $payoutSourceDetails->toArray();

        if (empty($payoutSourceDetails) === false)
        {
            $sourceDetails = end($payoutSourceDetails);
            $appName = $sourceDetails[PayoutSource\Entity::SOURCE_TYPE];
        }
        else
        {
            $appName = $this->app['basicauth']->getInternalApp();
        }

        // The filters are applied in order. If you have 10 rules
        // in total. Suppose 8 rules match with account type
        // as direct then the next filter will be applied on those 8
        // rules only and so on.
        // And for a filter if any rule matches the value being passed
        // but some other rules match default value in that case
        // only those rules will be returned from the applyFiltersOnRules
        // function which match the value being passed. If no rule matches
        // the value being passed and some rules match the default value.
        // Only then those rules matching default value will be returned.
        // In general, the rules having $chooseDefault value as false
        // should be kept above the ones having it as true
        $filters = [
            [Pricing\Entity::ACCOUNT_TYPE, $accountType, false, null],
            [Pricing\Entity::PAYOUTS_FILTER, $payoutsFilter, false, null],
            [Pricing\Entity::APP_NAME, $appName, true, null],
            [Pricing\Entity::CHANNEL, $channel, true, null],
            [Pricing\Entity::AUTH_TYPE, $authType, true, null],
        ];

        return $this->applyFiltersOnRules($rules, $filters);
    }

    protected function applyPayoutModeFilters($rules)
    {
        $mode = $this->entity->getMode();

        if ($this->entity->isVaToVaPayout() === true)
        {
            $mode = PayoutModel\Mode::NEFT;
        }

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

    protected function getAuthForPayout()
    {
        return ($this->entity->getUserId() === null) ? BasicAuth\Type::PRIVATE_AUTH : BasicAuth\Type::PROXY_AUTH;
    }

    protected function getFreePayoutsFilter()
    {
        return ($this->entity->getFeeType() === PayoutModel\Entity::FREE_PAYOUT) ?
                                                PayoutModel\Entity::FREE_PAYOUT : null;
    }
}
