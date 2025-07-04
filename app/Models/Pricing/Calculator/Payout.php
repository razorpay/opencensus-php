<?php

namespace RZP\Models\Pricing\Calculator;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric as MetricConstants;
use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Admin\Org;
use RZP\Models\Pricing;
use RZP\Http\BasicAuth;
use RZP\Models\Payout\Purpose;
use RZP\Models\Payout\Service;
use RZP\Models\Merchant\Balance\Type;
use RZP\Models\Payout as PayoutModel;
use RZP\Models\Merchant\Balance\Entity;
use RZP\Models\PayoutSource as PayoutSource;
use RZP\Models\Pricing\Fee;
use RZP\Trace\TraceCode;

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
    protected function getBasicPricingRule(Pricing\Plan $pricing, $feature)
    {
        $method   = $this->entity->getMethod();
        $orgId    = $this->entity->merchant->org->getId();
        $product  = $this->product;

        if($this->checkMobileNumberPayout() === true)
        {
            $method = PayoutModel\Method::MOBILE;
        }

        $filters = $this->getBasicPricingRuleFilters($product, $feature, $method);

        $rules = $this->applyFiltersOnRules($pricing, $filters);

        $rulesCount = count($rules);

        //
        // If pricing for the feature is optional, no rules may exist
        // In this case, we add the zero pricing rule and return
        //
        if (($rulesCount === 0) and
            (Pricing\Feature::isFeaturePricingOptional($feature) === true) and
            ($orgId === Org\Entity::RAZORPAY_ORG_ID))
        {
            $zeroPricingRule = (new Fee)->getZeroPricingPlanRule($this->entity);

            $this->pricingRules->push($zeroPricingRule);

            return;
        }

        $rule = $this->getPricingRule($rules, $method);

        if ($rule === null)
        {
            $entityName = $this->entity->getEntityName();

            $this->trace->count(count($pricing) == 0 ? MetricConstants::SERVER_ERROR_NO_PRICING_RULE_FOUND : MetricConstants::SERVER_ERROR_MULTIPLE_PRICING_RULES_FOUND,
                [
                    'route_name' => $this->app['api.route']->getCurrentRouteName(),
                    'entity' => $entityName,
                    'method' => $entityName == Constants\Entity::PAYMENT ? $this->entity->getMethod() : null,
                ]);

            throw new Exception\LogicException(
                'No appropriate pricing rule found for entity ' . $this->entity->getEntity(),
                ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT,
                ['entity' => $this->entity->toArray()]);
        }

        $this->pricingRules->push($rule);
    }

    protected function checkMobileNumberPayout(): bool
    {
        try{
            if($this->entity->getEntity() === Constants\Entity::PAYOUT and
                isset($this->entity->fundAccount) and
                (!empty($this->entity->fundAccount->getAttribute('customer_name')) and
                    !empty($this->entity->fundAccount->getAttribute('linked_number'))))
            {
                return true;
            }
            return false;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PAYOUT_TO_PHONE_NUMBER_PRICING_CHECK_FAILED);
            return false;
        }
    }

    protected function getPricingRule($rules, $method)
    {
        $balanceType = $this->entity->balance->getType();

        $rules = $this->applyProductFilters($rules);

        if ($balanceType === Type::BANKING)
        {
            $rules = $this->applyBankingAccountsFilters($rules);
        }

        /*
         Mode based pricing can only be defined on
         payouts of method=fund_transfer and method=mobile (Phone Number Payouts) at the moment.
        */
        if ($method === PayoutModel\Method::FUND_TRANSFER
            || $method === PayoutModel\Method::MOBILE)
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
        $payoutsFilter = $this->getPayoutsFilter();


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

    // Sets Payout Filter based on the purpose of the payout
    protected function getPayoutsFilter()
    {
        $purpose = $this->entity->getPurpose();
        $payoutsFilter = null;

        if ($purpose === Purpose::RZP_CHARGE_COLLECTIONS)
        {
            $payoutsFilter = $purpose;
        }

        if ($purpose === Purpose::RZP_FEES)
        {
            $payoutsFilter = $purpose;
        }

        if ($payoutsFilter === null)
        {
            $payoutsFilter = $this->getFreePayoutsFilter();
        }

        return $payoutsFilter;
    }
}
