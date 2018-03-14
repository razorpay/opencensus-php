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

    'testDummyFeatureEnabledOnMerchantAndApp' => [
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

    'testBearerAuthAllowAppFeaturesRouteAccess' => [
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

    'testAppBlacklistedFeatureEnabledOnMerchant' => [
        'request'  => [
            'url'     => '/payments/create/redirect',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ]
            ],
            'status_code' => 400
        ],
    ],

    'testAppBlacklistedFeatureEnabledOnApp' => [
        'request'  => [
            'url'     => '/payments/create/redirect',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200
        ],
    ],

    'testAppBlacklistedFeatureEnabledOnAppAndMerchant' => [
        'request'  => [
            'url'     => '/payments/create/redirect',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200
        ],
    ],

    'testFeatureDisabledOnAppAndMerchant' => [
        'request'  => [
            'url'     => '/dummy',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ]
            ],
            'status_code' => 400
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

    'testBearerAuthWriteAccessReadRoute' => [
        'request'  => [
            'url'     => '/payments/pay_10000000000000',
            'method'  => 'GET',
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

    'testBearerAuthLiveModeInActiveMerchant' => [
        'request'  => [
            'url'    => '/payments/pay_10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_MERCHANT_NOT_ACTIVATED
                ]
            ],
            'status_code' => 400
        ],
    ],
];
