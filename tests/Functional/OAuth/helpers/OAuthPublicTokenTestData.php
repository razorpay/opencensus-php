<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testAuthenticatePublicTokenViaKeyIdParam' => [
        'request'  => [
            'url'    => '/v1/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'version' => 1
            ],
        ],
    ],

    'testOAuthPublicTokenPrivateRoute' => [
        'request'   => [
            'url'    => '/v1/payments',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY,
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testOAuthInvalidPublicToken' => [
        'request'  => [
            'url'    => '/v1/preferences',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID,
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testOAuthPublicTokenExpired' => [
        'request'  => [
            'url'    => '/v1/preferences',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID,
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testOAuthPublicTokenInvalidScope' => [
        'request'  => [
            'url'    => '/v1/preferences',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_SCOPE_INVALID,
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testStatusAfterPaymentOAuth' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID
        ],
    ],

];
