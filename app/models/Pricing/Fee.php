<?php

namespace Models\Pricing;

use Constants\Mode;
use EE\Exception;
use Models\Card;
use Models\Payment;
use Models\Pricing;
use Services\SlackPoster;
use Trace\Trace;
use Trace\TraceCode;

class Fee
{
    use SlackPoster;

    const SERVICE_TAX_PERCENT = 14.5;

    protected $defaultPricingPlan = '1hDYlICobzOCYt';

    public function __construct()
    {
        $this->repo = new Pricing\Repository;
    }

    /**
     *  Used in testing to mock
     *  pricing repository
     */
    public function setPricingRepo($repo)
    {
        $this->repo = $repo;
    }

    public function getZeroPricingPlanRule($payment)
    {
        $planId = Pricing\Entity::ZERO_PRICING;

        $method = $payment->getMethod();

        return $this->repo->getZeroPricingPlanRuleForMethod($method)->getId();
    }

    public function calculateMerchantFees($payment)
    {
        $pricingPlanId = $this->getPricingPlanId($payment->merchant);

        $rule = $this->getRelevantPricingRule($pricingPlanId, $payment);

        list($fee, $serviceTax) = $this->getFees($rule, $payment->getAmount(), self::SERVICE_TAX_PERCENT);

        return array($fee, $serviceTax, $rule->getKey());
    }

    public function calculateServiceTax($txn, $payment)
    {
        $rule = $this->repo->getPricingPlanRule($txn->getPricingRule());

        $txnAuthTime = $payment->getAuthorizeTimestamp();

        // Set the authorized_at time if not set
        if (is_null($txnAuthTime) === True)
        {
            $txnCreatedTime = $payment->getCreatedTimestamp();
            $txnCapturedTime = $payment->getCaptureTimestamp();

            assert(is_null($txnCreatedTime) === FALSE);
            assert(is_null($txnCapturedTime) === FALSE);

            $txnAuthTime = ($txnCreatedTime + 45);

            $payment->setAuthorizeTimestamp($txnAuthTime);
        }

        list($fee, $serviceTax) = $this->getFees($rule, $payment->getAmount(), 0);

        $serviceTax = $txn->getFee() - $fee;
        assert($serviceTax > 0);

        return $serviceTax;
    }

    protected function getFees($rule, $amount, $serviceTaxPercentage)
    {
        $percent = $rule->getAttribute(Pricing\Entity::PERCENT_RATE);
        $fixed = $rule->getAttribute(Pricing\Entity::FIXED_RATE);

        list($fee, $serviceTax) = $this->getFeesByPercentAndFixedRates(
                            $amount, $serviceTaxPercentage, $percent, $fixed);

        assert ($fee < $amount);

        return  array($fee, $serviceTax);
    }

    protected function getFeesByPercentAndFixedRates($amount, $serviceTaxPercentage, $percent, $fixed)
    {
        $fee = $this->getUnroundedFees($amount, $percent, $fixed);

        $fee = (int) ceil($fee);

        $serviceTax = (int) ceil(($fee * $serviceTaxPercentage) / 100);

        $fee += $serviceTax;

        return array($fee, $serviceTax);
    }

    protected function getFeesByPercentAndFixedRatesForAtom($amount, $percent, $fixed)
    {
        $fee = (float) $this->getUnroundedFees($amount, $percent, $fixed);

        $serviceTax = $fee * self::SERVICE_TAX_PERCENT / 100;

        $fee += $serviceTax;

        $fee = (int) round($fee);

        return $fee;
    }

    protected function getUnroundedFees($amount, $percent, $fixed)
    {
        return (($amount * $percent) / 10000) + $fixed;
    }

    protected function getPricingPlanId($merchant)
    {
        $pricingPlanId = $merchant->getPricingPlanId();

        $mode = \BasicAuth::getMode();

        if ($pricingPlanId === null)
        {
            if ($mode === Mode::LIVE)
            {
                throw new Exception\LogicException(
                    'No pricing plan assigned for merchant id: ' . $merchant->getKey());
            }

            $pricingPlanId = $this->defaultPricingPlan;
        }

        return $pricingPlanId;
    }

    protected function getRelevantPricingRule($pricingPlanId, $payment)
    {
        if ($payment->getMethod() === Payment\Method::CARD)
        {
            $rule = $this->getRelevantPricingRuleForCard($pricingPlanId, $payment);
        }
        else
        {
            $rule = $this->getRelevantPricingRuleForMethod($pricingPlanId, $payment);
        }

        if ($rule === null)
        {
            throw new Exception\LogicException(
                'No appropriate pricing rule found', ['payment' => $payment->toArray()]);
        }

        return $rule;
    }

