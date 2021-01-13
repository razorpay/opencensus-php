<?php

use RZP\Error\ErrorCode;

return [
    'testDocumentUploadDownload'      => [
        'request'  => [
            'url'     => '/v2/documents',
            'method'  => 'POST',
            'content' => [
                'purpose' => 'kyc_proof'
            ],
        ],
        'response' => [
            'content' => [
                'mime_type' => 'image/png',
                'purpose'   => 'kyc_proof',
                'size'      => 12345,
                'id'        => 'file_1cXSLlUU8V9sXl',
            ],
        ]
    ],

    'testDocumentUploadWrongPurpose' => [
        'request'   => [
            'url'     => '/v2/documents',
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

    'testDocumentDownloadSuccess' => [
        'request'   => [
            'url'     => '/v2/documents/{id}',
            'method'  => 'GET',
        ],
        'response'  => [
            'content'     => [
                'url'  => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf'
            ],
            'status_code' => 200,
        ]
    ],
];