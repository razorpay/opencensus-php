<?php

namespace RZP\Models\Pricing\Calculator;

use Cache;

use RZP\Constants\Entity;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Pricing\Fee;
use RZP\Models\Transaction;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base as BaseModel;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;
use RZP\Models\Payment\Processor\Processor;

use Razorpay\Trace\Logger as Trace;

abstract class Base extends BaseModel\Core
{
    const IGST_PERCENTAGE = 1800; // Integrated GST
    const CGST_PERCENTAGE = 900; // Central GST
    const SGST_PERCENTAGE = 900; // State GST

    // 1st July 2017 00:00:00 IST - Timestamp at which GST will begin to be levied on transactions
    const GST_START_TIMESTAMP = 1498847400;

    // '29' - Karnataka's state code
    const RZP_GST_STATE_CODE = '29';

    const RZP_STATE = 'KA';

    const CARD_TAX_CUT_OFF = 200000;

    /**
     * For which fees needs to be calculated.
     */
    protected $entity;

    /**
     * Product line for Fee calculation (primary - pg / banking)
     *
     * @var string
     */
    protected $product;

    protected $feesSplit;

    protected $pricingRules;

    protected $amount = null;

    protected $taxComponents;

    public function __construct(BaseModel\PublicEntity $entity, string $product)
    {
        parent::__construct();

        $this->entity = $entity;

        $this->product = $product;

        $this->feesSplit = new BaseModel\PublicCollection;

        $this->pricingRules = new BaseModel\PublicCollection;

        $this->taxComponents = self::getTaxComponents($this->entity->merchant);

        $this->setAmount();
    }

    protected function setAmount()
    {
        $amount = $this->entity->getBaseAmount();

        if ($this->isFeeBearerCustomer() === true)
        {
            // 1. The first call will have the fee = 0,
            //    hence fees will be calculated on the original amount
            // 2. On validation/capture call, the fee will be set
            $amount = $amount - $this->entity->getFee();
        }

        $this->amount = $amount;
    }

    protected function isFeeBearerCustomer()
    {
        $entity = $this->entity;

        return ($entity->merchant->isFeeBearerCustomer() === true);
    }

    protected function isFeeBearerCustomerOrDynamic()
    {
        $entity = $this->entity;

        return ($entity->merchant->isFeeBearerCustomerOrDynamic() === true);
    }

    /**
     * Gets pricing calculator for entity
     */

    public static function make(BaseModel\PublicEntity $entity, string $product): Base
    {
        $entityType = $entity->getEntity();

        if (strpos($entityType, '.') !== false)
        {
            $entityType = str_replace('.', '_', $entityType);
        }

        $calculator = __NAMESPACE__. '\\' .studly_case($entityType);

        return new $calculator($entity, $product);
    }

    /**
     * Calculate by pricing plan according to pricing rule
     *
     * @param Pricing\Plan $pricing
     *
     * @return array
     */

    public function calculate(Pricing\Plan $pricing): array
    {
        $this->getRelevantPricingRule($pricing);

        list($fee, $tax) = $this->getFees();

        return [$fee, $tax, $this->feesSplit];
    }

    protected function getFees()
    {
        $fees = 0;

        foreach ($this->pricingRules as $rule)
        {
            $fee = $this->calculateRzpFee($rule);

            $fees += $fee;
        }

        $totalTaxes = $this->calculateGst($fees);

        $totalFees = $fees + $totalTaxes;

        $this->validateFees($totalFees);

        return [$totalFees, $totalTaxes];
    }

