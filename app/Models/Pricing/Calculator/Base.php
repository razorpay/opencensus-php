<?php

namespace RZP\Models\Pricing\Calculator;

use Cache;

use RZP\Exception;
use RZP\Constants;
use RZP\Trace\Tracer;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Constants\Entity;
use RZP\Models\Pricing\Fee;
use RZP\Models\Transaction;
use RZP\Constants\HyperTrace;
use RZP\Models\Payout\Metric;
use RZP\Constants\Metric as MetricConstants;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base as BaseModel;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Processor\Processor;
use RZP\Models\UpiMandate\Metrics as UpiMandateMetrics;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;
use RZP\Models\Pricing\Calculator\Tax\Base as TaxBase;

abstract class Base extends BaseModel\Core
{
    const TEST_MERCHANT_ID = 'Hod4BwliaNS6bo';

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

    protected $rewardAmount = null;

    protected $processor;

    const DEFAULT_PERCENT_RATE_SCALE_FACTOR = 100;

    const VALID_PERCENT_RATE_SCALE_FACTOR_VALUES = [100, 1000, 10000, 100000, 1000000];

    public function __construct(BaseModel\PublicEntity $entity, string $product)
    {
        parent::__construct();

        $this->entity = $entity;

        $this->product = $product;

        $this->feesSplit = new BaseModel\PublicCollection;

        $this->pricingRules = new BaseModel\PublicCollection;

        $this->setAmount();

        $this->setRewardAmount();
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

    protected function setRewardAmount()
    {
        $this->rewardAmount = 0;
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

    public static function make(BaseModel\PublicEntity $entity, string $product, string $type = ""): Base
    {
        $entityType = $entity->getEntity();

        if (strpos($entityType, '.') !== false)
        {
            $entityType = str_replace('.', '_', $entityType);
        }

        if (empty($type) === false)
        {
            $entityType = $type;
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

        $totalTaxes = $this->calculateTax($fees);

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

        if (($this->entity->getEntity() === Constants\Entity::PAYMENT) and
            ($this->entity->isUpi()))
        {
            $this->trace->info(
                TraceCode::PRICING_FEES_GREATER_THAN_AMOUNT,
                [
                    'merchantId' => $this->entity->getMerchantId(),
                    'fees' => ($totalFees > $amount),
                    'amountCredit' => ($amountCredits <= 0),
                    'feeCredits' => ($totalFees > $feeCredits),
                    'feeCreditsValue' => $feeCredits,
                ]
            );
        }

        if (($totalFees > $amount) and
            ($amountCredits <= 0) and
            ($totalFees > $feeCredits))
        {
            if(($this->entity->getEntity() === Constants\Entity::PAYMENT) and
               ($this->entity->isUpiRecurring() === true))
            {
                $this->trace->count(UpiMandateMetrics::UPI_AUTOPAY_PRICING_FAILED,
                    ['merchantId' => $this->entity->getMerchantId(),
                        'step'    => 'Fee greater than amount'
                    ]);
                $this->trace->info(
                    TraceCode::UPI_RECURRING_PRICING_RULE_ERROR,
                    ['merchantId' => $this->entity->getMerchantId(),
                        'step'    => 'Fee greater than amount'
                    ]
                );
            }

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

    /**
     * Filter Pricing rule for only Addon
     *
     * @param Pricing\Plan  $pricing
     *
     * @throws Exception\LogicException
     */
    protected function getRelevantPricingRuleOnlyAddon(Pricing\Plan $pricing)
    {
        $entityName = $this->entity->getEntity();

        $features = $this->entity->getPricingFeatures();

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
            $this->trace->count(count($pricing) == 0 ? MetricConstants::SERVER_ERROR_NO_PRICING_RULE_FOUND : MetricConstants::SERVER_ERROR_MULTIPLE_PRICING_RULES_FOUND,
                [
                    'route_name' => $this->app['api.route']->getCurrentRouteName()
                ]);

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
            if(($this->entity->getEntity() === Constants\Entity::PAYMENT) and
               ($payment->isUpiRecurring() === true))
            {
                $this->trace->count(UpiMandateMetrics::UPI_AUTOPAY_PRICING_FAILED,
                    ['merchantId' => $payment->getMerchantId(),
                        'step'    => 'No rule after amount range active check'
                    ]);

                $this->trace->info(
                    TraceCode::UPI_RECURRING_PRICING_RULE_ERROR,
                    ['merchantId' => $this->entity->getMerchantId(),
                        'step'    => 'No rule after amount range active check'
                    ]
                );
            }

            if (($this->app['basicauth']->getProduct() === Constants\Product::BANKING) and
                ($payment->getMerchantId() !== self::TEST_MERCHANT_ID))
            {
                $this->trace->count(Metric::SERVER_ERROR_PRICING_RULE_ABSENT_TOTAL,
                                    [
                                        'route_name' => $this->app['api.route']->getCurrentRouteName(),
                                    ]);

                Tracer::startSpanWithAttributes(HyperTrace::SERVER_ERROR_PRICING_RULE_ABSENT_TOTAL,
                    [
                        'route_name' => $this->app['api.route']->getCurrentRouteName(),
                    ]);
            }

            $this->trace->count(MetricConstants::SERVER_ERROR_NO_PRICING_RULE_FOUND,
                [
                    'route_name' => $this->app['api.route']->getCurrentRouteName()
                ]);

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
            if(($this->entity->getEntity() === Constants\Entity::PAYMENT) and
               ($payment->isUpiRecurring() === true))
            {
                $this->trace->count(UpiMandateMetrics::UPI_AUTOPAY_PRICING_FAILED,
                    ['merchantId' => $payment->getMerchantId(),
                        'step'    => 'No rule with this given amount range'
                        ]);

                $this->trace->info(
                    TraceCode::UPI_RECURRING_PRICING_RULE_ERROR,
                    ['merchantId' => $this->entity->getMerchantId(),
                        'step'    => 'No rule with this given amount range'
                    ]
                );
            }

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
                        'value' => $filter[1],
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
            $planId = '';
            if (empty($defaultMatchRules) === false) {
                $planId = $defaultMatchRules[0]->getAttribute('plan_id');;
            }
            $this->trace->count(Metrics::EMPTY_MATCHED_RULES,
                [
                    'fieldName' => $fieldName
                ]);

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

                if (($rule->getAmountRangeMin() < $amount) and
                    ($rule->getAmountRangeMax() === null) and
                    ($rule->getType() === Pricing\Type::BUY_PRICING))
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

            if(($this->entity->getEntity() === Constants\Entity::PAYMENT) and
               ($this->entity->isUpiRecurring() === true))
            {
                $this->trace->count(UpiMandateMetrics::UPI_AUTOPAY_PRICING_FAILED,
                    ['merchantId' => $this->entity->getMerchantId(),
                        'step'    => 'More than 1 rule was found at the end'
                    ]);

                $this->trace->info(
                    TraceCode::UPI_RECURRING_PRICING_RULE_ERROR,
                    ['merchantId' => $this->entity->getMerchantId(),
                        'step'    => 'More than 1 rule was found at the end'
                    ]
                );
            }

            $this->trace->count(count($rules) == 0? MetricConstants::SERVER_ERROR_NO_PRICING_RULE_FOUND : MetricConstants::SERVER_ERROR_MULTIPLE_PRICING_RULES_FOUND,
                [
                    'route_name' => $this->app['api.route']->getCurrentRouteName()
                ]);

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
            $this->trace->count(count($pricing) == 0? MetricConstants::SERVER_ERROR_NO_PRICING_RULE_FOUND : MetricConstants::SERVER_ERROR_MULTIPLE_PRICING_RULES_FOUND,
                [
                    'route_name' => $this->app['api.route']->getCurrentRouteName()
                ]);

            throw new Exception\LogicException(
                'Only 1 pricing rule should have been present here. Found: ' . count($pricing),
                count($pricing) == 0 ? ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT : null);
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

    protected function getUnroundedFees($percent, $fixed, $percentScaleFactor)
    {
        return $this->getRzpFeesUsingPercentOfOriginalAmount($percent, $fixed, $percentScaleFactor);
    }

    protected function getUnroundedFeesExcludingReward($percent, $fixed, $percentScaleFactor)
    {
        return $this->getRzpFeesExcludingRewardUsingPercentOfOriginalAmount($percent, $fixed, $percentScaleFactor);
    }

    /**
     * Irrespective of preCalculationOfFees, Use the percent of original amount
     * to calculate razorpay fees. Tax is not included here.
     *
     * @param int $percent               e.g 2% is 200
     * @param int $fixed
     * @return int
     */
    protected function getUnroundedFeesForRewardAmount($percent, $fixed, $percentScaleFactor)
    {
        return $this->getRzpFeesUsingPercentOfRewardAmount($percent, $fixed, $percentScaleFactor);
    }


    /**
     *
     * Uses the following formula for fees calculation
     *
     * rzpFees = percent * amount + fixed
     */
    protected function getRzpFeesUsingPercentOfOriginalAmount($percent, $fixed, $percentScaleFactor = 100)
    {
        return (($this->amount * $percent) / (100 * $percentScaleFactor)) + $fixed;
    }

    protected  function getRzpFeesExcludingRewardUsingPercentOfOriginalAmount($percent, $fixed, $percentScaleFactor=100)
    {
        return ((($this->amount - $this->rewardAmount)  * $percent) / (100 * $percentScaleFactor)) + $fixed;
    }

    protected function getRzpFeesUsingPercentOfRewardAmount($percent, $fixed, $percentScaleFactor = 100)
    {
        return (($this->rewardAmount * $percent) / (100 * $percentScaleFactor)) + $fixed;
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
            ['rules' => $this->redactSensitiveInfo($array)]);
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

    protected function redactSensitiveInfo(array $input)
    {
        return $input;
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

        $percentScaleFactor = $rule->getPercentRateScaleFactor();
        if (in_array($percentScaleFactor, self::VALID_PERCENT_RATE_SCALE_FACTOR_VALUES) === false) {
            $percentScaleFactor = self::DEFAULT_PERCENT_RATE_SCALE_FACTOR;
        }

        $fee = $this->getUnroundedFees($percent, $fixed, $percentScaleFactor);

        if($rule->isPaymentFeature() === true && $this->rewardAmount != null && $this->rewardAmount > 0)
        {
            $fee = $this->getUnroundedFeesExcludingReward($percent, $fixed, $percentScaleFactor);
        }

        // in case of reward feature the calculation should happen on reward amount and not on the actual payment
        // amount
        if ($rule->isRewardFeature() === true)
        {
            $fee = $this->getUnroundedFeesForRewardAmount($percent, $fixed, $percentScaleFactor);
        }

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

    protected function calculateTax($fee)
    {
        $processor = TaxBase::getTaxCalculator($this->entity, $this->amount);

        list($feeSplit, $totalPercentage, $totalTaxes) = $processor->calculateTax($fee);

        $feeBreakup = $this->createFeeBreakup($feeSplit, $totalPercentage, $totalTaxes);

        $this->feesSplit->push($feeBreakup);

        return $totalTaxes;
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
