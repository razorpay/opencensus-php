<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testDeleteDocumentIdNotValid' => [
        'request'   => [
            'url'    => '/merchant/documents/doc_aaaabbbbcccc',
            'method' => 'delete'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'aaaabbbbcccc is not a valid id',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDeleteDocument' => [
        'request'  => [
            'url'    => '/merchant/documents/%s',
            'method' => 'delete'
        ],
        'response' => [
            'content' => [
                "id"      => '%s',
                "deleted" => true
            ]
        ]
    ],

    'testDeleteDocumentError' => [
        'request'   => [
            'url'    => '/merchant/documents/doc_aaaabbbbccccbb',
            'method' => 'delete'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testFileUploadFileNotExist' => [
        'request'   => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'promoter_address_url'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The file field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFileUploadFormLocked' => [
        'request'   => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'promoter_address_url'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Activation form has been locked for editing by admin.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED,
        ],
    ],

    'testFileUploadFileTypeNotSupported' => [
        'request'   => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'promoter_address_url'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The file must be a file of type: pdf, jpeg, jpg, png, zip.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFileUpload' => [
        'request'  => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'promoter_address_url'
            ],
        ],
        'response' => [
            'content' => [
                "merchant_id"   => "10000000000000",
                "document_type" => "promoter_address_url"
            ]
        ]
    ]
];
