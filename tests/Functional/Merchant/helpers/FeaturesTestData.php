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
                    'entity_type'       => "merchant"
                ],
                [
                    'name'              => 's2s',
                    'entity_id'         => '10000000000000',
                    'entity_type'       => "merchant"
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
                null
            ],
            'status_code' => 200,
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
                    'entity_type'   => "merchant"
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000002',
                    'entity_type'   => "merchant"
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000003',
                    'entity_type'   => "merchant"
                ],
            ]
        ]
    ],

    'testMultiAssignFeature' => [
        'request' => [
            'content' => [
                'name'          => 'dummy',
                'entity_ids'    => ["10000000000001", "10000000000002", "10000000000003"],
                'entity_type'   => 'merchant'
            ],
            'url' => '/features/assign',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000001',
                    'entity_type'   => "merchant"
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000002',
                    'entity_type'   => "merchant"
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000003',
                    'entity_type'   => "merchant"
                ],
            ]
        ]
    ],

    'testMultiRemoveFeature' => [
        'request' => [
            'content' => [
                'name'          => 'dummy',
                'entity_ids'    => ["10000000000001", "10000000000002", "10000000000003"]
            ],
            'url' => '/features/remove',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000001',
                    'entity_type'   => "merchant"
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000002',
                    'entity_type'   => "merchant"
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000003',
                    'entity_type'   => "merchant"
                ],
            ]
        ]
    ],

    'testGetFeatureListForMerchant' => [
        'request' => [
            'content' => [
            ],
            'url' => '/features/10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            "content" => [
                "assigned_features" => [
                    [
                        "name"              => "dummy",
                        "entity_id"         => "10000000000000",
                        "entity_type"       => "merchant"
                    ],
                    [
                        "name"              => "s2s",
                        "entity_id"         => "10000000000000",
                        "entity_type"       => "merchant"
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
            'url' => '/features/10000000000000/dummy',
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
                'name' => 'dummy',
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ]
        ]
    ],

    'testDummyFeatureRouteWithoutAccess' => [
        'request' => [
            'content' => [
            ],
            'url' => '/dummy',
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
            'url' => '/dummy',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
];
