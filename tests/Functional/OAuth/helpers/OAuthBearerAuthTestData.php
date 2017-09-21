<?php

namespace RZP\Tests\Functional\OAuth;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testBearerAuth' => [
        'request'  => [
            'url'    => '/payments/pay_10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity'   => 'payment',
                'id'       => 'pay_10000000000000',
                'amount'   => 1000000,
                'currency' => 'INR',
                'status'   => 'created',
                'method'   => 'card',
                'captured' => false,
            ],
        ],
    ],

    'testBearerAuthProdClient' => [
        'request'  => [
            'url'    => '/payments/pay_10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity'   => 'payment',
                'id'       => 'pay_10000000000000',
                'amount'   => 1000000,
                'currency' => 'INR',
                'status'   => 'created',
                'method'   => 'card',
                'captured' => false,
            ],
        ],
    ],

    'testBearerAuthDummyRouteScope' => [
        'request'  => [
            'url'     => '/dummy',
            'method'  => 'GET',
            'content' => [
                'name' => 'dummy',
                'role' => 'just chilling',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'dummy',
                'role' => 'just chilling',
            ],
        ],
    ],

    'testBearerAuthDummyRouteScopeFail' => [
        'request'  => [
            'url'     => '/payments',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_SCOPE_INVALID
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testBearerAuthWriteAccess' => [
        'request'  => [
            'url'     => '/webhooks',
            'method'  => 'POST',
            'content' => [
                'url'    => 'https://www.example.com',
                'events' => ['payment.authorized' => '1'],
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'webhook',
                'url' => 'https://www.example.com',
                'events' => [
                    'payment.authorized' => true,
                ],
                'active' => true,
            ],
        ],
    ],

    'testBearerAuthOutsideOfScope' => [
        'request'  => [
            'url'    => '/payments/create',
            'method' => 'POST'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_SCOPE_INVALID
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testBearerAuthWithTamperedToken' => [
        'request'   => [
            'url'    => '/payments/create',
            'method' => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testBearerAuthWithTamperedJWTPayload' => [
        'request'   => [
            'url'    => '/webhooks',
            'method' => 'POST',
            'content' => [
                'url'    => 'https://www.example.com',
                'events' => ['payment.authorized' => '1'],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testBearerAuthExpiredToken' => [
        'request'   => [
            'url'    => '/webhooks',
            'method' => 'POST',
            'content' => [
                'url'    => 'https://www.example.com',
                'events' => ['payment.authorized' => '1'],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID
                ]
            ],
            'status_code' => 401
        ],
    ],
];
