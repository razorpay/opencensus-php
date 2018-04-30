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
];
