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
            'method' => 'POST',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                [
                    'name'              => 'dummy',
                    'entity_id'         => '10000000000000',
                    'entity_type'       => 'merchant'
                ],
                [
                    'name'              => 's2s',
                    'entity_id'         => '10000000000000',
                    'entity_type'       => 'merchant'
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
            'method' => 'POST',
            'server' => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-Admin-Username' => 'admin',
                'HTTP_X-Dashboard-User-Email'     => 'user@rzp.dev',
            ],
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
            'method' => 'POST',
            'server' => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-Admin-Username' => 'admin',
            ],
        ],
        'response' => [
            'content' => [
                null
            ],
            'status_code' => 200,
        ]
    ],

    'testMultiAssignFeature' => [
        'request' => [
            'content' => [
                'name'          => 'dummy',
                'entity_ids'    => ['10000000000001', '10000000000002', '10000000000003'],
                'entity_type'   => 'merchant'
            ],
            'url' => '/features/assign',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-Admin-Username' => 'admin',
                'HTTP_X-Dashboard-User-Email'     => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000001',
                    'entity_type'   => 'merchant'
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000002',
                    'entity_type'   => 'merchant'
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000003',
                    'entity_type'   => 'merchant'
                ],
            ]
        ]
    ],

    'testMultiRemoveFeature' => [
        'request' => [
            'content' => [
                'name'          => 'dummy',
                'entity_ids'    => ['10000000000001', '10000000000002', '10000000000003']
            ],
            'url' => '/features/remove',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000001',
                    'entity_type'   => 'merchant'
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000002',
                    'entity_type'   => 'merchant'
                ],
                [
                    'name'          => 'dummy',
                    'entity_id'     => '10000000000003',
                    'entity_type'   => 'merchant'
                ],
            ]
        ]
    ],

    'testGetFeatureListForMerchant' => [
        'request' => [
            'content' => [
            ],
            'url' => '/features/10000000000000',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'assigned_features' => [
                    [
                        'name'              => 'dummy',
                        'entity_id'         => '10000000000000',
                        'entity_type'       => 'merchant'
                    ],
                    [
                        'name'              => 's2s',
                        'entity_id'         => '10000000000000',
                        'entity_type'       => 'merchant'
                    ],
                ],
                'all_features' => [
                    'dummy',
                    'webhooks',
                    'aggregator',
                    'tokens',
                    's2swallet',
                    's2supi',
                    's2saeps',
                    'setl_report',
                    'noflashcheckout',
                    'recurring',
                    's2s',
                    'invoice',
                    'nozeropricing',
                    'reverse',
                ]
            ]
        ]
    ],

    'testDeleteFeatureFromMerchant' => [
        'request' => [
            'content' => [
            ],
            'url' => '/features/10000000000000/dummy',
            'method' => 'DELETE',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
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
            'method' => 'GET',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
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
            'method' => 'GET',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testDeleteNonExistentFeatureFromMerchant' => [
        'request' => [
            'url'       => '/features/10000000000000/xxxxx',
            'method'    => 'delete',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_RECORDS_FOUND
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ]
    ],

    'testAddFeatureToTestVerifyAbsenceInLive' => [
        'request'  => [
            'url'    => '/features/10000000000000',
            'method' => 'get',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ]
    ],

    'testDeleteFeatureFromTestAndVerifyPresenceInLive' => [
        'request'  => [
            'url'    => '/features/10000000000000',
            'method' => 'get',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'assigned_features' => [
                    [
                        'name'        => 'dummy',
                        'entity_id'   => '10000000000000',
                        'entity_type' => 'merchant'
                    ],
                    [
                        'name'        => 'noflashcheckout',
                        'entity_id'   => '10000000000000',
                        'entity_type' => 'merchant'
                    ]
                ],
                'all_features'      => [
                    'dummy',
                    'webhooks',
                    'aggregator',
                    'tokens',
                    's2swallet',
                    's2supi',
                    's2saeps',
                    'setl_report',
                    'noflashcheckout',
                    'recurring',
                    's2s',
                    'invoice',
                    'nozeropricing',
                    'reverse',
                ]
            ]
        ]
    ],

    'testAddOptOutFeatureToLiveVerifyAbsenceInTest' => [
        'request'  => [
            'url'    => '/features/10000000000000',
            'method' => 'get',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ]
    ],

    'testDeleteOptInFeatureFromLiveVerifyPresenceInTest' => [
        'request'  => [
            'url'    => '/features/10000000000000',
            'method' => 'get',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'assigned_features' => [
                    [
                        'name'        => 'dummy',
                        'entity_id'   => '10000000000000',
                        'entity_type' => 'merchant'
                    ]
                ],
                'all_features'      => [
                    'dummy',
                    'webhooks',
                    'aggregator',
                    'tokens',
                    's2swallet',
                    's2supi',
                    's2saeps',
                    'setl_report',
                    'noflashcheckout',
                    'recurring',
                    's2s',
                    'invoice',
                    'nozeropricing',
                    'reverse',
                ]
            ]
        ]
    ],

    'testDeleteOptOutFeatureFromLiveVerifyAbsenceInTest' => [
        'request'  => [
            'url'    => '/features/10000000000000',
            'method' => 'get',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ]
    ],

];
