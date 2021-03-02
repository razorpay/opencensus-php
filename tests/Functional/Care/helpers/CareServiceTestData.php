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

    'testProxy' => [
        'request'  => [
            'method' => 'POST',
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

    'testProxy400Exception' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'error message',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testProxy500Exception' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'SERVER_ERROR',
                    'description' => 'The server encountered an error. The incident has been reported to admins.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => \RZP\Exception\IntegrationException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR,
        ],
    ],
];
