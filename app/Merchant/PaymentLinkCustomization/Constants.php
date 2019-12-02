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

    const EXTRA_FORM_FIELDS = [
        self::BHARTI_AXA => [
            'name' => 'Product',
            'type' => 'select',
            'required' => true,
            'options' => [
                'Car',
                'Health',
                'Travel'
            ],
            'addAt' => 'description'
        ],
        self::TEST_MID => [
            'name' => 'Product',
            'type' => 'select',
            'required' => true,
            'options' => [
                'Car',
                'Health',
                'Travel'
            ],
            'addAt' => 'description'
        ]
    ];

    public static function getDefaultExpiryTimeForPaymentLinksByMID($mid)
    {
        return self::DEFAULT_EXPIRY[$mid] ?? null;
    }

    public static function getExtraFormFieldsForPaymentLinksByMID($mid)
    {
        return self::EXTRA_FORM_FIELDS[$mid] ?? null;
    }
}
