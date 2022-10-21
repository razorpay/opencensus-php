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
    public function getDefaultExpiryTimeForPaymentLinksForMerchant(string $currentMerchantId): ?int
    {
        $defaultExpiryTime = Constants::getDefaultExpiryTimeForPaymentLinksByMID($currentMerchantId);

        return $defaultExpiryTime;
    }

    /**
     * To get the extra form fields to be shown to a user.
     *
     * @param string $currentMerchantId Current selected merchant id
     *
     */
    public function getExtraFormFieldsByMID(string $currentMerchantId): array
    {
        $extraFields = Constants::getExtraFormFieldsByMID($currentMerchantId);

        return $extraFields;
    }

    /**
     * To get the custom form fields to be shown to a user.
     *
     * @param string $currentMerchantId Current selected merchant id
     *
     */
    public function getCustomizedFormFieldsByMID(string $currentMerchantId): array
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
    public function getIsCustomerNameFieldEnabledByMID(string $currentMerchantId): ?bool
    {
        $isEnabled = Constants::getIsCustomerNameFieldEnabledByMID($currentMerchantId);

        return $isEnabled;
    }
}
