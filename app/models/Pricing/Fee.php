<?php

namespace Models\Pricing;

use EE\Exception;
use Models\Pricing;

class Fee
{
    const SERVICE_TAX_PERCENT = 12;

    const EDUCATION_CESS_PERCENT = 0.36;

    protected $defaultPricingPlan = '13906d42c88a41ee4e2d812e';

    public function calculateMerchantFees($merchant, $card, $amount)
    {
        $networks = array($card->getNetwork(), null);

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

        $pricingRepo = new Pricing\Repository;

        $pricing = $pricingRepo->getPricingPlanByIdAndPaymentNetworks($pricingPlanId, $networks);

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
                'Failed to find a valid pricing rule for the transaction');
        }

        $percent = $rule->getAttribute(Pricing\Entity::PERCENT_RATE);
        $fixed = $rule->getAttribute(Pricing\Entity::FIXED_RATE);

        $fee = (($amount * $percent) / 10000) + $fixed;
        $fee = (int) ceil($fee);

        $serviceTax = (int) ceil(($fee * self::SERVICE_TAX_PERCENT) / 100);
        $educationCess = (int) ceil(($fee * self::EDUCATION_CESS_PERCENT) / 100);

        $fee += $serviceTax + $educationCess;

        return array($fee, $rule->getKey());
    }
}
