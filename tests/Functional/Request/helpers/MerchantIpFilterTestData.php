<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testApiGetsExpectedResponseForWhitelistedIps' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/merchant/instant_activation',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description'         => 'The business category field is required.',
        ],
    ],

    'testApiGetsExpectedResponseForNoWhitelistedIps' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/merchant/instant_activation',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description'         => 'The business category field is required.',
        ],
    ],

    'testApiGetsBlockedForNonWhitelistedIps' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/merchant/instant_activation',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Dashboard cant be accessed from the current location'
                ],
            ],
            'status_code' => 400,
        ],

    ],

    'testApiGetsExpectedResponseForOtherAuths' => [
        'request'  => [
            'url'    => '/admin/iin/607500',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'iin'         => '607500',
                'category'    => 'STANDARD',
                'network'     => 'RuPay',
                'type'        => 'debit',
                'country'     => 'IN',
                'issuer_name' => 'State Bank of India',
                'trivia'      => 'random trivia'
            ]
        ],
    ]
];