    protected function getRelevantPricingRuleForMethod($pricingPlanId, $payment)
    {
        $method = $payment->getMethod();

        $pricing = $this->repo->getPricingRulesForMethod($pricingPlanId, $method);

        if (count($pricing) > 1)
        {
            throw new Exception\LogicException(
                'Only 1 pricing rule should have been present here. Found: ' . count($pricing),
                [$pricing->toArray()]);
        }

        $rule = $pricing->first();

        return $rule;
    }

    protected function getRelevantPricingRuleForCard($pricingPlanId, $payment)
    {
        // Fee based on the method type
        $cardType = $payment->card->getType();

        if ($cardType === Card\Type::UNKNOWN)
        {
            $slackArray = ['id' => $payment->card->getDashboardEntityLinkForSlack() ];

            $this->slackPost(
                'Unknown card type found',
                $slackArray,
                ['channel' => '#tech_logs']);

            $cardType = Card\Type::DEBIT;
        }

        $isInternational = $payment->isInternational();

        $network = Card\Network::getCode($payment->card->getNetwork());

        $pricing = $this->repo->getPricingRulesForCard(
                        $pricingPlanId, $isInternational, $network, $cardType);

        $amount = $payment->getAmount();

        $rule = $this->getRuleFromPricingCollection($pricing, $network, $cardType, $amount);

        return $rule;
    }

    protected function getRuleFromPricingCollection($pricing, $network, $cardType, $amount)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.

        $rules = $pricing->all();

        // Current Implementation
        // 1. Filter based on Network
        // 2. Filter based on Card Type
        // 3. Filter based on AmountRange
        // 4. Choose based on Amount

        $filters = array(
            Pricing\Entity::PAYMENT_NETWORK, $network, false,
            Pricing\Entity::PAYMENT_METHOD_TYPE, $cardType, false,
            Pricing\Entity::AMOUNT_RANGE_ACTIVE, true, true);

        $rules = $this->filterRulesOnFieldByValue($rules, Pricing\Entity::PAYMENT_NETWORK, $network);

        $rules = $this->filterRulesOnFieldByValue($rules, Pricing\Entity::PAYMENT_METHOD_TYPE, $cardType);

        $isFieldBoolean = true;

        $amountRangeActive = true;

        $rules = $this->filterRulesOnFieldByValue(
            $rules, Pricing\Entity::AMOUNT_RANGE_ACTIVE, $amountRangeActive, $isFieldBoolean);

        $rule = $this->chooseRuleWithAmount($rules, $amount);

        if ($rule === null)
        {
            throw new Exception\LogicException(
                'Failed to find a valid pricing rule for the payment');
        }

        return $rule;
    }

    /**
     * Filter pricing rules based on fieldName and fieldValue
     * If the value is not found, matches based on null
     * will be returned, unless the field is boolean
     * when matches based on boolean false will be returned.
     */
    protected function filterRulesOnFieldByValue($rules, $fieldName, $fieldValue, $isFieldBoolean = false)
    {
        $matchRules     = [];
        $nullMatchRules = [];

        $counterValue = null;

        if ($isFieldBoolean)
        {
            $counterValue = !$fieldValue;
        }

        foreach ($rules as $rule)
        {
            if ($rule->getAttribute($fieldName) === $fieldValue)
            {
                $matchRules[] = $rule;
            }
            else if($rule->getAttribute($fieldName) === $counterValue)
            {
                $nullMatchRules[] = $rule;
            }
        }

        if (empty($matchRules))
        {
            return $nullMatchRules;
        }

        return $matchRules;
    }

    /**
     * If the rules are amount range active rules,
     * choose rule based on amount
     * else return first available rule.
     */
    protected function chooseRuleWithAmount($rules, $amount)
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
        else if(count($rules) === 1)
        {
            return $rules[0];
        }
        // Ideally should not reach this case, ever.
        else
        {
            $this->trace->info(
                TraceCode::PAYMENT_PRICING_RULE_NOT_FOUND,
                ['amount' => $amount, 'plan_id' => $rules[0]->getPlanId()]);

            return $rules[0];
        }

        return $relevantRule;
    }

    public function getGatewayFeeForAtomSharedTerminal($payment)
    {
        $amount = $payment->getAmount();
        $method = $payment->getMethod();

        $percent = 0;

        if ($method === Payment\Method::NETBANKING)
        {
            $percent = 175;
        }
        else if ($method === Payment\Method::CARD)
        {
            $card = $payment->card;
            $type = $card->getType();

            $type = Card\Type::DEBIT;

            if ($type === Card\Type::CREDIT)
            {
                $percent = 200;
            }
            else if ($type === Card\Type::DEBIT)
            {
                // Percent changes at Rs 2000
                if ($amount <= 200000)
                {
                    $percent = 85;
                }
                else
                {
                    $percent = 110;
                }
            }
        }

        if ($percent === 0)
        {
            throw new Exception\LogicException('Percent should not be 0');
        }

        $gatewayFee = $this->getFeesByPercentAndFixedRatesForAtom($amount, $percent, 0);

        return $gatewayFee;
    }
}
