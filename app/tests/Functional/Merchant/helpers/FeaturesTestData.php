<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testAddFeatureToMerchant' => [
        'request' => [
            'content' => [
                'features'    => 'dummy',
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'id' => '10000000000000',
                //'features' => 'dummy'
            ],
        ],
    ],

    'testGetFeatureListForMerchant' => [
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

    'testDummyFeatureRouteWithoutAccess' => [
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

    'testDummyFeatureRouteWithAccess' => [
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
