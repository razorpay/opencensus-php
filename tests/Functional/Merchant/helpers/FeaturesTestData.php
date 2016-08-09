<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

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

    'testResetFeatureForMerchant' => [
        'request' => [
            'content' => [
                'features'    => '',
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

    'testAddInvalidFeatureToMerchant' => [
        'request' => [
            'content' => [
                'features'    => 'invalid',
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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
