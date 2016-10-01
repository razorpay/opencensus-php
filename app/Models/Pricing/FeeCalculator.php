<?php

namespace RZP\Models\Pricing;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Models\Pricing\FeeBreakup as FeeBreak;
use RZP\Models\Pricing\FeeBreakup\Type as FeeBreakType;
use RZP\Models\Pricing\FeeBreakup\Name as FeeBreakName;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;


class FeeCalculator
{
    const SERVICE_TAX_PERCENT = 15.0;

    const SERVICE_TAX_PERCENTAGE            = 1400;
    const SWACHH_BHARAT_CESS_PERCENTAGE     = 50;
    const KRISHI_KALYAN_CESS_PERCENTAGE     = 50;

    /**
     * For which fees needs to be calculate.
     *
     * @var RZP\Models\Payment\Entity
     */
    protected $entity;

    protected $defaultPricingPlan = '1hDYlICobzOCYt';

    public function __construct($entity)
    {
        $this->entity = $entity;

        $this->trace = \Trace::getFacadeRoot();

    }

    public function calculate($pricing, $preCalculationOfFees = false)
    {
        $entity = $this->entity;

        $rule = $this->getRelevantPricingRule($pricing);

        list($fee, $serviceTax, $feesSplit) = $this->getFees($rule, $entity->getAmount(), $preCalculationOfFees);

        return array($fee, $serviceTax, $rule->getKey(), $feesSplit);
    }


    protected function getFees($rule, $amount, $preCalculationOfFees = false)
    {
        list($percent, $fixed) = $rule->getRates();

        list($fee, $feesSplit) = $this->getUnroundedFees($amount, $percent, $fixed, self::SERVICE_TAX_PERCENTAGE, $preCalculationOfFees);

        $fee = (int) ceil($fee);

        list($totaltaxes, $feesSplit) = $this->calculateServiceTaxes($fee, $feesSplit);

        $totalFees = $fee + $totaltaxes;

        assert ($totalFees < $amount);

        return  array($totalFees, $totaltaxes, $feesSplit);
    }

    public static function getServiceTaxRate()
    {
        return self::SERVICE_TAX_PERCENTAGE + self::KRISHI_KALYAN_CESS_PERCENTAGE + self::SWACHH_BHARAT_CESS_PERCENTAGE;
    }

    public static function getServiceTaxPercentage()
    {
        return self::SERVICE_TAX_PERCENTAGE;
    }

    public static function getKrishiKalyanCessPercentage()
    {
        return self::KRISHI_KALYAN_CESS_PERCENTAGE;
    }

    public static function getSwachhBharatCessPercentage()
    {
        return self::SWACHH_BHARAT_CESS_PERCENTAGE;
    }

    protected function getRelevantPricingRule($pricing)
    {
        $entity = $this->entity;

        $feature = $entity->getEntity();

        $method = $entity->getMethod();

        $filters = array(
            [Pricing\Entity::FEATURE, $feature, false, null  ],
            [Pricing\Entity::PAYMENT_METHOD,  $method,  false, null  ],
        );

        $rules = $this->applyFiltersOnRules($pricing, $filters);

        $this->trace->debug(
            TraceCode::PRICING_RULE_SELECTION,
            ['count' => count($rules)]);

        $this->traceAllRules($rules);

        if ($feature === Pricing\Feature::PAYMENT)
        {
            $rule = $this->getRelevantPaymentPricingRule($rules, $method);
        }

        if ($rule === null)
        {
            throw new Exception\LogicException(
                'No appropriate pricing rule found', null, ['entity' => $entity->toArray()]);
        }

        return $rule;
    }

    protected function getRelevantPaymentPricingRule($rules, $method)
    {
        $rule = null;

        if ($method === Payment\Method::CARD)
        {
            $rule = $this->getRelevantPricingRuleForCardPayment($rules);
        }
        else if ($method === Payment\Method::WALLET)
        {
            $rule = $this->getRelevantPricingRuleForWalletPayment($rules);
        }
        else if ($method === Payment\Method::NETBANKING)
        {
            $rule = $this->getRelevantPricingRuleForNBPayment($rules);
        }
        else if ($method === Payment\Method::UPI)
        {
            $rule = $this->getRelevantPricingRuleForUPI($rules);
        }
        else
        {
            $rule = $this->getRelevantPricingRuleForMethod($rules);
        }

        return $rule;
    }

