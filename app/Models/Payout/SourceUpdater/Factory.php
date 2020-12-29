<?php

namespace RZP\Models\Payout\SourceUpdater;

use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\PayoutSource\Validator as PayoutSourceValidator;

class Factory
{
    /*
     * its possible that the same payout may have to update multiple sources
     * this method will send the list of classes to be called
     */
    public static function getUpdaters(PayoutEntity $payout, string $mode): array
    {
        $subscriberList = [];

        $sourceDetails = $payout->getSourceDetails();

        foreach ($sourceDetails as $source)
        {
            switch ($source->getSourceType())
            {
                case PayoutSourceValidator::VENDOR_PAYMENTS:

                case PayoutSourceValidator::TAX_PAYMENTS:

                    array_push($subscriberList, (new VendorPaymentUpdater($payout, $mode)));

                    break;

                case PayoutSourceValidator::PAYOUT_LINKS:

                    array_push($subscriberList, (new PayoutLinkUpdater($payout, $mode)));

                    break;
            }
        }

        return $subscriberList;
    }

}
