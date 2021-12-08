<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateCorporateCard' => [
        'request'  => [
            'content' => [
                'number'        => '340169570990137',
                'name'          => 'test',
                'holder_name'   => 'ABC',
                'expiry_month'  => 10,
                'expiry_year'   => 2030,
                'billing_cycle' => "1 July",
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'last4'         => '0137',
                'name'          => 'test',
                'holder_name'   => 'ABC',
                'expiry_month'  => 10,
                'expiry_year'   => 2030,
                'billing_cycle' => "1 July",
            ],
            'status_code' => '201'
        ],
    ],

    'testCreateCorporateCardFailInvalidToken' => [
        'request'  => [
            'content' => [
                'number'        => '340169570990137',
                'name'          => 'test',
                'holder_name'   => 'ABC',
                'expiry_month'  => 10,
                'expiry_year'   => 2030,
                'billing_cycle' => "1 July",
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_BATCH_UPLOAD_INVALID_TOKEN
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_UPLOAD_INVALID_TOKEN
        ],
    ],

    'testMerchantTokenCreate' => [
        'request'  => [
            'url'     => '/merchant/token',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [],
            'status_code' => '200'
        ],
    ],
];
