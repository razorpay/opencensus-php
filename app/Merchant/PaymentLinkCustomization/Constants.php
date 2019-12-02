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

    const CUSTOM_FIELDS_FALLBACK = [
        'receipt' => [
            'label' => 'Receipt No.',
            'placeholder' => ''
        ],
        'description' => [
            'label' => 'Payment For',
            'placeholder' =>  'Payment Description'
        ]
    ];

    const CUSTOM_FIELDS = [
        self::BHARTI_AXA => [
            'receipt' => [
                'label' => 'Reference Number',
                'placeholder' => ''
            ],
            'description' => [
                'label' => 'Policy/Vehicle Registration  Number',
                'placeholder' => ''
            ]
        ],
        self::TEST_MID => [
            'receipt' => [
                'label' => 'Reference Number',
                'placeholder' => ''
            ],
            'description' => [
                'label' => 'Policy/Vehicle Registration  Number',
                'placeholder' => ''
            ]
        ]
    ];

    public static function getDefaultExpiryTimeForPaymentLinksByMID($mid)
    {
        return self::DEFAULT_EXPIRY[$mid] ?? null;
    }

    public static function getCustomFormFieldsForPaymentLinksByMID($mid)
    {
        return self::CUSTOM_FIELDS[$mid] ?? self::CUSTOM_FIELDS_FALLBACK;
    }
}
