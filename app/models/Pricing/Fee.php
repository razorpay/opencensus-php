<?php

namespace Models\Pricing;

use EE\Exception;

class Fee
{
    public function calculateMerchantFees($merchant, $card, $amount)
    {
        $merchantId = $merchant->getKey();
        $networks = array($card->getNetwork(), null);

        $pricingRepo = new Pricing\Repository;
        $pricing = $pricingRepo->getPricingPlanByMerchantAndPaymentNetworks(
                                    $merchantId,
                                    $networks);

        $rule = null;

        if (count($pricing) === 1)
        {
            $rule = $pricing->first();
        }
        else if (count($pricing) === 2)
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
            throw new Exception\LogicException('Failed to find a valid pricing rule for the transaction');
        }

        $percent = $rule->getAttribute(Pricing\Entity::PERCENT_RATE);
        $fixed = $rule->getAttribute(Pricing\Entity::FIXED_RATE);

        $fee = (($amount * $percent) / 100) + $fixed;

        return array($fee, $item->getKey());
    }

    public function calculateGatewayFees($lgr)
    {
        ;
    }
}
