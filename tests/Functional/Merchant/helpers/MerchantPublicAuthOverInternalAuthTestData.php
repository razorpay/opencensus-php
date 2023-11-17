<?php

return [
    'testPublicAuthInternal' => [
        'request' => [
            'method' => 'GET',
            'url' => '/internal/checkout/auth',
            'content' => [
                'merchant_public_key' => 'rzp_test_TheTestAuthKey',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'merchant_key' => 'rzp_test_TheTestAuthKey',
                'mode' => 'test',
            ],
        ],
    ],

    'testPartnerAuthInternal' => [
        'request' => [
            'method' => 'GET',
            'url' => '/internal/checkout/auth',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '100000Razorpay',
                'merchant_key' => '',
                'mode' => 'test',
            ],
        ],
    ],

    'testPublicAuthInternalKeyless' => [
        'request' => [
            'method' => 'GET',
            'url' => '/internal/checkout/auth',
            'content' => [], // Filled by the Test with various Keyless Entities with their Public Id's
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'merchant_key' => 'rzp_test_TheTestAuthKey',
                'mode' => 'test',
            ],
        ],
    ],
];
