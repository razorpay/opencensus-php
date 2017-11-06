<?php

namespace RZP\Models\Pricing;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;

use Razorpay\Trace\Logger as Trace;

class FeeCalculator
{
    const IGST_PERCENTAGE   = 1800; // Integrated GST
    const CGST_PERCENTAGE   = 900;  // Central GST
    const SGST_PERCENTAGE   = 900;  // State GST

    // 1st July 2017 00:00:00 IST - Timestamp at which GST will begin to be levied on transactions
    const GST_START_TIMESTAMP = 1498847400;

    // '29' - Karnataka's state code
    const RZP_GST_STATE_CODE = '29';

    const CARD_TAX_CUT_OFF = 200000;

    /**
     * For which fees needs to be calculated.
     */
    protected $entity;

    protected $defaultPricingPlan = '1hDYlICobzOCYt';

    protected $feesSplit = null;

    protected $pricingRules = null;

    protected $amount = null;

    /**
     * @var Trace
     */
    protected $trace;

    protected $taxComponents = null;

    public function __construct($entity)
    {
        $this->entity = $entity;

        $this->feesSplit = new Base\PublicCollection;

        $this->pricingRules = new Base\PublicCollection;

        $this->trace = \Trace::getFacadeRoot();

        $this->taxComponents = self::getTaxComponents($this->entity->merchant);
    }

    public function calculate(Pricing\Plan $pricing): array
    {
        $entity = $this->entity;

        $amount = $entity->getBaseAmount();

        if ($entity->merchant->isFeeBearerCustomer())
        {
            // 1. The first call will have the fee = 0,
            //    hence fees will be calculated on the original amount
            // 2. On validation/capture call, the fee will be set
            $amount = $amount - $entity->getFee();
        }

        $this->amount = $amount;

        $this->getRelevantPricingRule($pricing);

        list($fee, $tax) = $this->getFees($amount);

        return [$fee, $tax, $this->feesSplit];
    }

