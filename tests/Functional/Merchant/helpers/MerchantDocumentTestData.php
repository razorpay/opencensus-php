<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\Merchant\Document\Type as DocumentType;

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
                'verification' => [
                    'required_fields' => [

                    ]
                ],
            ]
        ]
    ],

    'testGetDocumentTypes' => [
        'request'  => [
            'url'    => '/merchant_document/types',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'count' => count(DocumentType::VALID_DOCUMENTS)
            ]
        ],
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
                    'description' => 'Merchant activation form has been locked for editing by admin.',
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
                    'description' => 'The file must be a file of type: pdf, jpeg, jpg, png, jfif, heic, heif.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testVideoFileUpload' => [
            'request'  => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'cancelled_cheque_video',
            ],
        ],
    'response' => [
            'content' => [
                'documents' => [
                    'cancelled_cheque_video' => [

                    ]
                ],
            ]
        ]
    ],

    'testOnlyVideoFileUploadSupported' => [
        'request'   => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'cancelled_cheque_video',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The file must be a file of type: wmv, m4v, mkv, mpg, avi, flv, mov, mp4, mpeg',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testVideoUploadForUnsupportedDocuments' => [
        'request'   => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'gia_certificate',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The file must be a file of type: pdf, jpeg, jpg, png, jfif, heic, heif.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testFileUploadDocumentTypeInvalid' => [
        'request'   => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'abc'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'invalid document type:abc',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDocumentUpload' => [
        'request'  => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'promoter_address_url'
            ],
        ],
        'response' => [
            'content' => [
                'documents' => [
                    'promoter_address_url' => [

                    ]
                ],
            ]
        ]
    ],

    'testDocumentUploadForPartnerKyc' => [
        'request'  => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type'  => 'promoter_address_url',
                'is_partner_kyc' => true
            ],
        ],
        'response' => [
            'content' => [
                'documents' => [
                    'promoter_address_url' => [

                    ]
                ],
            ]
        ]
    ],

    'testUploadFilesByAgent' => [
        'request' => [
            'url'     => '/merchant_document',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'promoter_address_url',
                'merchant_id'   => '10000000000000'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000'
            ]
        ]
    ],

    'testDocumentUploadAndCheckOcrVerificationStatusSuccess' => [
        'request'  => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type' => ''
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'documents' => [
                ],
            ]
        ]
    ],

    'uploadDocAndCheckOcrSuccess' => [
        'request'  => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'content' => [
                'document_type' => ''
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'documents' => [
                ],
            ]
        ]
    ],

    'testFetchMerchantDocuments' => [
        'request'  => [
            'url'    => '/merchant/documents',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'Address_proof_url' => [
                    [
                        'file_store_id' => 'DM6dXJfU4WzeAF',
                    ]
                ],
                'Aadhar_back'       => [
                    [
                        'file_store_id' => 'DA6dXJfU4WzeAF',
                    ]
                ]
            ],
        ],
    ],

    'testFetchMerchantDocumentsByAdmin' => [
        'request'  => [
            'url'    => '/merchant/documents/1cXSLlUU8V9sXl',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'Address_proof_url' => [
                    [
                        'file_store_id' => 'DM6dXJfU4WzeAF',
                    ]
                ],
                'Aadhar_back'       => [
                    [
                        'file_store_id' => 'DA6dXJfU4WzeAF',
                    ]
                ]
            ],
        ],
    ],

    'testFetchFIRSDocuments' => [
        'request'  => [
            'url'       => '/merchant/firs?month=%s&year=%s',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testFetchFIRSDocumentsUploadedOnFirstDayOfMonth' => [
        'request'  => [
            'url'       => '/merchant/firs?month=%s&year=%s',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testDownloadFIRSDocuments' => [
        'request'  => [
            'url'       => '/merchant/firs/content?month=%s&year=%s&document_id=%s',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testDownloadFIRSDocumentsZIP' => [
        'request' => [
            'url'       => '/merchant/firs/content?month=%s&year=%s',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testDownloadFIRSDocumentsZIPFileNotPresent' => [
        'request' => [
            'url'       => '/merchant/firs/content?month=%s&year=%s',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testFetchFIRSDocumentsWithICICIZippedDocument' => [
        'request'  => [
            'url'       => '/merchant/firs?month=%s&year=%s',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testFetchFIRSDocumentsWithICICIZippedDocumentInCreatedState' => [
        'request'  => [
            'url'       => '/merchant/firs?month=%s&year=%s',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testICICIZipFIRSDocumentsIfNoZipExists' => [
        'request'  => [
            'url'       => '/merchant/firs/collect/cron',
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testICICIZipFIRSDocumentsIfZipAlreadyExists' => [
        'request'  => [
            'url'       => '/merchant/firs/collect/cron',
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testICICIZipFIRSDocumentsIfZipAlreadyExistsForPreviousToPreviousMonth' => [
        'request'  => [
            'url'       => '/merchant/firs/collect/cron',
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],


    'testFetchRBLAndFirstdataFIRSDocuments' => [
        'request'  => [
            'url'       => '/merchant/firs?month=%s&year=%s',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testFetchRBLAndFirstdataFIRSDocumentsWithSummaryFile' => [
        'request'  => [
            'url'       => '/merchant/firs?month=%s&year=%s',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ]
];
