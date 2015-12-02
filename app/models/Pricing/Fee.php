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
    const SERVICE_TAX_PERCENT_BEFORE_15NOV = 14.0;
    const SERVICE_TAX_PERCENT_BEFORE_1JUNE = 12.36;
    const TIMESTAMP_1JUNE = 1433136600;
    const TIMESTAMP_16NOV = 1447651800;

    protected $defaultPricingPlan = '1hDYlICobzOCYt';

    public function getZeroPricingPlanRule($payment)
    {
        $planId = Pricing\Entity::ZERO_PRICING;

        $method = $payment->getMethod();

        return (new Pricing\Repository)->getZeroPricingPlanRuleForMethod($method)->getId();
    }

    public function calculateMerchantFees($payment)
    {
        $pricingPlanId = $this->getPricingPlanId($payment->merchant);

        $rule = $this->getRelevantPricingRule($pricingPlanId, $payment);

        $serviceTaxPercentage = $this->getServiceTaxPercentage($payment->getAuthorizeTimestamp());
        list($fee, $serviceTax) = $this->getFees($rule, $payment->getAmount(), $serviceTaxPercentage);

        return array($fee, $serviceTax, $rule->getKey());
    }

    public function calculateServiceTax($txn, $payment)
    {
        $pricingRepo = new Pricing\Repository;
        $rule = $pricingRepo->getPricingPlanRule($txn->getPricingRule());

        $serviceTaxPercentage = $this->getServiceTaxPercentage($payment->getCaptureTimestamp());

        list($fee, $serviceTax) = $this->getFees($rule, $payment->getAmount(), $serviceTaxPercentage);

        assert($fee === $txn->getFee());

        return $serviceTax;   
    }

    protected function getServiceTaxPercentage($timestamp)
    {
        $serviceTaxPercentage = self::SERVICE_TAX_PERCENT;

        if ($timestamp < self::TIMESTAMP_1JUNE)
        {
            $serviceTaxPercentage = self::SERVICE_TAX_PERCENT_BEFORE_1JUNE;
        }
        else if ($timestamp < self::TIMESTAMP_16NOV)
        {
            //service tax rates changed after 15th but service was updated on 16th
            $serviceTaxPercentage = self::SERVICE_TAX_PERCENT_BEFORE_15NOV;
        }

        return $serviceTaxPercentage;
    }

    protected function getFees($rule, $amount, $serviceTaxPercentage)
    {
        $percent = $rule->getAttribute(Pricing\Entity::PERCENT_RATE);
        $fixed = $rule->getAttribute(Pricing\Entity::FIXED_RATE);

        list($fee, $serviceTax) = $this->getFeesByPercentAndFixedRates($amount, $serviceTaxPercentage, $percent, $fixed);

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
        $pricingRepo = new Pricing\Repository;

        if ($payment->getMethod() === Payment\Method::CARD)
        {
            $rule = $this->getRelevantPricingRuleForCard($pricingPlanId, $payment);
        }
        else if ($payment->isNetbanking())
        {
            $pricing = $pricingRepo->getPricingRulesForNetbanking($pricingPlanId);

            if (count($pricing) > 1)
            {
                throw new Exception\LogicException(
                    'Currently only 1 net-banking pricing rule allowed. Found: ' . count($pricing));
            }

            $rule = $pricing->first();
        }
        else if ($payment->isWallet())
        {
            $pricing = $pricingRepo->getPricingRulesForWallet($pricingPlanId);

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

        $network = $card->getNetwork();

        $pricingRepo = new Pricing\Repository;

        $pricing = $pricingRepo->getPricingRulesForGivenCardNetwork($pricingPlanId, $network);

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
                if ($item->getAttribute(Pricing\Entity::PAYMENT_NETWORK) === $card->getNetwork())
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
