<?php

use RZP\Error\ErrorCode;

return [
    'testDocumentUploadDownload' => [
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
                'id'        => 'doc_1cXSLlUU8V9sXl',
            ],
        ]
    ],

    'testDocumentUploadDownloadV1Routes' => [
        'request'  => [
            'url'     => '/documents',
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
                'id'        => 'doc_1cXSLlUU8V9sXl',
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
                    'code'        => 'BAD_REQUEST_ERROR',
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
        'request'  => [
            'url'    => '/v2/documents/{id}',
            'method' => 'GET',
        ],
        'response' => [
            'content'     => [
                'url' => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf'
            ],
            'status_code' => 200,
        ]
    ],

    'testDocumentDownloadInvalidExpiryUpperLimit' => [
        'request'  => [
            'url'    => '/v2/documents/{id}',
            'method' => 'GET',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'The expiry may not be greater than 120.',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testDocumentDownloadInvalidExpiryLowerLimit' => [
        'request'  => [
            'url'    => '/v2/documents/{id}',
            'method' => 'GET',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'The expiry must be at least 1.',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testInvalidDocumentTypeForDocumentPost' => [
        'request'   => [
            'url'     => '/v2/accounts/{accountId}/documents',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'abcd',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'invalid document type:abcd',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendStakeholderDocsForAccount' => [
        'request'   => [
            'url'     => '/v2/accounts/{accountId}/documents',
            'method'  => 'POST',
            'content' => [
                'document_type'        => 'aadhar_front',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'invalid document type:aadhar_front for merchant',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testStakeholderDoesnotBelongToMerchantDocumentPost' => [
        'request'   => [
            'url'     => '/v2/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
            'method'  => 'POST',
            'content' => [
                'document_type'   => 'aadhar_front',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Stakeholder does not belong to merchant',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_STAKEHOLDER_DOES_NOT_BELONG_TO_MERCHANT,
        ],
    ],

    'testPostStakeholderDocument'   => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
            'method'  => 'POST',
            'content' => [
                'document_type'        => 'aadhar_front',
            ]
        ],
        'response' => [
            'content' => [
                'individual_proof_of_address' => [
                    [
                        'type'        => 'aadhar_front',
                        'url'         => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf',
                    ],
                ]
            ],
        ]
    ],

    'testStakeholderDocumentFetch'                       => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'individual_proof_of_address' => [
                    [
                        'type'        => 'aadhar_front',
                        'url'         => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf',
                    ],
                ]
            ],
        ]
    ],

    'testPostAccountDocument'                            => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/documents',
            'method'  => 'POST',
            'content' => [
                'document_type'        => 'shop_establishment_certificate',
            ]
        ],
        'response' => [
            'content' => [
                'business_proof_of_identification' => [
                    [
                        'type'        => 'shop_establishment_certificate',
                        'url'         => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf',
                    ],
                ]
            ],
        ]
    ],

    'testAccountDocumentFetch'                           => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/documents',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'business_proof_of_identification' => [
                    [
                        'type'        => 'shop_establishment_certificate',
                        'url'         => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf',
                    ],
                ]
            ],
        ]
    ],

    'testPostAccountAdditionalDocuments' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/documents',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'form_12a_url',
            ]
        ],
        'response' => [
            'content' => [
                'additional_documents' => [
                    [
                        'type' => 'form_12a_url',
                        'url'  => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf',
                    ],
                ]
            ],
        ]
    ],

    'testUploadCancelledChequeVideo' => [
        'request'   => [
            'url'     => '/v2/accounts/{accountId}/documents',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'cancelled_cheque_video',
            ]
        ],
        'response'  => [
            'content' => []
        ],
    ],
];
