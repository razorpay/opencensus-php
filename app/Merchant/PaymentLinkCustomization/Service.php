<?php

namespace App\Merchant\PaymentLinkCustomization;

use App\Base;

class Service extends Base\Service
{

    /**
     * To get the list of notifications to be shown to a user.
     *
     * @param string $currentMerchantId Current selected merchant id
     *
     */
    public function getDefaultExpiryTimeForPaymentLinksForMerchant(string $currentMerchantId)
    {
        $defaultExpiryTime = Constants::getDefaultExpiryTimeForPaymentLinksByMID($currentMerchantId);

        return $defaultExpiryTime;
    }
}
