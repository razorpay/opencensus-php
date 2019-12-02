<?php

namespace App\Merchant\PaymentLinkCustomization;

class Constants
{
    const   TEST_MID = '10000000000000',
            BHARTI_AXA = 'D2BsrUJVg04abr';

    const DEFAULT_EXPIRY = [
        self::BHARTI_AXA => 72,
        self::TEST_MID => 72
    ];

    const ENABLE_CUSTOMER_NAME_FIELD = [
        self::BHARTI_AXA => true,
        self::TEST_MID => true
    ];

    public static function getDefaultExpiryTimeForPaymentLinksByMID($mid)
    {
        return self::DEFAULT_EXPIRY[$mid] ?? null;
    }

    public static function getIsCustomerNameFieldEnabledForPaymentLinksByMID($mid)
    {
        return self::ENABLE_CUSTOMER_NAME_FIELD[$mid] ?? null;
    }
}
