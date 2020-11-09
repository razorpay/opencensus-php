<?php

namespace RZP\Models\Payout\SourceUpdater;

use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\PayoutSource\Entity as PayoutSourceEntity;

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
                case PayoutSourceEntity::VENDOR_PAYMENTS:

                case PayoutSourceEntity::TAX_PAYMENTS:

                    array_push($subscriberList, (new VendorPaymentUpdater($payout, $mode)));

                    break;
                    // un-comment this code once payout link source migration happens
//                    case PayoutSourceEntity::SOURCE_PAYOUT_LINK:
//
//                        array_push($subscriberList, (new PayoutLinkUpdater($payout, $mode)));
            }
        }

        // this line to be removed and the above Switch statement to be used, once payout-link migration is done
        array_push($subscriberList, (new PayoutLinkUpdater($payout, $mode)));

        return $subscriberList;
    }

}
