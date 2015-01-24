<?php

namespace Models\Pricing;

use EE\Exception;
use Models\Payment;
use Models\Pricing;

class Fee
{
    const SERVICE_TAX_PERCENT = 12;

    const EDUCATION_CESS_PERCENT = 0.36;

    protected $defaultPricingPlan = '1hDYlICobzOCYt';

    public function calculateMerchantFees($payment)
    {
        $pricingPlanId = $this->getPricingPlanId($payment->merchant);

        $rule = $this->getRelevantPricingRule($pricingPlanId, $payment);

        $fee = $this->getFees($rule, $payment->getAmount());

        return array($fee, $rule->getKey());
    }

    protected function getFees($rule, $amount)
    {
        $percent = $rule->getAttribute(Pricing\Entity::PERCENT_RATE);
        $fixed = $rule->getAttribute(Pricing\Entity::FIXED_RATE);

        $fee = (($amount * $percent) / 10000) + $fixed;
        $fee = (int) ceil($fee);

        $serviceTax = (int) ceil(($fee * self::SERVICE_TAX_PERCENT) / 100);
        $educationCess = (int) ceil(($fee * self::EDUCATION_CESS_PERCENT) / 100);

        $fee += ($serviceTax + $educationCess);

        return $fee;
    }

    protected function getPricingPlanId($merchant)
    {
        $pricingPlanId = $merchant->getPricingPlanId();

        $mode = \BasicAuth::getMode();

        if ($pricingPlanId === null)
        {
            if ($mode === 'live')
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
        else
        {
            $pricing = $pricingRepo->getPricingRulesForNetBanking($pricingPlanId);

            if (count($pricing) > 1)
            {
                throw new Exception\LogicException(
                    'Currently only 1 net-banking pricing rule allowed. Found: ' . count($rules));
            }

            $rule = $pricing->first();
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
}
