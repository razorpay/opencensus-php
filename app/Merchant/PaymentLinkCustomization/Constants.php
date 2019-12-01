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

    const CUSTOM_LABEL_NAMES = [
        self::BHARTI_AXA => [
            'receipt' => 'Reference Number',
            'description' => 'Policy/Vehicle Registration  Number'
        ],
        self::TEST_MID => [
            'receipt' => 'Reference Number',
            'description' => 'Policy/Vehicle Registration  Number'
        ]
    ];

    public static function getDefaultExpiryTimeForPaymentLinksByMID($mid)
    {
        return self::DEFAULT_EXPIRY[$mid] ?? null;
    }

    public static function getCustomFormLabelsForPaymentLinksByMID($mid)
    {
        return self::CUSTOM_LABEL_NAMES[$mid] ?? null;
    }
}
