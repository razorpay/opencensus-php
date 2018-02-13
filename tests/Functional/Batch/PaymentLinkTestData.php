<?php

use RZP\Error\ErrorCode;
use RZP\Models\Batch\Header;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreateBatchOfPaymentLinkType1' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'payment_link',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => null,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testCreateBatchOfPaymentLinkType2' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreateBatchOfPaymentLinkTypeWithInvalidFile1' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file has invalid headers',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
        ],
    ],

    'testCreateBatchOfPaymentLinkTypeWithInvalidFile2' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file does not have any entries',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_EMPTY,
        ],
    ],

    'testProcessPaymentLinkBatchById' => [
        'request' => [
            'url'     => '/batches/batch_00000000000001/process',
            'method'  => 'post',
            'content' => [
                'sms_notify'   => 1,
                'email_notify' => 0,
                'draft'        => 0,
            ],
        ],
        'response' => [
            'content' => [
                'id'     => 'batch_00000000000001',
                'entity' => 'batch',
                'type'   => 'payment_link',
                'status' => 'created',
            ],
            'status_code' => 200,
        ],
    ],

    'testBatchFileValidation' => [
        'request' => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count'         => 3,
                'error_count'               => 0,
                'parsed_entries'            => [
                    [
                        Header::INVOICE_NUMBER   => '#1',
                        Header::CUSTOMER_NAME    => 'test',
                        Header::CUSTOMER_EMAIL   => 'test@test.test',
                        Header::CUSTOMER_CONTACT => '9999998888',
                        Header::AMOUNT           => 100,
                        Header::DESCRIPTION      => 'test payment link',
                        Header::EXPIRE_BY        => null,
                        Header::PARTIAL_PAYMENT  => null,
                        Header::ERROR_CODE       => null,
                        Header::ERROR_DESCRIPTION=> null,
                    ],
                    [
                        Header::INVOICE_NUMBER   => '#1',
                        Header::CUSTOMER_NAME    => 'test 2',
                        Header::CUSTOMER_EMAIL   => 'test-2@test.test',
                        Header::CUSTOMER_CONTACT => '9999997777',
                        Header::AMOUNT           => 100,
                        Header::DESCRIPTION      => 'test payment link - 2',
                        Header::EXPIRE_BY        => null,
                        Header::PARTIAL_PAYMENT  => 0,
                        Header::ERROR_CODE       => null,
                        Header::ERROR_DESCRIPTION=> null,
                    ],
                    [
                        Header::INVOICE_NUMBER   => '#3',
                        Header::CUSTOMER_NAME    => 'test 3',
                        Header::CUSTOMER_EMAIL   => 'test-3@test.test',
                        Header::CUSTOMER_CONTACT => '9999996666',
                        Header::AMOUNT           => 100,
                        Header::DESCRIPTION      => 'test payment link - 3',
                        Header::EXPIRE_BY        => null,
                        Header::PARTIAL_PAYMENT  => null,
                        Header::ERROR_CODE       => null,
                        Header::ERROR_DESCRIPTION=> null,
                    ],
                ],
            ],
        ],
    ],

    'testBatchCreateForUploadedFile'    => [
        'request' => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count'         => 3,
                'error_count'               => 0,
                'parsed_entries'            => [
                    [
                        Header::INVOICE_NUMBER   => '#1',
                        Header::CUSTOMER_NAME    => 'test',
                        Header::CUSTOMER_EMAIL   => 'test@test.test',
                        Header::CUSTOMER_CONTACT => '9999998888',
                        Header::AMOUNT           => 100,
                        Header::DESCRIPTION      => 'test payment link',
                        Header::EXPIRE_BY        => null,
                        Header::PARTIAL_PAYMENT  => null,
                        Header::ERROR_CODE       => null,
                        Header::ERROR_DESCRIPTION=> null,
                    ],
                    [
                        Header::INVOICE_NUMBER   => '#1',
                        Header::CUSTOMER_NAME    => 'test 2',
                        Header::CUSTOMER_EMAIL   => 'test-2@test.test',
                        Header::CUSTOMER_CONTACT => '9999997777',
                        Header::AMOUNT           => 100,
                        Header::DESCRIPTION      => 'test payment link - 2',
                        Header::EXPIRE_BY        => null,
                        Header::PARTIAL_PAYMENT  => 0,
                        Header::ERROR_CODE       => null,
                        Header::ERROR_DESCRIPTION=> null,
                    ],
                    [
                        Header::INVOICE_NUMBER   => '#3',
                        Header::CUSTOMER_NAME    => 'test 3',
                        Header::CUSTOMER_EMAIL   => 'test-3@test.test',
                        Header::CUSTOMER_CONTACT => '9999996666',
                        Header::AMOUNT           => 100,
                        Header::DESCRIPTION      => 'test payment link - 3',
                        Header::EXPIRE_BY        => null,
                        Header::PARTIAL_PAYMENT  => null,
                        Header::ERROR_CODE       => null,
                        Header::ERROR_DESCRIPTION=> null,
                    ],
                ],
            ],
        ],
    ],

    'testBatchCreateForUploadedFileAfterFileUpload'    => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
                'name' => 'My batch entity',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'name'             => 'My batch entity',
                'type'             => 'payment_link',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => null,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],
];
