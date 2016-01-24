<?php

namespace Models\Pricing;

use Constants\Mode;
use EE\Exception;
use Models\Card;
use Models\Payment;
use Models\Pricing;

class Fee
{
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
        else if ($payment->isNetbanking())
        {
            $pricing = $this->repo->getPricingRulesForNetbanking($pricingPlanId);

            if (count($pricing) > 1)
            {
                throw new Exception\LogicException(
                    'Only 1 pricing rule should have been present here. Found: ' . count($pricing),
                    [$pricing->toArray()]);
            }

            $rule = $pricing->first();
        }
        else if ($payment->isWallet())
        {
            $pricing = $this->repo->getPricingRulesForWallet($pricingPlanId);

            if (count($pricing) > 1)
            {
                throw new Exception\LogicException(
                    'Currently only 1 net-banking pricing rule allowed. Found: ' . count($pricing));
            }

            $rule = $pricing->first();
        }
        else
        {
            throw new Exception\InvalidArgumentException('Argument - Method: ' . $payment->getMethod());
        }

        if ($rule === null)
        {
            throw new Exception\LogicException(
                'No appropriate pricing rule found', ['payment' => $payment->toArray()]);
        }

        return $rule;
    }

    protected function getRelevantPricingRuleForCard($pricingPlanId, $payment)
    {
        $card = $payment->card;

        $network = Card\Network::getCode($card->getNetwork());

        $isInternational = $payment->isInternational();

        $pricing = $this->repo->
            getPricingRulesForGivenCardNetwork($pricingPlanId, $network, $isInternational);

        $rule = null;
        $rules = $pricing->all();

        if (count($rules) === 1)
        {
            $rule = $pricing->first();
        }
        else if (count($rules) === 2)
        {
            foreach ($pricing->all() as $item)
            {
                if ($item->getAttribute(Pricing\Entity::PAYMENT_NETWORK) === $network)
                {
                    $rule = $item;
                    break;
                }
            }
        }
        else
        {
            throw new Exception\LogicException(
                'Failed to find a valid pricing rule for the payment');
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