    public function validateFees($totalFees)
    {
        $amount = $this->amount;

        // In case the payment is customer fee bearer, we shouldn't check
        // $amount <= $totalFees because amount is already inclusive of the fees.
        if ($this->isFeeBearerCustomer() === true)
        {
            return;
        }

        // In case the merchant is fee bearer but on postpaid model,
        // we shouldn't check $amount <= $totalFees.
        if ($this->entity->merchant->getFeeModel() === Merchant\FeeModel::POSTPAID)
        {
            return;
        }

        // In this case, fees will almost always be greater than 0.
        // For e-mandate registration, we have taken a call to fail
        // later if balance not present.
        if ($amount === 0)
        {
            return;
        }

        list($amountCredits, $feeCredits) = $this->getAvailableAmountOrFeeCredits();

        if (($totalFees > $amount) and
            ($amountCredits <= 0) and
            ($totalFees > $feeCredits))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT,
                Payment\Entity::AMOUNT,
                [
                    'amount' => $amount,
                    'fees'   => $totalFees
                ]);
        }
    }

    protected function getAvailableAmountOrFeeCredits()
    {
        $balanceType = Balance\Type::getTypeForProduct($this->product);

        $merchantBalance = $this->entity->merchant->getBalanceByType($balanceType);

        $amountCredits = $merchantBalance->getAmountCredits();

        $feeCredits = $merchantBalance->getFeeCredits();

        return [$amountCredits, $feeCredits];
    }

    public static function getTaxRate()
    {
        // returning igst percentage as igst = cgst + sgst
        return self::IGST_PERCENTAGE;
    }

    public static function isGstApplicable($fromTimestamp)
    {
        return ($fromTimestamp >= self::GST_START_TIMESTAMP);
    }

    /**
     * Filter Pricing rule
     *
     * @param Pricing\Plan $pricing
     *
     * @throws Exception\LogicException
     */
    public function getRelevantPricingRule(Pricing\Plan $pricing)
    {
        $entityName = $this->entity->getEntity();

        $features = $this->entity->getPricingFeatures();

        $this->getBasicPricingRule($pricing, $entityName);

        $this->getAddOnPricingRule($pricing, $features, $entityName);

        $this->traceAllRules($this->pricingRules);
    }

    protected function getAddOnPricingRule(Pricing\Plan $pricing, array $features, $entityName)
    {
        return;
    }

    protected function getBasicPricingRule(Pricing\Plan $pricing, $feature)
    {
        $method   = $this->entity->getMethod();
        $orgId    = $this->entity->merchant->org->getId();
        $product  = $this->product;

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
            throw new Exception\LogicException(
                'No appropriate pricing rule found for entity ' . $this->entity->getEntity(),
                ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT,
                ['entity' => $this->entity->toArray()]);
        }

        $this->pricingRules->push($rule);
    }

    protected function applyAmountRangeFilterAndReturnOneRule($rules)
    {
        $payment = $this->entity;

        $amount = $this->amount;

        $filters = [
            [Pricing\Entity::AMOUNT_RANGE_ACTIVE, true, true, false]
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        if (count($rules) === 0)
        {
            throw new Exception\LogicException(
                'Invalid rule count: 0, Merchant Id: ' . $payment->getMerchantId(),
                ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT,
                [
                    'payment_id' => $payment->getId(),
                    'method'     => $payment->getMethod(),
                ]);
        }

        $rule = $this->chooseRuleWithAmount($rules, $amount);

        if ($rule === null)
        {
            throw new Exception\LogicException(
                'Failed to find a valid pricing rule for the payment, Merchant Id: ' . $payment->getMerchantId(),
                ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
                [
                    'payment_id' => $payment->getId(),
                    'method'     => $payment->getMethod(),
                ]);
        }

        return $rule;
    }

    protected function applyFiltersOnRules($rules, $filters)
    {
        foreach ($filters as $filter)
        {
            $beforeCount = count($rules);

            $rules = $this->filterRulesOnFieldByValue(
                $rules, $filter[0], $filter[1], $filter[2], $filter[3]);

            $afterCount = count($rules);

            if ($beforeCount !== $afterCount)
            {
                $this->trace->info(
                    TraceCode::PRICING_RULES_FILTERED_ON_FILTER,
                    [
                        'filter' => $filter[0],
                        'before_count' => $beforeCount,
                        'after_count' => $afterCount,
                    ]
                );
            }
        }

        return $rules;
    }

    /**
     * Filter pricing rules based on fieldName and fieldValue
     * If the value is not found, and a default value is allowed,
     * matches based on default value will be returned.
     *
     * @param  array       $rules         List of rules
     * @param  string      $fieldName     Field name to be filtered on
     * @param  string      $fieldValue    Field value to be filtered on
     * @param  bool        $chooseDefault Default value to be considered if field value not found
     * @param  string|null $defaultValue  Default value to be filtered on if $chooseDefault is true
     *
     * @return array
     */
    protected function filterRulesOnFieldByValue(
        $rules,
        $fieldName,
        $fieldValue,
        $chooseDefault = true,
        $defaultValue = null)
    {
        $matchRules         = [];
        $defaultMatchRules  = [];

        foreach ($rules as $rule)
        {
            $value = $rule->getAttribute($fieldName);

            if ($value === $fieldValue)
            {
                $matchRules[] = $rule;
            }
            else if (($chooseDefault === true) and
                ($value === $defaultValue))
            {
                $defaultMatchRules[] = $rule;
            }
        }

        if (empty($matchRules))
        {
            return $defaultMatchRules;
        }

        return $matchRules;
    }

    /**
     * We are modifying Customer subvention to choose rule based on original
     * amount only. This implies that only the merchant subvention rule selection
     * will be applied, irrespective of the subvention type.
     *
     * @param $rules
     * @param $amount
     *
     * @return null
     */
    protected function chooseRuleWithAmount($rules, $amount)
    {
        return $this->chooseRuleWithAmountForMerchantSubvention($rules, $amount);
    }

    /**
     * If the rules are amount range active rules,
     * choose rule based on amount
     * else return first available rule.
     *
     * @param $rules
     * @param $amount
     *
     * @return null
     * @throws Exception\RuntimeException
     */
    protected function chooseRuleWithAmountForMerchantSubvention($rules, $amount)
    {
        $relevantRule = null;

        // Either all the rules will be amount range active,
        // Else none will be, so test against only one.
        if ($rules[0]->isAmountRangeActive())
        {
            foreach ($rules as $rule)
            {
                if (($rule->getAmountRangeMin() < $amount) and
                    ($rule->getAmountRangeMax() >= $amount))
                {
                    $relevantRule = $rule;
                    break;
                }
            }

        }
        // If only one other possible rule, return it.
        else if (count($rules) === 1)
        {
            return $rules[0];
        }
        else
        {
            // please see comment in validateAndGetOnePricingRule in Models/Pricing/Payment/Calculator.php
            // for the reason to filter here
            $rules = $this->getRelevantPricingRulesForFeeBearer($rules);

            if (count($rules) === 1)
            {
                return $rules[0];
            }

            // Should not reach this case, ever.
            throw new Exception\RuntimeException(
                'Should not have reached here');
        }

        return $relevantRule;
    }

    protected function validateAndGetOnePricingRule($pricing)
    {
        if (count($pricing) !== 1)
        {
            throw new Exception\LogicException(
                'Only 1 pricing rule should have been present here. Found: ' . count($pricing));
        }

        $rule = $pricing[0];

        return $rule;
    }

    protected function getBasicPricingRuleFilters($product, $feature, $method) : array
    {
        $filters = [
            [Pricing\Entity::PRODUCT,        $product,   false, null],
            [Pricing\Entity::FEATURE,        $feature,   false, null],
            [Pricing\Entity::PAYMENT_METHOD, $method,    false, null],
        ];

        return $filters;
    }

    /**
     * Irrespective of preCalculationOfFees, Use the percent of original amount
     * to calculate razorpay fees. Tax is not included here.
     *
     * @param int $percent               e.g 2% is 200
     * @param int $fixed
     * @return int
     */
    protected function getUnroundedFees($percent, $fixed)
    {
        return $this->getRzpFeesUsingPercentOfOriginalAmount($percent, $fixed);
    }

    /**
     *
     * Uses the following formula for fees calculation
     *
     * rzpFees = percent * amount + fixed
     */
    protected function getRzpFeesUsingPercentOfOriginalAmount($percent, $fixed)
    {
        return (($this->amount * $percent) / 10000) + $fixed;
    }

    protected function traceAllRules($rules)
    {
        $verbose = $this->isVerboseLogEnabled();

        if ($verbose === false)
        {
            return;
        }

        // This is sending a lot of traces and so for
        // this tracing is not required.
        $array = [];

        foreach ($rules as $rule)
        {
            $array[] = $rule->toArray();
        }

        $this->trace->info(
            TraceCode::PAYMENT_PRICING_RULE_SELECTION,
            ['rules' => $array]);
    }

    /**
     * Verbosity of pricing rule selection logs are determined
     * by a flag held in cache
     * @return boolean verbosity flag
     */
    protected function isVerboseLogEnabled(): bool
    {
        return false;
        //commenting this for IPL
        /*
        try
        {
            $verbose = (bool) Cache::get(ConfigKey::PRICING_RULE_SELECTION_LOG_VERBOSE);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::PRICING_RULE_CONFIG_FETCH_ERROR);

            $verbose = false;
        }

        return $verbose;
        */
    }

    protected function createFeeBreakup($name, $percent, $amount, $pricingRule = null)
    {
        $params = [
            Transaction\FeeBreakup\Entity::NAME       => $name,
            Transaction\FeeBreakup\Entity::PERCENTAGE => $percent,
            Transaction\FeeBreakup\Entity::AMOUNT     => $amount,
        ];

        $feeBreakup = (new Transaction\FeeBreakup\Entity)->build($params);

        $feeBreakup->pricingRule()->associate($pricingRule);

        return $feeBreakup;
    }

    protected function calculateRzpFee(Pricing\Entity $rule)
    {
        list($percent, $fixed) = $rule->getRates();

        list($min, $max) = $rule->getMinMaxFees();

        $fee = $this->getUnroundedFees($percent, $fixed);

        $fee = (int) ceil($fee);

        // Fee is checked with bounds after being rounded up.
        // This ensures fee will always be within the bound.
        $fee = $this->compareBoundsAndGetFee($fee, $min, $max);

        $rzpFee = $this->createFeeBreakup(
            $rule->getFeature(),
            null,
            $fee,
            $rule);

        $this->feesSplit->push($rzpFee);

        return $fee;
    }

    protected function calculateGst($fee)
    {
        $totalTaxes = 0;

        $totalPercentage = 0;

        $taxComponents = $this->taxComponents;

        // Check if GST needs to be levied
        $eligibleForGst = $this->isEligibleForGst($fee);

        foreach ($taxComponents as $name => $percentage)
        {
            if (in_array($name, [FeeBreakupName::CGST, FeeBreakupName::SGST], true) === true)
            {
                $taxValue = ((int) round(($percentage * $fee) / 10000));
            }
            else if ($name === FeeBreakupName::IGST)
            {
                // Calculate as per cgst percentage, and double it to get the exact tax value.
                // We do this so that if this value needs to be split later into sgst+cgst, it is an even value
                $calculationPercentage = self::CGST_PERCENTAGE;

                $taxValue = 2 * ((int) round(($calculationPercentage * $fee) / 10000));
            }

            $taxValue = ($eligibleForGst === true) ? $taxValue : 0;

            $totalTaxes += $taxValue;

            $totalPercentage += $percentage;
        }

        $tax = $this->createFeeBreakup(FeeBreakupName::TAX, $totalPercentage, $totalTaxes);

        $this->feesSplit->push($tax);

        return $totalTaxes;
    }

    protected function isEligibleForGst($fee): bool
    {
        if ($this->entity->getEntity() === Constants\Entity::PAYMENT)
        {
            $payment = $this->entity;

            $amount = $this->amount;

            if ($payment->isFeeBearerCustomer() === true)
            {
                $amount = $amount + $fee;
            }

            //Adding fee in Amount in case merchant is on Dynamic Fee Bearer and has split the fee
            // with customer
            if($payment->hasOrder() === true and
                $payment->order->getFeeConfigId() !== null )
            {
                $customerFee = (new Payment\Processor\Processor($this->merchant))->calculateCustomerFee($payment, $payment->order, $fee);

                if($customerFee !== null)
                {
                    $amount = $amount + $customerFee;
                }
            }
            // No tax is levied on card payments of 2000 Rs. or less

            // For the Bajaj finserv emi payments we have to skip this condition because tax
            //must be calculated whether amount is smaller, equal or greater than the 2000 for bajaj emi payments
            if ( !($payment->gateway === Entity::BAJAJFINSERV and $payment->isMethod(Payment\Entity::EMI)) and
                ($payment->isMethodCardOrEmi() === true) and
                ($amount <= self::CARD_TAX_CUT_OFF))
            {
                return false;
            }
        }

        return true;
    }

    public static function getTaxComponents(Merchant\Entity $merchant): array
    {
        $gstin = $merchant->getGstin();

        return self::getTaxComponentsForMerchant($gstin, $merchant);
    }

    public static function getTaxComponentsForMerchant(string $gstin = null, Merchant\Entity $merchant): array
    {
        $merchantGstStateCode = Merchant\Detail\Entity::getStateCodeFromGstin($gstin);

        $registeredBusinessStateCode = $merchant->getBusinessRegisteredState();

        // Intrastate => Within Karnataka
        $intraStateGstApplicable = true;

        if (empty($merchantGstStateCode) === false)
        {
            $intraStateGstApplicable = ($merchantGstStateCode === self::RZP_GST_STATE_CODE);
        }
        else if (empty($registeredBusinessStateCode) === false)
        {
            $merchantStateCode = substr($registeredBusinessStateCode, 0, 2);

            $intraStateGstApplicable = (strtoupper($merchantStateCode) === self::RZP_STATE);
        }

        if ($intraStateGstApplicable === true)
        {
            return [
                FeeBreakupName::CGST => self::CGST_PERCENTAGE,
                FeeBreakupName::SGST => self::SGST_PERCENTAGE,
            ];
        }

        return [
            FeeBreakupName::IGST => self::IGST_PERCENTAGE,
        ];
    }

    /**
     * Checks for the min_fee and max_fee against fee.
     * If fee is less than min_fee, then min_fee will be charged.
     * If max_fee is available and fee is above max_fee,
     *  then max_fee will be charged.
     *
     * @param int $fee
     * @param int $min
     * @param int $max
     * @return int
     */
    protected function compareBoundsAndGetFee($fee, $min, $max)
    {
        if ($fee < $min)
        {
            $fee = $min;
        }
        else if ((is_null($max) === false) and ($fee > $max))
        {
            $fee = $max;
        }

        return $fee;
    }

    abstract protected function getPricingRule($rules, $method);
}
