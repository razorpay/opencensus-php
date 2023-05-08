<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testOAuthAppMerchantMap' => [
        'request'  => [
            'url'     => '/merchants/10000000000000/applications',
            'method'  => 'POST',
            'content' => [
                'application_id' => '10000000000App',
                'partner_id'     => '10000000000000',
            ]
        ],
        'response' => [
            'content'     => [
                'merchant_id' => '10000000000000',
                'entity_id'   => '10000000000App',
                'entity_type' => 'application',
            ],
            'status_code' => 200,
        ],
    ],

    'testOAuthAppMerchantMapIncorrectEntityId' => [
        'request'  => [
            'url'     => '/merchants/10000000000000/applications',
            'method'  => 'POST',
            'content' => [
                'application_id' => '10000000000Ap',
                'partner_id'     => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                     'description' => 'The application id must be 14 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOAuthAppMerchantMapDuplicate' => [
        'request'  => [
            'url'     => '/merchants/10000000000000/applications',
            'method'  => 'POST',
            'content' => [
                'application_id' => '10000000000App',
                'partner_id'     => '10000000000000',
            ]
        ],
        'response' => [
            'content'     => [
                'merchant_id' => '10000000000000',
                'entity_id'   => '10000000000App',
                'entity_type' => 'application',
            ],
            'status_code' => 200,
        ],
    ],

    'testOAuthAppMerchantMapDuplicateWithDeleted' => [
        'request'  => [
            'url'     => '/merchants/10000000000000/applications',
            'method'  => 'POST',
            'content' => [
                'application_id' => '10000000000App',
                'partner_id'     => '10000000000000',
            ]
        ],
        'response' => [
            'content'     => [
                'merchant_id' => '10000000000000',
                'entity_id'   => '10000000000App',
                'entity_type' => 'application',
            ],
            'status_code' => 200,
        ],
    ],

    'testOAuthAppDeleteMerchantMap' => [
        'request'  => [
            'url'     => '/merchants/10000000000000/applications/10000000000App',
            'method'  => 'DELETE',
        ],
        'response' => [
            'content'     => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testOAuthAppDeleteWebhook' => [
        'request'  => [
            'url'     => '/merchants/10000000000000/applications/10000000000App',
            'method'  => 'DELETE',
        ],
        'response' => [
            'content'     => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testOAuthAppDeleteMerchantMapNoEntries' => [
        'request'  => [
            'url'     => '/merchants/10000000000000/applications/10000000000App',
            'method'  => 'DELETE',
        ],
        'response' => [
            'content'     => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testOAuthSyncMerchantMap' => [
        'request'  => [
            'url'     => '/oauth/update_merchant_map',
            'method'  => 'POST',
        ],
        'response' => [
            'content'     => [
                'success' => 2,
                'failure' => 0,
                'total'   => 2,
                'failed'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testGetConnectedApplications' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/merchants/10000000000000/applications',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'merchant_id'     => '10000000000000',
                        'entity_id'       => '10000000000App',
                        'entity_type'     => 'application',
                        'entity_owner_id' => '10000000000000',
                        // 'created_at'      => 1559036401,
                    ],
                ],
            ],
        ],
    ],

    'testGetConnectedApplicationsWithServiceOwner' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/merchants/10000000000000/applications',
            'content' => [
                'service' => 'api-live',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'merchant_id'     => '10000000000000',
                        'entity_id'       => '10000000000App',
                        'entity_type'     => 'application',
                        'entity_owner_id' => '10000000000000',
                        // 'created_at'      => 1559036401,
                    ],
                ],
            ],
        ],
    ],

    'testGetConnectedApplicationsWithServiceOwnerAsApi' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/merchants/10000000000000/applications',
            'content' => [
                'service' => 'api-live',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
                        'merchant_id'     => '10000000000000',
                        'entity_id'       => '10000000000App',
                        'entity_type'     => 'application',
                        'entity_owner_id' => '10000000000000',
                        // 'created_at'      => 1559036401,
                    ],
                    [
                        'merchant_id'     => '10000000000000',
                        'entity_type'     => 'application',
                        'entity_owner_id' => '10000000000000',
                    ]
                ],
            ],
        ],
    ],

    'testGetConnectedApplicationsWithServiceOwnerAsRx' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/merchants/10000000000000/applications',
            'content' => [
                'service' => 'rx-live',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'merchant_id'     => '10000000000000',
                        'entity_id'       => '10000000000App',
                        'entity_type'     => 'application',
                        'entity_owner_id' => '10000000000000',
                    ],
                ],
            ],
        ],
    ],
];
