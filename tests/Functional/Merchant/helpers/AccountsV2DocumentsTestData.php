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

    'testInvalidProofTypeDocumentLink'    => [
        'request'   => [
            'url'     => '/v2/accounts/{accountId}/documents',
            'method'  => 'POST',
            'content' => [
                'wrong_proof_type' => [
                    [
                        'type'        => 'shop_establishment_certificate',
                        'document_id' => 'file_abc'
                    ]
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'invalid proof type: wrong_proof_type',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testInvalidDocumentTypeDocumentLink' => [
        'request'   => [
            'url'     => '/v2/accounts/{accountId}/documents',
            'method'  => 'POST',
            'content' => [
                'business_proof_of_identification' => [
                    [
                        'type'        => 'abcd',
                        'document_id' => 'file_abc'
                    ]
                ]
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

    'testSendIncorrectDocumentForProofType' => [
        'request'   => [
            'url'     => '/v2/accounts/{accountId}/documents',
            'method'  => 'POST',
            'content' => [
                'business_proof_of_identification' => [
                    [
                        'type'        => 'nbfc_registration_certificate',
                        'document_id' => 'doc_asdf1234567890'
                    ]
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Incorrect Document nbfc_registration_certificate sent for proof type business_proof_of_identification',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendStakeholderDocsForAccountLink' => [
        'request'   => [
            'url'     => '/v2/accounts/{accountId}/documents',
            'method'  => 'POST',
            'content' => [
                'individual_proof_of_address' => [
                    [
                        'type'        => 'aadhar_front',
                        'document_id' => 'doc_1cXSLlUU8V9sXl'
                    ]
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'proof type not supported: individual_proof_of_address',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testStakeholderDoesnotBelongToMerchantDocumentLink' => [
        'request'   => [
            'url'     => '/v2/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
            'method'  => 'POST',
            'content' => [
                'individual_proof_of_address' => [
                    [
                        'type'        => 'aadhar_front',
                        'document_id' => 'doc_1cXSLlUU8V9sXl'
                    ]
                ]
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
    'testStakeholderDocumentLink'                        => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
            'method'  => 'POST',
            'content' => [
                'individual_proof_of_address' => [
                    [
                        'type'        => 'aadhar_front',
                        'document_id' => 'doc_1cXSLlUU8V9sXl'
                    ],
                    [
                        'type'        => 'aadhar_back',
                        'document_id' => 'doc_1cXSLlUU8V9sXm',
                    ]
                ]
            ]
        ],
        'response' => [
            'content' => [
                'individual_proof_of_address' => [
                    [
                        'type'        => 'aadhar_front',
                        'document_id' => 'doc_1cXSLlUU8V9sXl'
                    ],
                    [
                        'type'        => 'aadhar_back',
                        'document_id' => 'doc_1cXSLlUU8V9sXm',
                    ]
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
                        'document_id' => 'doc_1cXSLlUU8V9sXl'
                    ],
                    [
                        'type'        => 'aadhar_back',
                        'document_id' => 'doc_1cXSLlUU8V9sXm',
                    ]
                ]
            ],
        ]
    ],
    'testAccountDocumentLink'                            => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/documents',
            'method'  => 'POST',
            'content' => [
                'business_proof_of_identification' => [
                    [
                        'type'        => 'shop_establishment_certificate',
                        'document_id' => 'doc_1cXSLlUU8V9sXl'
                    ],
                    [
                        'type'        => 'gst_certificate',
                        'document_id' => 'doc_1cXSLlUU8V9sXm'
                    ],
                ]
            ]
        ],
        'response' => [
            'content' => [
                'business_proof_of_identification' => [
                    [
                        'type'        => 'shop_establishment_certificate',
                        'document_id' => 'doc_1cXSLlUU8V9sXl'
                    ],
                    [
                        'type'        => 'gst_certificate',
                        'document_id' => 'doc_1cXSLlUU8V9sXm'
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
                        'document_id' => 'doc_1cXSLlUU8V9sXl'
                    ],
                    [
                        'type'        => 'gst_certificate',
                        'document_id' => 'doc_1cXSLlUU8V9sXm'
                    ],
                ]
            ],
        ]
    ]
];
