<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testAuthWithoutKeyOrPwd' => [
        'request' => [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
                'name'  => 'Tester',
                'email' => 'test@localhost.com'
            ],
            'url' => '/merchants',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY
                ]
            ],
            'status_code' => 401
        ],
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
            'url' => '/payments/abcd/callback/',
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

    'testPublicAuthOnPrivateRoute' => [
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
            'url' => '/payments/jsonp',
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

    'testPublicAuthOnAppRoute' => [
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
                'data' => [
                    [
                        'entity' => 'payment',
                    ],
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
    ]
];
