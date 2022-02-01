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

                case PayoutSourceEntity::PAYOUT_LINK:

                    array_push($subscriberList, (new PayoutLinkUpdater($payout, $mode)));

                    break;

                case PayoutSourceEntity::SETTLEMENTS:

                    array_push($subscriberList, (new SettlementsUpdater($payout, $mode)));

                    break;

                case PayoutSourceEntity::XPAYROLL:

                    array_push($subscriberList, (new XPayrollUpdater($payout, $mode)));

                    break;

                case PayoutSourceEntity::REFUND:

                    array_push($subscriberList, (new RefundsUpdater($payout, $mode)));

                    break;

                case PayoutSourceEntity::CAPITAL_COLLECTIONS:

                    array_push($subscriberList, (new CapitalCollectionsUpdater($payout, $mode)));

                    break;
            }
        }

        return $subscriberList;
    }

}
