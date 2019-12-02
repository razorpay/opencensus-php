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

    const DEFAULT_CUSTOMIZED_FIELDS = [
        'receipt' => [
            'label' => 'Receipt No.',
            'placeholder' => ''
        ],
        'description' => [
            'label' => 'Payment For',
            'placeholder' =>  'Payment Description'
        ]
    ];

    const CUSTOMIZED_FIELDS = [
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

    const ENABLE_CUSTOMER_NAME_FIELD = [
        self::BHARTI_AXA => true,
        self::TEST_MID => true
    ];

    public static function getDefaultExpiryTimeForPaymentLinksByMID($mid)
    {
        return self::DEFAULT_EXPIRY[$mid] ?? null;
    }

    public static function getCustomizedFormFieldsByMID($mid)
    {
        return self::CUSTOMIZED_FIELDS[$mid] ?? self::DEFAULT_CUSTOMIZED_FIELDS;
    }

    public static function getIsCustomerNameFieldEnabledByMID($mid)
    {
        return self::ENABLE_CUSTOMER_NAME_FIELD[$mid] ?? null;
    }
}
