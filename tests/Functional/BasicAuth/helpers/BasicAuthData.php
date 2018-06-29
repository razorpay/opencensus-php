<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testNoAuth' => [
        'request' => [
            'url' => '/merchants',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testNoAuthOnJsonpRoute' => [
        'request' => [
            'url' => '/payments/create/jsonp',
            'method' => 'GET',
            'content' => [
                'callback' => 'abdefsdf',
                '_' => '',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testWrongKeyOnPublicJsonpRoute' => [
        'request' => [
            'url' => '/payments/create/jsonp',
            'method' => 'GET',
            'content' => [
                'callback' => 'abdefsdf',
                '_' => '',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY,
                ],
                'http_status_code' => 401,
            ],
            'status_code' => 200
        ],
        'jsonp' => true
    ],

    'testAppRoutesWithPrivateAuth' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ]
            ],
            'status_code' => 400,
        ]
    ],

    'testAdminAuth' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => []
            ],
            'status_code' => 200,
        ]
    ],

    'testPrivateAuthOnAdminRoute' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testAppRoutesWithInvalidPrivateAuth' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ]
            ],
            'status_code' => 400,
        ]
    ],

    'testPrivateAuthOnPublicRoute' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payments',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_SECRET_SENT_ON_PUBLIC_ROUTE
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testUnauthorizedOnJsonpRoute' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/create/jsonp',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY,
                ],
                // 'http_status_code' => 401,
            ],
            'status_code' => 401,
        ],
    ],

    'testNoSecretOnPrivateRoute' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/1kKG3wHhnPdcg8',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testPublicAuthWithWrongKeyId' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payments',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testPrivateAuthWithWrongKeyId' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/1kKG3wHhnPdcg8',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testPrivateAuthWithWrongSecret' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/1kKG3wHhnPdcg8',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testAppAuthWithNoSecret' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testPrivateAuthOnAppRoute' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testProxyAuthOnPrivateRouteInCloud' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments',
            'content' => [
                'count' => 1
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'items' => [
                ],
            ]
        ],
    ],

    'testProxyAuthOnPrivateRouteNotInCloud' => [
        'request' => [
            'method' => 'GET',
            'url' => 'payments',
            'content' => [
                'count' => 1
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testPrivateAuthKeyNotExpired' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments',
            'content' => [
                'count' => 1
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'items' => [
                ],
            ]
        ],
    ],

    'testPrivateAuthKeyExpired' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments',
            'content' => [
                'count' => 1
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testAppAuthWithAccount' => [
        'request' => [
            'method' => 'GET',
            'url' => '/dummy/internal'
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testAdminAuthWithAccount' => [
        'request' => [
            'method' => 'GET',
            'url' => '/dummy/admin'
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testAccountAuthInvalidId' => [
        'request' => [
            'method' => 'GET',
            'url' => '/dummy/internal'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID,
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testUserWhiteListAuthenticate' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/users/resend-verification',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_NOT_AUTHENTICATED,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testFailedMerchantUserRouteValidation' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'get',
            'content' => [
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testPartnerAuthOnJsonpRoute' => [
        'request'   => [
            'url'     => '/emi',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'HDFC' => [
                    'min_amount' => 500000,
                    'plans' => [
                        '9' => 12,
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testPartnerAuthOnJsonpRouteWrongClientId' => [
        'request'   => [
            'url'     => '/emi',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testPartnerAuthOnJsonpRouteWrongMerchantForClient' => [
        'request'   => [
            'url'     => '/emi',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testPartnerAuthOnJsonpRouteApiKey' => [
        'request'   => [
            'url'     => '/emi',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testRequestWithPartnerHeadersClientCreds' => [
        'request'   => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testRequestWithPartnerHeadersPurePlatform' => [
        'request'   => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_AUTH_NOT_ALLOWED,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testRequestWithPartnerNoSecret' => [
        'request'   => [
            'url'     => '/customers',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testRequestWithPartnerHeadersClientCredsWrongMode' => [
        'request'   => [
            'url'     => '/customers',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testRequestWithPartnerHeadersWrongClientCreds' => [
        'request'   => [
            'url'     => '/customers',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testRequestWithPartnerInactiveMerchantLiveMode' => [
        'request'   => [
            'url'     => '/customers',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ]
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\LogicException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ],
    ],

    'testRequestWithPartnerHeadersClientCredsNotPartner' => [
        'request'   => [
            'url'     => '/customers',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_AUTH_NOT_ALLOWED,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testPartnerRequestOnNonMappedMerchant' => [
        'request'   => [
            'url'     => '/customers',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER,
                ]
            ],
            'status_code' => 400,
        ],
    ],
];