    protected function getFees($amount)
    {
        $fees = 0;

        foreach ($this->pricingRules as $rule)
        {
            $fee = $this->calculateRzpFee($rule, $amount);

            $fees += $fee;
        }

        $totalTaxes = $this->calculateGst($fees);

        $totalFees = $fees + $totalTaxes;

        // In case the merchant is customer fee bearer, we shouldn't check $amount < $totalFees
        if ($this->entity->merchant->isFeeBearerCustomer() === false)
        {
            if ($totalFees > $amount)
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

        return [$totalFees, $totalTaxes];
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

    protected function getRelevantPricingRule(Pricing\Plan $pricing)
    {
        $entity = $this->entity;

        $entityName = $entity->getEntity();

        $features = $entity->getPricingFeatures();

        $this->getBasicPricingRule($pricing, $entityName);

        $this->getAddOnPricingRule($pricing, $features, $entityName);

        $this->traceAllRules($this->pricingRules);
    }

    protected function getAddOnPricingRule(Pricing\Plan $pricing, array $features, $entityName)
    {
        $method = $this->entity->getMethod();

        foreach ($features as $feature)
        {
            $filters = array(
                [Pricing\Entity::FEATURE, $feature, false, null  ],
                [Pricing\Entity::PAYMENT_METHOD,  $method,  false, null  ],
            );

            $rules = $this->applyFiltersOnRules($pricing, $filters);

            $this->trace->debug(
                TraceCode::PRICING_RULE_SELECTION,
                ['count' => count($rules)]);

            if ((count($rules) > 0) and
                $entityName === Pricing\Feature::PAYMENT)
            {
                $rule = $this->getRelevantPaymentPricingRule($rules, $method);

                $this->pricingRules->push($rule);
            }
        }
    }

    protected function getBasicPricingRule(Pricing\Plan $pricing, $feature)
    {
        $method = $this->entity->getMethod();

        $filters = array(
            [Pricing\Entity::FEATURE,         $feature, false, null  ],
            [Pricing\Entity::PAYMENT_METHOD,  $method,  false, null  ],
        );

        $rules = $this->applyFiltersOnRules($pricing, $filters);

        $this->traceAllRules($rules);

        $rulesCount = count($rules);

        $this->trace->debug(
            TraceCode::PRICING_RULE_SELECTION,
            ['count' => $rulesCount]);

        //
        // If pricing for the feature is optional, no rules may exist
        // In this case, we add the zero pricing rule and return
        //
        if (($rulesCount === 0) and
            (Feature::isFeaturePricingOptional($feature) === true))
        {
            $zeroPricingRule = (new Fee)->getZeroPricingPlanRule($this->entity);

            $this->pricingRules->push($zeroPricingRule);

            return;
        }

        //
        // `$feature` is among those defined in Pricing/Feature
        //
        $ruleFunction = 'getRelevant' . studly_case($feature) . 'PricingRule';

        $rule = null;

        if (method_exists($this, $ruleFunction) === true)
        {
            $rule = $this->$ruleFunction($rules, $method);
        }

        if ($rule === null)
        {
            throw new Exception\LogicException(
                'No appropriate pricing rule found for entity ' . $this->entity->getEntity(),
                ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT,
                ['entity' => $this->entity->toArray()]);
        }

        $this->pricingRules->push($rule);
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
        else if ($method === Payment\Method::AEPS)
        {
            $rule = $this->getRelevantPricingRuleForAeps($rules);
        }
        else if ($method === Payment\Method::EMI)
        {
            $rule = $this->getRelevantPricingRuleForEmi($rules);
        }
        // else if ($method === Payment\Method::TRANSFER)
        // {
        //     $rule = $this->getRelevantPricingRuleForTransfer($rules);
        // }
        else
        {
            $rule = $this->getRelevantPricingRuleForMethod($rules);
        }

        return $rule;
    }

    protected function getRelevantPayoutPricingRule($rules, $method)
    {
        $rule = $this->getRelevantPricingRuleForMethod($rules);

        return $rule;
    }

    protected function getRelevantTransferPricingRule($rules, $method)
    {
        //
        // Transfer pricing rules are optional -
        // However, if a rule exists, we validate that only one
        // rule is applied per transfer
        //
        $rule = $this->getRelevantPricingRuleForMethod($rules);

        return $rule;
    }

    protected function getRelevantPricingRuleForMethod($rules)
    {
        return $this->validateAndGetOnePricingRule($rules);
    }

    protected function getRelevantPricingRuleForAeps($rules)
    {
        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForUPI($rules)
    {
        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
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
                'Invalid rule count: 0, Payment Id: ' . $payment->getId(),
                ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT);
        }

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
        $emiPlan = $payment->emiPlan;

        $network = Card\Network::getCode($payment->card->getNetwork());

        $emiDuration = $emiPlan->getDuration();

        $issuer = $emiPlan->getIssuer();

        //Emi duration and issuer filter is for merchant subvented model
        //in normal emi it will be null where feature is payment
        $filters1 = array(
            [Pricing\Entity::PAYMENT_NETWORK, $network,     true, null ],
            [Pricing\Entity::PAYMENT_ISSUER,  $issuer,      true, null ],
            [Pricing\Entity::EMI_DURATION,    $emiDuration, true, null ]
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
     * @param $subventionType
     *
     * @return null
     */
    protected function chooseRuleWithAmount($rules, $amount, $subventionType)
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
        $this->traceAllRules($rules);

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

    /**
     * Irrespective of preCalculationOfFees, Use the percent of original amount
     * to calculate razorpay fees. Tax is not included here.
     *
     * @param int $amount                Amount in paise
     * @param int $percent               e.g 2% is 200
     * @param int $fixed
     * @return int
     */
    protected function getUnroundedFees($amount, $percent, $fixed)
    {
        return $this->getRzpFeesUsingPercentOfOriginalAmount($amount, $percent, $fixed);
    }

    /**
     *
     * Uses the following formula for fees calculation
     *
     * rzpFees = percent * amount + fixed
     */
    protected function getRzpFeesUsingPercentOfOriginalAmount($amount, $percent, $fixed)
    {
        return (($amount * $percent) / 10000) + $fixed;
    }

    protected function traceAllRules($rules)
    {
        // This is sending a lot of traces and so for
        // this tracing is not required.
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
            Transaction\FeeBreakup\Entity::NAME                 => $name,
            Transaction\FeeBreakup\Entity::PERCENTAGE           => $percent,
            Transaction\FeeBreakup\Entity::AMOUNT               => $amount,
            Transaction\FeeBreakup\Entity::PRICING_RULE_ID      => $pricingRuleId,
        ];

        $feeBreakup = (new Transaction\FeeBreakup\Entity)->build($params);

        return $feeBreakup;
    }

    public function calculateRzpFee(Pricing\Entity $rule, $amount)
    {
        list($percent, $fixed) = $rule->getRates();

        list($min, $max) = $rule->getMinMaxFees();

        $fee = $this->getUnroundedFees($amount, $percent, $fixed);

        $fee = (int) ceil($fee);

        // Fee is checked with bounds after being rounded up.
        // This ensures fee will always be within the bound.
        $fee = $this->compareBoundsAndGetFee($fee, $min, $max);

        $rzpFee = $this->createFeeBreakup(
                                $rule->getFeature(),
                                null,
                                $fee,
                                $rule->getId());

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

            $taxValue = ($eligibleForGst === true) ? $taxValue: 0;

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

            if ($payment->merchant->isFeeBearerCustomer() === true)
            {
                $amount = $amount + $fee;
            }

            // No tax is levied on card payments of 2000 Rs. or less
            if (($payment->isMethodCardOrEmi() === true) and
                ($amount <= self::CARD_TAX_CUT_OFF))
            {
                return false;
            }
        }

        return true;
    }

    protected static function getTaxComponents(Merchant\Entity $merchant): array
    {
        $merchantBusinessStateCode = $merchant->getBusinessStateCode();

        return self::getTaxComponentsFromStateCode($merchantBusinessStateCode);
    }

    public static function getTaxComponentsFromStateCode(string $merchantGstStateCode = null): array
    {
        // Intrastate gst
        if (($merchantGstStateCode === null) or ($merchantGstStateCode === self::RZP_GST_STATE_CODE))
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

    protected function calculateTaxFromFees($fee, $taxPercentage)
    {
        // Solving these
        // rzpFee + servTax = totFee;
        // servTax = ST_PERC * rzpFee;
        //         = ST_PERC * (totFee - servTax);

        // servTax = ( ST_PERC * totFee ) / ( 10000 + ST_PERC ) ;

        //10000 as percentage is 1400 instead of 14

        $numerator = $fee * $taxPercentage;

        $denominator = 10000 + $taxPercentage;

        return ceil($numerator / $denominator);
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
}
