<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testAuthWithoutKeyOrPwd' => [
        'request' => [
            'content' => [
                'id'    => '41ce4abda390575910cba897',
                'name'  => 'Tester',
                'email' => 'test@localhost.com'
            ],
            'url' => '/merchants',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ]
            ],
            'status_code' => 400
        ],
    ],

    'updateKeyTwice' => [
        'request' => [
            'content' => [
            ],
            'url' => '/merchants/363e4efa820b0c06208ccd99/keys/d9c6bf091a1a64cb5678d8c1',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_KEY_EXPIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_KEY_EXPIRED,
        ],
    ],

    'testAppRoutesWithPrivateAuth' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ]
            ],
            'status_code' => 400,
        ]
    ],

    'testAppRoutesWithAppAuth' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'Exception',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],
];
