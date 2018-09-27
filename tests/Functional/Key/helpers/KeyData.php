<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testNewKeyIdRandom' => [
        'request' => [
            'url' => '/keys/rzp_test_TheTestAuthKey',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'old' => [
                ],
                'new' => [
                ]
            ],
        ],
    ],

    'testRegenerateKeyWhereMerchantIdIsDifferent' => [
        'request' => [
            'url' => '',
            'method' => 'PUT',
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testGetKeys' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testGetKeysByNonOwnerUser' => [
        'request'  => [
            'method' => 'GET',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testGetKeysByEPosUser' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'         => 'rzp_test_AltTestAuthKey',
                        'entity'     => 'key',
                        'expired_at' => NULL,
                    ],
                ]
            ],
            'status_code' => 200,
        ],
    ],
];
