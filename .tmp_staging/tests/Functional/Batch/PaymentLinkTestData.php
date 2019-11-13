<?php

use RZP\Error\ErrorCode;
use RZP\Models\Batch\Header;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateBatchOfPaymentLinkType1' => [
        'request'  => [
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
        'request'  => [
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

    'testCreateBatchOfPaymentLinkWithKubernetes' => [
        'request'  => [
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

    'testCreateBatchOfPaymentLinkTypeWithNewHeaderValues' => [
        'request'  => [
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

    'testCreateBatchOfPaymentLinkTypeWithNewHeaderValuesFileRows' => [
        [
            Header::INVOICE_NUMBER   => '#1',
            Header::CUSTOMER_NAME    => 'test',
            Header::CUSTOMER_EMAIL   => 'test@test.test',
            Header::CUSTOMER_CONTACT => '9999998888',
            Header::AMOUNT_IN_PAISE  => 500,
            Header::DESCRIPTION      => 'test payment link',
            Header::EXPIRE_BY        => null,
            Header::PARTIAL_PAYMENT  => 'YES',
        ],
    ],

    'testCreateBatchOfPaymentLinkTypeWithNotes' => [
        'request'  => [
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

    'testCreateBatchOfPaymentLinkTypeWithNotesFileRows' => [
        [
            Header::INVOICE_NUMBER   => '#1',
            Header::CUSTOMER_NAME    => 'test',
            Header::CUSTOMER_EMAIL   => 'test@test.test',
            Header::CUSTOMER_CONTACT => '9999998888',
            Header::AMOUNT_IN_PAISE  => 500,
            Header::DESCRIPTION      => 'test payment link',
            Header::EXPIRE_BY        => null,
            Header::PARTIAL_PAYMENT  => 'YES',
            'notes[key1]'            => 'Notes Value 1',
            'notes[key2]'            => 'Notes Value 2',
        ],
    ],

    'testCreateBatchOfPaymentLinkTypeWithInvalidFile1' => [
        'request'   => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file has invalid headers',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
        ],
    ],

    'testCreateBatchOfPaymentLinkTypeWithInvalidFile2' => [
        'request'   => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file does not have any entries',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_EMPTY,
        ],
    ],

    'testCreateBatchOfPaymentLinkTypeWithInvalidFile3' => [
        'request'   => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'There are validation errors in 1 row of the file',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testProcessPaymentLinkBatchById' => [
        'request'  => [
            'url'     => '/batches/batch_00000000000001/process',
            'method'  => 'post',
            'content' => [
                'sms_notify'   => 1,
                'email_notify' => 0,
                'draft'        => 0,
            ],
        ],
        'response' => [
            'content'     => [
                'id'     => 'batch_00000000000001',
                'entity' => 'batch',
                'type'   => 'payment_link',
                'status' => 'created',
            ],
            'status_code' => 200,
        ],
    ],

    'testBatchFileValidation' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payment_link',
                'draft' => 0,
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 5,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::INVOICE_NUMBER   => '#1',
                        Header::CUSTOMER_NAME    => 'test',
                        Header::CUSTOMER_EMAIL   => 'test@test.test',
                        Header::CUSTOMER_CONTACT => '9999998888',
                        Header::AMOUNT           => 100,
                        Header::DESCRIPTION      => 'test payment link',
                        Header::EXPIRE_BY        => null,
                        Header::PARTIAL_PAYMENT  => 'YES',
                        'notes[key1]'            => 'Notes Value 1',
                        'notes[key2]'            => 'Notes Value 2',
                    ],
                    // Duplicate receipt number will not get detected in the validation api
                    [
                        Header::INVOICE_NUMBER   => '#1',
                        Header::CUSTOMER_NAME    => 'test 2',
                        Header::CUSTOMER_EMAIL   => 'test-2@test.test',
                        Header::CUSTOMER_CONTACT => '9999997777',
                        Header::AMOUNT           => 100,
                        Header::DESCRIPTION      => 'test payment link - 2',
                        Header::EXPIRE_BY        => null,
                        Header::PARTIAL_PAYMENT  => 'NO',
                        'notes[key1]'            => null,
                        'notes[key2]'            => null,
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
                        'notes[key1]'            => 'Notes Value 1 - second',
                        'notes[key2]'            => 'Notes Value 2 - second',
                    ],
                ],
            ],
        ],
    ],

    'testBatchCreateForUploadedFile' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'  => 'payment_link',
                'name'  => 'My batch entity',
                'draft' => 0,
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

    'testBatchCreateForwardingToNewBatchService' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'  => 'payment_link',
                'name'  => 'My batch entity',
                'draft' => 0,
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreateBatchWithHumanReadableExpireBy' => [
        'request'  => [
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

    'testCreateBatchWithHumanReadableExpireByFileRows' => [
        // Note: EXPIRE_BY attribute is updated in test method after Carbon instance is mocked.
        [
            Header::INVOICE_NUMBER   => '1',
            Header::CUSTOMER_NAME    => null,
            Header::CUSTOMER_EMAIL   => null,
            Header::CUSTOMER_CONTACT => '9999998881',
            Header::AMOUNT           => 500,
            Header::DESCRIPTION      => 'Test payment link',
            Header::PARTIAL_PAYMENT  => 'YES',
        ],
        [
            Header::INVOICE_NUMBER   => '2',
            Header::CUSTOMER_NAME    => null,
            Header::CUSTOMER_EMAIL   => null,
            Header::CUSTOMER_CONTACT => '9999998882',
            Header::AMOUNT           => 500,
            Header::DESCRIPTION      => 'Test payment link',
            Header::PARTIAL_PAYMENT  => 'YES',
        ],
        [
            Header::INVOICE_NUMBER   => '3',
            Header::CUSTOMER_NAME    => null,
            Header::CUSTOMER_EMAIL   => null,
            Header::CUSTOMER_CONTACT => '9999998885',
            Header::AMOUNT           => 500,
            Header::DESCRIPTION      => 'Test payment link',
            Header::PARTIAL_PAYMENT  => 'YES',
        ],
    ],

    'testPaymentLinkStatsOfBatch' => [
        'request'  => [
            'url'    => '/batches/batch_00000000000001/stats',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'type'  => 'payment_link',
                'stats' => [
                    'batch_total'   => 6,
                    'issued_count'  => 5,
                    'created_count' => 5,
                    'paid_count'    => 2,
                    'expired_count' => 1,
                ],
            ],
        ],
    ],

    'testPaymentLinkStatsOfBatchInputData' => [
        'attributes' => [
            [
                'invoiceAttributes' => [
                    'id'       => '1000001invoice',
                    'batch_id' => '00000000000001',
                    'order_id' => '100000001order',
                    'status'   => 'issued',
                ],
                'orderAttributes'   => [
                    'id' => '100000001order',
                ],
            ],
            [
                'invoiceAttributes' => [
                    'id'       => '1000002invoice',
                    'batch_id' => '00000000000001',
                    'order_id' => '100000002order',
                    'status'   => 'paid',
                ],
                'orderAttributes'   => [
                    'id' => '100000002order',
                ],
            ],
            [
                'invoiceAttributes' => [
                    'id'       => '1000003invoice',
                    'batch_id' => '00000000000001',
                    'order_id' => '100000003order',
                    'status'   => 'paid',
                ],
                'orderAttributes'   => [
                    'id' => '100000003order',
                ],
            ],
            [
                'invoiceAttributes' => [
                    'id'       => '1000004invoice',
                    'batch_id' => '00000000000001',
                    'order_id' => '100000004order',
                    'status'   => 'expired',
                ],
                'orderAttributes'   => [
                    'id' => '100000004order',
                ],
            ],
            [
                'invoiceAttributes' => [
                    'id'       => '1000005invoice',
                    'batch_id' => '00000000000001',
                    'order_id' => '100000005order',
                    'status'   => 'partially_paid',
                ],
                'orderAttributes'   => [
                    'id' => '100000005order',
                ],
            ],
            // Payment links created via batch won't be in draft
            // state. This is however, added for test purpose only
            [
                'invoiceAttributes' => [
                    'id'       => '1000006invoice',
                    'batch_id' => '00000000000001',
                    'order_id' => '100000006order',
                    'status'   => 'draft',
                ],
                'orderAttributes'   => [
                    'id' => '100000006order',
                ],
            ],
        ],
    ],

    'testGetStatsOfInvalidType' => [
        'request'   => [
            'url'    => '/batches/batch_00000000000001/stats',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Batch stats are not available for this batch type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_STATS_NOT_SUPPORTED_FOR_TYPE,
        ],
    ],

    'testFetchBatchesOfPaymentLinkTypeWithConfig' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'get',
            'content' => [
                'type'        => 'payment_link',
                'with_config' => '1',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'id'     => 'batch_00000000000002',
                        'type'   => 'payment_link',
                        'status' => 'created',
                        'config' => [
                            'sms_notify'   => '0',
                            'email_notify' => '0',
                        ],
                    ],
                    [
                        'id'     => 'batch_00000000000001',
                        'type'   => 'payment_link',
                        'status' => 'created',
                        'config' => [
                            'sms_notify'   => '1',
                            'email_notify' => '0',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchBatchOfPaymentLinkTypeIfBatchServiceIsDown' => [
        'request'  => [
            'url'     => '/batches/batch_C7e2YqUIpZ2KwZ',
            'method'  => 'get',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [
                'id'     => 'batch_C7e2YqUIpZ2KwZ',
                'type'   => 'payment_link',
                'status' => 'created',
            ],
        ],
    ],

    'testFetchBatchOfTypePaymentLinkFromBatchService' => [
        'request'  => [
            'url'     => '/batches/batch_C3fzDCb4hA4F6b',
            'method'  => 'get',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [
                'id'     => 'batch_C3fzDCb4hA4F6b',
                'type'   => 'payment_link',
                'status' => 'processed',
            ],
        ],
    ],

    'testGetBatchByEPosRole' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'get',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                ],
            ],
        ],
    ],

    'testCreateBatchOfPaymentLinkTypeByEposRole' => [
        'request'  => [
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

    'testCreateBatchOfPaymentLinkTypeByRefund' => [
        'request'   => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'refund',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FORBIDDEN,
                ],
            ],
            'status_code' => 403,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN,
        ],
    ],

    'testGetBatchByEPosRoleExperimentOff' => [
        'request'   => [
            'url'     => '/batches',
            'method'  => 'get',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FORBIDDEN,
                ],
            ],
            'status_code' => 403,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN,
        ],
    ],

    'testGetBatchByIdByEPosRole' => [
        'request'  => [
            'url'     => '/batches/batch_00000000000002',
            'method'  => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'batch',
                'id'          => 'batch_00000000000002',
                'type'        => 'payment_link',
                'total_count' => 1,
            ],
        ],
    ],

    'testGetBatchByIdByEPosRoleNonPaymentLinkType' => [
        'request'   => [
            'url'     => '/batches/batch_00000000000002',
            'method'  => 'get',
            'content' => [
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FORBIDDEN,
                ],
            ],
            'status_code' => 403,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN,
        ],
    ],
];
