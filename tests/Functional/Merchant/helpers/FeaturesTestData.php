<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAddFeatureToMerchant' => [
        'request' => [
            'content' => [
                'names'                     => ['dummy', 's2s'],
                'toggleable_type'           => 'merchant',
                'toggleable_id'             => '10000000000000'
            ],
            'url' => '/features',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'name'                  => 'dummy',
                    'toggleable_id'         => '10000000000000',
                    'toggleable_type'       => "RZP\\Models\\Merchant\\Entity"
                ],
                [
                    'name'                  => 's2s',
                    'toggleable_id'         => '10000000000000',
                    'toggleable_type'       => "RZP\\Models\\Merchant\\Entity"
                ]
            ],
        ],
    ],

    'testAddInvalidFeatureToMerchant' => [
        'request' => [
            'content' => [
                'names'                     => ['invalid'],
                'toggleable_type'           => 'merchant',
                'toggleable_id'             => '10000000000000'
            ],
            'url' => '/features',
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
            'url' => '/features/merchant/10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            "content" => [
                "assigned_features" => [
                    [
                        "name"                  => "dummy",
                        "toggleable_id"         => "10000000000000",
                        "toggleable_type"       => "RZP\\Models\\Merchant\\Entity"
                    ],
                    [
                        "name"                  => "s2s",
                        "toggleable_id"         => "10000000000000",
                        "toggleable_type"       => "RZP\\Models\\Merchant\\Entity"
                    ],
                ],
                "all_features" => [
                    "dummy",
                    "webhooks",
                    "aggregator",
                    "tokens",
                    "s2swallet",
                    "setl_report",
                    "cardsaving",
                    "recurring",
                    "s2s"
                ]
            ]
        ]
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