    protected function getRelevantPricingRuleForMethod($rules)
    {
        return $this->validateAndGetOnePricingRule($rules);
    }

    protected function getRelevantPricingRuleForUPI($rules)
    {
        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function applyAmountRangeFilterAndReturnOneRule($rules)
    {
        $payment = $this->entity;

        $amount = $payment->getAmount();

        $filters = [
            [Pricing\Entity::AMOUNT_RANGE_ACTIVE, true, true, false]
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        $subventionType = $payment->merchant->getSubventionType();

        $rule = $this->chooseRuleWithAmount($rules, $amount, $subventionType);

        if ($rule === null)
        {
            throw new Exception\LogicException(
                'Failed to find a valid pricing rule for the payment. ' .
                'Payment id: ' . $payment->getId());
        }

        return $rule;
    }

    protected function getRelevantPricingRuleForNBPayment($rules)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.

        $payment = $this->entity;

        $bank = $payment->getBank();

        // Current Implementation
        // * Filter based on AmountRange
        // * Choose based on Amount

        $filters = [
            [Pricing\Entity::PAYMENT_NETWORK, $bank, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForWalletPayment($rules)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.

        $payment = $this->entity;

        $wallet = $payment->getWallet();

        // Current Implementation
        // * Filter based on wallet

        // Structure is as follows:
        // Field name, Field value, Choose default (true/false), default value
        $filter = array(
            [Pricing\Entity::PAYMENT_NETWORK, $wallet, true, null]
        );

        $rules = $this->applyFiltersOnRules($rules, $filter);

        return $this->validateAndGetOnePricingRule($rules);
    }

    protected function getRelevantPricingRuleForCardPayment($rules)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.

        // Fee based on the method type
        $payment = $this->entity;

        $cardType = $payment->card->getTypeElseDefault();

        $international = $payment->isInternational();

        $network = Card\Network::getCode($payment->card->getNetwork());

        // Current Implementation
        // * Filter based on international
        // * Filter based on Network
        // * If its amex, then stop
        // * Filter based on Card Type
        // * Filter based on AmountRange
        // * Choose based on Amount

        // Structure is as follows:
        // Field name, Field value, Choose default (true/false), default value
        $filters1 = array(
            [Pricing\Entity::INTERNATIONAL,         $international, false,  false   ],
            [Pricing\Entity::PAYMENT_NETWORK,       $network,       true,   null    ],
        );



        $rules = $this->applyFiltersOnRules($rules, $filters1);

        if ($network === Card\Network::AMEX)
        {
            return $this->validateAndGetOnePricingRule($rules);
        }

        // If network is not amex, we can check for AMOUNT RANGE FILTERS

        $filters2 = array(
            [Pricing\Entity::PAYMENT_METHOD_TYPE,   $cardType,      true,   null    ],
        );

        $rules = $this->applyFiltersOnRules($rules, $filters2);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function applyFiltersOnRules($rules, $filters)
    {
        foreach ($filters as $filter)
        {
            $rules = $this->filterRulesOnFieldByValue(
                $rules, $filter[0], $filter[1], $filter[2], $filter[3]);

            $this->trace->debug(
                TraceCode::PAYMENT_PRICING_RULE_SELECTION,
                ['filter' => $filter, 'count' => count($rules)]);

            $this->traceAllRules($rules);
        }

        return $rules;
    }

    /**
     * Filter pricing rules based on fieldName and fieldValue
     * If the value is not found, and a default value is allowed,
     * matches based on default value will be returned.
     *
     * @param  $rules       List of rules
     * @param  $filedName   Field name to be filtered on
     * @param  $filedValue  Filed value to be filtered on
     * @param  $chooseDefault Default value to be considered if field value not found
     * @param  $defaultValue  Default value to be filtered on if $chooseDefualt is true
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
     */
    protected function chooseRuleWithAmount($rules, $amount, $subventionType)
    {
        return $this->chooseRuleWithAmountForMerchantSubvention($rules, $amount);
    }

    /**
     * If the rules are amount range active rules,
     * choose rule based on amount
     * else return first available rule.
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
            // Should not reach this case, ever.
            throw new Exception\RuntimeException(
                'Should not have reached here');
        }

        return $relevantRule;
    }

    /**
     * NOT USED CURRENTLY
     *
     * In customer subvention,
     * If the rule before applying the amount
     * and the new amount after using merchant
     * subvention is same then use the given rule
     */
    protected function chooseRuleWithAmountForCustomerSubvention($rules, $amount, $subventionType)
    {
        $fees = [];

        foreach ($rules as $rule)
        {
            list($fee, $st) = $this->getFees($rule, $amount);

            $newAmount = $amount + $fee;

            $newSubventionType = Merchant\FeeBearer::PLATFORM;

            $newRule = $this->chooseRuleWithAmount($rules, $newAmount, $newSubventionType);

            if ($rule === $newRule)
            {
                return $rule;
            }
        }
    }

    protected function validateAndGetOnePricingRule($pricing)
    {
        $this->traceAllRules($pricing);

        if (count($pricing) > 1)
        {
            $this->traceAllRules($pricing);

            throw new Exception\LogicException(
                'Only 1 pricing rule should have been present here. Found: ' . count($pricing));
        }

        $rule = $pricing[0];

        return $rule;
    }

    /**
     * Irrespective of preCalculationOfFees, Use the percent of original amount
     * to calculate razorpay fees. Service tax is not included here.
     *
     * @param int $amount                Amount in paise
     * @param int $percent               e.g 2% is 200
     * @param int $fixed
     * @param float $serviceTaxPercentage  15.0
     * @param boolean $preCalculationOfFees
     * @return fees
     */
    protected function getUnroundedFees($amount, $percent, $fixed, $serviceTaxPercentage, $preCalculationOfFees = false)
    {
        return $this->getRzpFeesUsingPercentOfOriginalAmount($amount, $percent, $fixed, $serviceTaxPercentage);
    }

    /**
     * This formula is only to be used if support is required for the following
     * formula.
     *
     * amount + rzpFees + serviceTax = totalAmount
     *                       rzpFees = percent * totalAmount + fixed
     *                    serviceTax = serviceTaxPercentage * rzpFees
     */
    protected function getRzpFeesUsingPercentOfTotalAmount($amount, $percent, $fixed, $serviceTaxPercentage)
    {
        $numerator =   (100 * ( $fixed * 100 + ($percent * $amount) / 100 ));

        $denominator = (10000 - ($percent) - ($percent * $serviceTaxPercentage / 100));

        return $numerator / $denominator;
    }

    /**
     *
     * Uses the following formula for fees calculation
     *
     * rzpFees = percent * amount + fixed
     */
    protected function getRzpFeesUsingPercentOfOriginalAmount($amount, $percent, $fixed, $serviceTaxPercentage)
    {
        $percentageAmount = (int) ceil(($amount * $percent)/10000);
        $totalAmount = $percentageAmount + $fixed;

        $feesSplit = array();

        if (empty($percent) === false)
        {
            $rzpPercentageFeeBreakup = $this->createFeeBreakup(FeeBreakName::RZP, $percent, $percentageAmount, FeeBreakType::PERCENTAGE);

            array_push($feesSplit, $rzpPercentageFeeBreakup);
        }

        if (empty($fixed) === false)
        {
            $rzpFixedFeeBreakup = $this->createFeeBreakup(FeeBreakName::RZP, 0, $fixed, FeeBreakType::FIXED);

            array_push($feesSplit, $rzpFixedFeeBreakup);
        }

        return array($totalAmount, $feesSplit);
    }

    protected function traceAllRules($rules)
    {
        $array = [];

        foreach ($rules as $rule)
        {
            $array[] = $rule->toArray();
        }

        $this->trace->debug(
            TraceCode::PAYMENT_PRICING_RULE_SELECTION,
            ['rules' => $array]);
    }

    protected function createFeeBreakup($name, $percent, $amount, $type)
    {
        $params = [
            FeeBreakup\Entity::NAME         => $name,
            FeeBreakup\Entity::PERCENTAGE   => $percent,
            FeeBreakup\Entity::AMOUNT       => $amount,
            FeeBreakup\Entity::TYPE         => $type,
        ];

        $feeBreakup = (new Pricing\FeeBreakup\Entity)->build($params);

        return $feeBreakup;
    }

    protected function calculateServiceTaxes($fee, $feesSplit)
    {
        $serviceTaxValue = (int) ceil(($fee * self::SERVICE_TAX_PERCENTAGE)/10000);
        $serviceTaxFeeBreakup = $this->createFeeBreakup(FeeBreakName::SERVICE_TAX, self::SERVICE_TAX_PERCENTAGE, $serviceTaxValue, FeeBreakType::PERCENTAGE);

        $krishiKalyanCessValue = (int) ceil(($fee * self::KRISHI_KALYAN_CESS_PERCENTAGE)/10000);
        $krishiKalyanCessFeeBreakup = $this->createFeeBreakup(FeeBreakName::KRISHI_KALYAN_CESS, self::KRISHI_KALYAN_CESS_PERCENTAGE, $krishiKalyanCessValue, FeeBreakType::PERCENTAGE);

        $swachhBharatCessValue = (int) ceil(($fee * self::SWACHH_BHARAT_CESS_PERCENTAGE)/10000);
        $swachhBharatCessFeeBreakup = $this->createFeeBreakup(FeeBreakName::SWACHH_BHARAT_CESS, self::SWACHH_BHARAT_CESS_PERCENTAGE, $swachhBharatCessValue, FeeBreakType::PERCENTAGE);

        array_push($feesSplit, $serviceTaxFeeBreakup, $krishiKalyanCessFeeBreakup, $swachhBharatCessFeeBreakup);

        $totaltaxes = $serviceTaxValue + $krishiKalyanCessValue + $swachhBharatCessValue;

        return array($totaltaxes, $feesSplit);
    }

    public function calculateServiceTaxesFromFees($fee)
    {
        $serviceTaxValue = $this->calculateTaxFromFees($fee, self::SERVICE_TAX_PERCENTAGE);
        $serviceTaxFeeBreakup = $this->createFeeBreakup(FeeBreakName::SERVICE_TAX, self::SERVICE_TAX_PERCENTAGE, $serviceTaxValue, FeeBreakType::PERCENTAGE);

        $krishiKalyanCessValue = $this->calculateTaxFromFees($fee, self::KRISHI_KALYAN_CESS_PERCENTAGE);
        $krishiKalyanCessFeeBreakup = $this->createFeeBreakup(FeeBreakName::KRISHI_KALYAN_CESS, self::KRISHI_KALYAN_CESS_PERCENTAGE, $krishiKalyanCessValue, FeeBreakType::PERCENTAGE);

        $swachhBharatCessValue = $this->calculateTaxFromFees($fee, self::SWACHH_BHARAT_CESS_PERCENTAGE);
        $swachhBharatCessFeeBreakup = $this->createFeeBreakup(FeeBreakName::SWACHH_BHARAT_CESS, self::SWACHH_BHARAT_CESS_PERCENTAGE, $swachhBharatCessValue, FeeBreakType::PERCENTAGE);

        $feesSplit = array($serviceTaxFeeBreakup, $krishiKalyanCessFeeBreakup, $swachhBharatCessFeeBreakup);

        $totaltaxes = $serviceTaxValue + $krishiKalyanCessValue + $swachhBharatCessValue;

        return array($totaltaxes, $feesSplit);
    }

    public function calculateTaxFromFees($fee, $taxPercentage)
    {
        // Solving these
        // rzpFee + servTax = totFee;
        // servTax = ST_PERC * rzpFee;
        //         = ST_PERC * (totFee - servTax);

        // servTax = ( ST_PERC * totFee ) / ( 10000 + ST_PERC ) ;

        //10000 as percentage is 1400 instead of 14

        $numerator = $fee * $taxPercentage;

        $denominator = 10000 + $taxPercentage ;

        return ceil($numerator / $denominator);
    }
}
