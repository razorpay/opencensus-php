<?php

namespace RZP\Models\Pricing;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Models\Pricing\FeeBreakup\Name as FeeBreakupName;
use RZP\Models\Merchant;
use RZP\Models\Base;
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

    public function calculate($pricing, $feesSplit, $preCalculationOfFees = false)
    {
        $entity = $this->entity;

        $rule = $this->getRelevantPricingRule($pricing);

        list($fee, $serviceTax) = $this->getFees($rule, $entity->getAmount(), $feesSplit, $preCalculationOfFees);

        return array($fee, $serviceTax, $rule->getKey());
    }


    protected function getFees($rule, $amount, $feesSplit, $preCalculationOfFees = false)
    {
        list($percent, $fixed) = $rule->getRates();

        $fee = $this->getUnroundedFees($amount, $percent, $fixed, $feesSplit, $rule->getKey(), $preCalculationOfFees);

        $fee = (int) ceil($fee);

        $totaltaxes = $this->calculateServiceTaxes($fee, $feesSplit);

        $totalFees = $fee + $totaltaxes;

        if ($totalFees > $amount)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT,
                Payment\Entity::AMOUNT);
        }

        return  array($totalFees, $totaltaxes);
    }

    public static function getServiceTaxRate()
    {
        return self::SERVICE_TAX_PERCENTAGE +
                self::KRISHI_KALYAN_CESS_PERCENTAGE +
                self::SWACHH_BHARAT_CESS_PERCENTAGE;
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
        else if ($method === Payment\Method::EMI)
        {
            $rule = $this->getRelevantPricingRuleForEmi($rules);
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

    protected function getRelevantPricingRuleForEmi($rules)
    {
        $payment = $this->entity;

        $network = Card\Network::getCode($payment->card->getNetwork());

        $filters1 = array(
            [Pricing\Entity::PAYMENT_NETWORK,       $network,       true,   null    ],
        );

        $rules = $this->applyFiltersOnRules($rules, $filters1);

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
    public function getUnroundedFees($amount, $percent, $fixed, $feesSplit, $pricingRuleId, $preCalculationOfFees = false)
    {
        return $this->getRzpFeesUsingPercentOfOriginalAmount($amount, $percent, $fixed, $feesSplit, $pricingRuleId);
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
    protected function getRzpFeesUsingPercentOfOriginalAmount($amount, $percent, $fixed, $feesSplit, $pricingRuleId)
    {
        $percentageAmount = (int) ceil(($amount * $percent)/10000);
        $totalAmount = $percentageAmount + $fixed;

        $rzpFee = $this->createFeeBreakup(
                                        FeeBreakupName::RZP,
                                        null,
                                        $totalAmount,
                                        $pricingRuleId);

        $feesSplit->push($rzpFee);

        return $totalAmount;
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

    protected function createFeeBreakup($name, $percent, $amount, $pricingRuleId = null)
    {
        $params = [
            FeeBreakup\Entity::NAME                 => $name,
            FeeBreakup\Entity::PERCENTAGE           => $percent,
            FeeBreakup\Entity::AMOUNT               => $amount,
            FeeBreakup\Entity::PRICING_RULE_ID      => $pricingRuleId,
        ];

        $feeBreakup = (new Pricing\FeeBreakup\Entity)->build($params);

        return $feeBreakup;
    }

    public function calculateServiceTaxes(
            $fee,
            $feesSplit,
            $serviceTaxPercentage = self::SERVICE_TAX_PERCENTAGE,
            $swachhBharatCessPercentage = self::SWACHH_BHARAT_CESS_PERCENTAGE,
            $krishiKalyanCessPercentage = self::KRISHI_KALYAN_CESS_PERCENTAGE)

    {
        $serviceTaxValue = (int) ceil(($fee * $serviceTaxPercentage)/10000);

        $serviceTaxFeeBreakup = $this->createFeeBreakup(
                                        FeeBreakupName::SERVICE_TAX,
                                        $serviceTaxPercentage,
                                        $serviceTaxValue);

        $krishiKalyanCessValue = (int) ceil(($fee * $krishiKalyanCessPercentage)/10000);

        $krishiKalyanCessFeeBreakup = $this->createFeeBreakup(
                                            FeeBreakupName::KRISHI_KALYAN_CESS,
                                            $krishiKalyanCessPercentage,
                                            $krishiKalyanCessValue);

        $swachhBharatCessValue = (int) ceil(($fee * $swachhBharatCessPercentage)/10000);

        $swachhBharatCessFeeBreakup = $this->createFeeBreakup(
                                            FeeBreakupName::SWACHH_BHARAT_CESS,
                                            $swachhBharatCessPercentage,
                                            $swachhBharatCessValue);

        $feesSplit->push($serviceTaxFeeBreakup);
        $feesSplit->push($krishiKalyanCessFeeBreakup);
        $feesSplit->push($swachhBharatCessFeeBreakup);

        $totaltaxes = $serviceTaxValue + $krishiKalyanCessValue + $swachhBharatCessValue;

        return $totaltaxes;
    }

    public function calculateServiceTaxesFromFees(
            $fee,
            $feesSplit,
            $serviceTaxPercentage = self::SERVICE_TAX_PERCENTAGE,
            $swachhBharatCessPercentage = self::SWACHH_BHARAT_CESS_PERCENTAGE,
            $krishiKalyanCessPercentage = self::KRISHI_KALYAN_CESS_PERCENTAGE)
    {
        $serviceTaxValue = $this->calculateTaxFromFees($fee, $serviceTaxPercentage);

        $serviceTaxFeeBreakup = $this->createFeeBreakup(
                                        FeeBreakupName::SERVICE_TAX,
                                        $serviceTaxPercentage,
                                        $serviceTaxValue);

        $krishiKalyanCessValue = $this->calculateTaxFromFees($fee, $krishiKalyanCessPercentage);

        $krishiKalyanCessFeeBreakup = $this->createFeeBreakup(
                                            FeeBreakupName::KRISHI_KALYAN_CESS,
                                            $krishiKalyanCessPercentage,
                                            $krishiKalyanCessValue);

        $swachhBharatCessValue = $this->calculateTaxFromFees($fee, $swachhBharatCessPercentage);

        $swachhBharatCessFeeBreakup = $this->createFeeBreakup(
                                            FeeBreakupName::SWACHH_BHARAT_CESS,
                                            $swachhBharatCessPercentage,
                                            $swachhBharatCessValue);

        $feesSplit->push($serviceTaxFeeBreakup);
        $feesSplit->push($krishiKalyanCessFeeBreakup);
        $feesSplit->push($swachhBharatCessFeeBreakup);

        $totaltaxes = $serviceTaxValue + $krishiKalyanCessValue + $swachhBharatCessValue;

        return $totaltaxes;
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
