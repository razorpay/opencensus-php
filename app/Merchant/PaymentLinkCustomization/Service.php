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

    /**
     * To get the custom form fields to be shown to a user.
     *
     * @param string $currentMerchantId Current selected merchant id
     *
     */
    public function getCustomizedFormFieldsByMID(string $currentMerchantId)
    {
        $fields = Constants::getCustomizedFormFieldsByMID($currentMerchantId);

        return $fields;
    }

    /**
     * To get the is customer name field to be shown to a user or not.
     *
     * @param string $currentMerchantId Current selected merchant id
     *
     */
    public function getIsCustomerNameFieldEnabledByMID(string $currentMerchantId)
    {
        $isEnabled = Constants::getIsCustomerNameFieldEnabledByMID($currentMerchantId);

        return $isEnabled;
    }
}
