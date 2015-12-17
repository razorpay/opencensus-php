<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testAddBetaFeatureToMerchant' => [
        'request' => [
            'content' => [
                'beta_features'    => 'dummy',
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'id' => '10000000000000',
            ],
        ],
    ],

    'testGetBetaFeatureListForMerchant' => [
        'request' => [
            'content' => [
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "dummy"
            ],
        ],
    ],

    'testGetAllFeatures' => [
        'request' => [
            'content' => [
            ],
            'url' => '/features',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "dummy","webhooks"
            ],
        ],
    ],

    'testDummyBetaFeatureRouteWithoutAccess' => [
        'request' => [
            'content' => [
            ],
            'url' => '/features/dummy',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testDummyBetaFeatureRouteWithAccess' => [
        'request' => [
            'content' => [
            ],
            'url' => '/features/dummy',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
];
