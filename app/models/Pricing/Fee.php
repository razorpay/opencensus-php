<?php

namespace Models\Pricing;

use Constants\Mode;
use EE\Exception;
use Models\Card;
use Models\Payment;
use Models\Pricing;
use Services\SlackPoster;

class Fee
{
    use SlackPoster;

    const SERVICE_TAX_PERCENT = 14.5;

    protected $defaultPricingPlan = '1hDYlICobzOCYt';

    public function __construct()
    {
        $this->repo = new Pricing\Repository;
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

            $cardType = Card\Type::CREDIT;
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
        $rules = $pricing->all();

        $rules = $this->filterRulesOnNetwork($rules, $network);

        $rulesMap = $this->filterRulesOnCardType($rules, $cardType);

        $rule = $this->chooseRuleWithAmount($rulesMap, $amount);

        if ($rule === null)
        {
            throw new Exception\LogicException(
                'Failed to find a valid pricing rule for the payment');
        }

        return $rule;
    }

    protected function filterRulesOnNetwork($rules, $network)
    {
        $networkMatchRules     = [];
        $nullnetworkMatchRules = [];

        foreach ($rules as $item)
        {
            if ($item->getAttribute(Pricing\Entity::PAYMENT_NETWORK) === $network)
            {
                $networkMatchRules[] = $item;
            }
            else
            {
                $nullnetworkMatchRules[] = $item;
            }
        }

        if (empty($networkMatchRules))
        {
            return $nullnetworkMatchRules;
        }

        return $networkMatchRules;
    }

    protected function filterRulesOnCardType($rules, $cardType)
    {
        $feeTypeAmountRules    = [];
        $nullTypeAmountRules   = [];
        $feeTypeNonAmountRule  = [];
        $nullTypeNonAmountRule = [];

        foreach ($rules as $item)
        {
            if (($item->getAttribute(Pricing\Entity::PAYMENT_METHOD_TYPE) === $cardType))
            {
                if ($item->getAttribute(Pricing\Entity::AMOUNT_RANGE_ACTIVE))
                {
                    $feeTypeAmountRules[] = $item;
                }
                else
                {
                    $feeTypeNonAmountRule = $item;
                }
            }
            else
            {
                if ($item->getAttribute(Pricing\Entity::AMOUNT_RANGE_ACTIVE))
                {
                    $nullTypeAmountRules[] = $item;
                }
                else
                {
                    $nullTypeNonAmountRule = $item;
                }
            }
        }

        return ['typeNonAmountRule' => $feeTypeNonAmountRule,
                'typeAmountRules' => $feeTypeAmountRules,
                'nullAmountRules' => $nullTypeAmountRules,
                'nullNonAmountRule' => $nullTypeNonAmountRule];
    }

    protected function chooseRuleWithAmount($rulesMap, $amount)
    {
        $rule = null;

        if (empty($rulesMap['typeAmountRules']) === false)
        {
            foreach ($rulesMap['typeAmountRules'] as $ruleItem)
            {
                if ((($amount === Payment\Entity::MIN_PAYMENT_AMOUNT) and
                    $ruleItem->getAttribute(Pricing\Entity::AMOUNT_RANGE_MIN) === $amount) or
                    (($ruleItem->getAttribute(Pricing\Entity::AMOUNT_RANGE_MIN) < $amount) and
                    ($ruleItem->getAttribute(Pricing\Entity::AMOUNT_RANGE_MAX) >= $amount)))
                {
                    $rule = $ruleItem;
                    break;
                }
            }
        }
        else if (empty($rulesMap['typeNonAmountRule']) === false)
        {
            $rule = $rulesMap['typeNonAmountRule'];
        }
        else if (empty($rulesMap['nullAmountRules']) === false)
        {
            foreach ($rulesMap['nullAmountRules'] as $ruleItem)
            {
                if ((($amount === Payment\Entity::MIN_PAYMENT_AMOUNT) and
                    $ruleItem->getAttribute(Pricing\Entity::AMOUNT_RANGE_MIN) === $amount) or
                    (($ruleItem->getAttribute(Pricing\Entity::AMOUNT_RANGE_MIN) < $amount) and
                    ($ruleItem->getAttribute(Pricing\Entity::AMOUNT_RANGE_MAX) >= $amount)))
                {
                    $rule = $ruleItem;
                    break;
                }
            }
        }
        else if (empty($rulesMap['nullNonAmountRule']) === false)
        {
            $rule = $rulesMap['nullNonAmountRule'];
        }

        return $rule;
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
