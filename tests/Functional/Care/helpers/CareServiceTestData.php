<?php

use RZP\Error\ErrorCode;

return [
    'testInternalMerchantFetch' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/internal/merchants/10000000000000',
        ],
        'response' => [
            'content' => [
                'merchant' => [
                    'id'         => '10000000000000',
                    'activated'  => false,
                    'hold_funds' => false,
                ],
            ],
        ],
    ],

    'testDashboardProxy' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testDashboardProxyInvalidRoute' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/care_service/merchant/twirp/random',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_URL_NOT_FOUND,
        ],

    ],
];
