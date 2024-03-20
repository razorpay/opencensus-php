<?php

namespace RZP\Models\Pricing;

use RZP\Constants\Country;
use RZP\Models\BankAccount;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Purpose;
use RZP\Models\Payout\Constants as PayoutConstants;
use RZP\Models\Base\PublicEntity;

class PayoutFee extends Fee
{
    // This Plan ID is synced with Prod. It is used to assign a custom pricing to fee_recovery payouts.
    const ZERO_PRICING_PLAN_ID_RBL   = 'EDoLfqMMBHVYGR';

    public function getCustomPricingPlan(PublicEntity $payout)
    {
        if ($payout->getPurpose() === Purpose::RZP_FEES)
        {
            return self::ZERO_PRICING_PLAN_ID_RBL;
        }

        return null;
    }

    /**
     * Recalculation because of the higher tax being deducted because even splitting of CGST and SGST
     */
    public static function recalculateTaxForPayout(int $fee, int $tax, $feesSplit, $countryCode = Country::IN)
    {
        if (in_array($countryCode, BankAccount\Entity::$IfscAllowedCountries) === false)
        {
            return [$fee, $tax];
        }
        $feeWithoutTax = $fee - $tax;

        $tax = (int) round($feeWithoutTax * (PayoutConstants::PAYOUT_FEE_TAX_PERCENTAGE / 100));

        foreach ($feesSplit as $split)
        {
            if ($split->name === PayoutConstants::TAX)
            {
                $split->amount = $tax;
                $split->percentage = PayoutConstants::PAYOUT_FEE_TAX_PERCENTAGE;
            }
        }

        return [$feeWithoutTax + $tax, $tax];
    }


    /**
     * @param Entity $entity
     */
    public function calculateMerchantFees($entity): array
    {
        list($totalFee, $totalTax, $feeSplit) = parent::calculateMerchantFees($entity);
        $merchantCountry = Country::IN;

        if (isset($this->merchant))
        {
            $merchantCountry = $this->merchant->getCountry();
        }

        list($totalFee, $totalTax) = $this->recalculateTaxForPayout($totalFee, $totalTax, $feeSplit, strtolower($merchantCountry));

        return [$totalFee, $totalTax, $feeSplit];
    }

}
