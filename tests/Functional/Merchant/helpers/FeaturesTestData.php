<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAddFeatureToMerchant' => [
        'request' => [
            'content' => [
                'names'             => ['dummy', 's2s'],
                'entity_type'       => 'merchant',
                'entity_id'         => '10000000000000'
            ],
            'url' => '/features',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'name'              => 'dummy',
                    'entity_id'         => '10000000000000',
                    'entity_type'       => "RZP\\Models\\Merchant\\Entity"
                ],
                [
                    'name'              => 's2s',
                    'entity_id'         => '10000000000000',
                    'entity_type'       => "RZP\\Models\\Merchant\\Entity"
                ]
            ],
        ],
    ],

    'testAddInvalidFeatureToMerchant' => [
        'request' => [
            'content' => [
                'names'             => ['invalid'],
                'entity_type'       => 'merchant',
                'entity_id'         => '10000000000000'
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

    'testAddDuplicateFeatureToMerchant' => [
        'request' => [
            'content' => [
                'names'             => ['dummy'],
                'entity_type'       => 'merchant',
                'entity_id'         => '10000000000000'
            ],
            'url' => '/features',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\DbQueryException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_DB_QUERY_FAILED,
        ]
    ],

    'testMigrateMerchantFeature' => [
        'request' => [
            'content' => [
            ],
            'url' => '/features/migrate',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000001',
                    'entity_type'   => "RZP\\Models\\Merchant\\Entity"
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000002',
                    'entity_type'   => "RZP\\Models\\Merchant\\Entity"
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000003',
                    'entity_type'   => "RZP\\Models\\Merchant\\Entity"
                ],
            ]
        ]
    ],

    'testMultiAssignFeature' => [
        'request' => [
            'content' => [
                'name'          => 'dummy',
                'merchant_ids'  => ["10000000000001", "10000000000002", "10000000000003"]
            ],
            'url' => '/features/multi_assign',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000001',
                    'entity_type'   => "RZP\\Models\\Merchant\\Entity"
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000002',
                    'entity_type'   => "RZP\\Models\\Merchant\\Entity"
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000003',
                    'entity_type'   => "RZP\\Models\\Merchant\\Entity"
                ],
            ]
        ]
    ],

    'testMultiRemoveFeature' => [
        'request' => [
            'content' => [
                'name'          => 'dummy',
                'merchant_ids'  => ["10000000000001", "10000000000002", "10000000000003"]
            ],
            'url' => '/features/multi_remove',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000001',
                    'entity_type'   => "RZP\\Models\\Merchant\\Entity"
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000002',
                    'entity_type'   => "RZP\\Models\\Merchant\\Entity"
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000003',
                    'entity_type'   => "RZP\\Models\\Merchant\\Entity"
                ],
            ]
        ]
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
                        "name"              => "dummy",
                        "entity_id"         => "10000000000000",
                        "entity_type"       => "RZP\\Models\\Merchant\\Entity"
                    ],
                    [
                        "name"              => "s2s",
                        "entity_id"         => "10000000000000",
                        "entity_type"       => "RZP\\Models\\Merchant\\Entity"
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

    'testDeleteFeatureFromMerchant' => [
        'request' => [
            'content' => [
            ],
            'url' => '/features/1',
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
                'name' => 'dummy',
                'entity_id' => '10000000000000',
                'entity_type' => 'RZP\\Models\\Merchant\\Entity'
            ]
        ]
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
