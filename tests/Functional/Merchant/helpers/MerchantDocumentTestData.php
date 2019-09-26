<?php
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testDeleteDocumentIdNotValid' => [
        'request' => [
            'url' => '/merchant/documents/doc_aaaabbbbcccc',
            'method' => 'delete'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'aaaabbbbcccc is not a valid id',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    "testDeleteDocument" => [
        'request' => [
            'url' => '/merchant/documents/%s',
            'method' => 'delete'
        ],
        'response' => [
            'content' => [
                "id" => '%s',
                "deleted" => true
            ]
        ]
    ],

    "testDeleteDocumentIdNOtExist" => [
        'request' => [
            'url' => '/merchant/documents/doc_aaaabbbbccccbb',
            'method' => 'delete'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ]
];
