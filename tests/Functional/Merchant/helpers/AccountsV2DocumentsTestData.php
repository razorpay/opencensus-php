<?php

use RZP\Error\ErrorCode;

return [
    'testDocumentUploadSuccess'      => [
        'request'  => [
            'url'     => '/documents/upload',
            'method'  => 'POST',
            'content' => [
                'purpose' => 'kyc_proof'
            ],
        ],
        'response' => [
            'content' => [
                'mime_type' => 'image/png',
                'purpose'   => 'kyc_proof'
            ],
        ]
    ],

    'testDocumentUploadWrongPurpose' => [
        'request'   => [
            'url'     => '/documents/upload',
            'method'  => 'POST',
            'content' => [
                'purpose' => 'wrong_purpose'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'invalid document upload purpose:wrong_purpose',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];