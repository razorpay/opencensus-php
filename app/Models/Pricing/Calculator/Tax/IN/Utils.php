<?php

namespace RZP\Models\Pricing\Calculator\Tax\IN;

use App;

use RZP\Constants;
use RZP\Models\Pricing\Calculator\Tax\IN\Constants as INConstants;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;

class Utils
{
    public static function getTaxRate()
    {
        // returning igst percentage as igst = cgst + sgst
        return INConstants::IGST_PERCENTAGE;
    }

    public static function isGstApplicable($fromTimestamp)
    {
        return ($fromTimestamp >= INConstants::GST_START_TIMESTAMP);
    }

    public static function isEligibleForGst($fee, $entity, $amount): bool
    {
        if ($entity->getEntity() === Constants\Entity::PAYMENT)
        {
            $payment = $entity;

            if ($payment->isFeeBearerCustomer() === true)
            {
                $amount = $amount + $fee;
            }

            //Adding fee in Amount in case merchant is on Dynamic Fee Bearer and has split the fee
            // with customer
            if($payment->hasOrder() === true and
                $payment->order->getFeeConfigId() !== null )
            {
                $customerFee = (new Payment\Processor\Processor($payment->merchant))->calculateCustomerFee($payment, $payment->order, $fee);

                if ($customerFee !== null)
                {
                    $amount = $amount + $customerFee;
                }
            }
            // No tax is levied on card payments of 2000 Rs. or less

            // For the Bajaj finserv emi payments we have to skip this condition because tax
            //must be calculated whether amount is smaller, equal or greater than the 2000 for bajaj emi payments
            if ( !($payment->gateway === Constants\Entity::BAJAJFINSERV and $payment->isMethod(Payment\Entity::EMI)) and
                ($payment->isMethodCardOrEmi() === true) and
                ($amount <= INConstants::CARD_TAX_CUT_OFF))
            {
                return false;
            }
        }

        return true;
    }

    /*
        To Do :  need to move this to base class so that every country can inherit it and implement it
        on the basis of their tax criterio
    */

    public static function getTaxComponents(Merchant\Entity $merchant): array
    {
        $gstin = $merchant->getGstin();

        return self::getTaxComponentsWithGSTIN($gstin, $merchant);
    }

    public static function getTaxComponentsWithGSTIN(string $gstin = null, Merchant\Entity $merchant): array
    {
        $merchantGstStateCode = Merchant\Detail\Entity::getStateCodeFromGstin($gstin);

        $registeredBusinessStateCode = $merchant->getBusinessRegisteredState();

        // Intrastate => Within Karnataka
        $intraStateGstApplicable = true;

        if (empty($merchantGstStateCode) === false)
        {
            $intraStateGstApplicable = ($merchantGstStateCode === INConstants::RZP_GST_STATE_CODE);
        }
        else if (empty($registeredBusinessStateCode) === false)
        {
            $merchantStateCode = substr($registeredBusinessStateCode, 0, 2);

            $intraStateGstApplicable = (strtoupper($merchantStateCode) === INConstants::RZP_STATE);
        }

        if ($intraStateGstApplicable === true)
        {
            return [
                FeeBreakupName::CGST => INConstants::CGST_PERCENTAGE,
                FeeBreakupName::SGST => INConstants::SGST_PERCENTAGE,
            ];
        }

        return [
            FeeBreakupName::IGST => INConstants::IGST_PERCENTAGE,
        ];
    }
}
