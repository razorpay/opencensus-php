<?php

use RZP\Error\ErrorCode;

return [
    'testCreateMerchantReferral' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/merchant/referral',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
    ],

    'testFetchMerchantReferral' => [
        'request'  => [
            'url'    => '/merchant/referral',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
    ],

    'testCreateReferralNonResellerPartner' => [
        'request'   => [
            'url'    => '/merchant/referral',
            'method' => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER,
        ],
    ],

    'testCreateOrFetchReferral' => [
        'request'   => [
            'url'    => '/merchant/referral',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'product'     => 'primary',
                'referrals'   =>  [
                    'primary' => [
                        'merchant_id' => '10000000000000'
                    ],
                    'banking' => [
                        'merchant_id' => '10000000000000'
                    ],
                ]
            ],
        ],
    ]

];
