<?php

namespace RZP\Tests\Functional\OAuth;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testGetToken' => [
        'request'  => [
            'url'     => '/oauth/tokens/8ckeirnw84ifkg',
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetAllTokens' => [
        'request'  => [
            'url'     => '/oauth/tokens',
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetAllTokensForBankingRoute' => [
        'request'  => [
            'url'     => '/oauth/tokens',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testRevokeToken' => [
        'request'  => [
            'url'     => '/oauth/tokens/8ckeirnw84ifkg/revoke',
            'method'  => 'PUT',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreateAppleWatchTokenForOwner' => [
        'request' => [
            'url'       => '/oauth/tokens/apple-watch',
            'method'    => 'POST',
            'content'   => [
                'otp'   =>  '000007',
                'token' =>  'dummy'
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response'  =>  [
            'content' => [
                'public_token'  => 'rzp_test_oauth_10000000000000',
                'token_type'    => 'Bearer',
                'expires_in'    => 7862400,
                'access_token'  => 'access_token',
            ],
        ]
    ],

    'testCreateAppleWatchTokenForAdminFails' => [
        'request' => [
            'url'       => '/oauth/tokens/apple-watch',
            'method'    => 'POST',
            'content'   => [
                'otp'   =>  '000007',
                'token' =>  'dummy'
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testCreateAppleWatchTokenForOwnerWhenAppDoesNotExist' => [
        'request' => [
            'url'       => '/oauth/tokens/apple-watch',
            'method'    => 'POST',
            'content'   => [
                'otp'   =>  '000007',
                'token' =>  'dummy'
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response'  =>  [
            'content' => [
                'public_token'  => 'rzp_test_oauth_10000000000000',
                'token_type'    => 'Bearer',
                'expires_in'    => 7862400,
                'access_token'  => 'access_token',
            ],
        ]
    ],

    'testCreateAppleWatchTokenForOwnerOtpMissing' => [
        'request' => [
            'url'       => '/oauth/tokens/apple-watch',
            'method'    => 'POST',
            'content'   => [
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'The otp field is required.',

                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],
];
