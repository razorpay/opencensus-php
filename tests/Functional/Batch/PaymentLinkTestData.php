<?php

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
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

    'testCreateBatchOfPaymentLinkTypeWithInvalidFile3' => [
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
                    'description' => 'The uploaded batch payment link file does not contain proper values',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_PAYMENT_LINK_FILE_ERRORS,
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
                'draft'=> 0,
            ],
        ],
        'response' => [
            'content' => [
                'processable_count'         => 5,
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
                        Header::PARTIAL_PAYMENT  => 'YES',
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
                    ],
                ],
            ],
        ],
    ],

    'testBatchCreateForUploadedFile'    => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
                'name' => 'My batch entity',
                'draft'=> 0,
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

    'testCreateBatchOfPaymentLinkTypeWithHumanReadableExpireBy' => [
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

    'testCreateBatchOfPaymentLinkTypeWithHumanReadableExpireByFileRows' => [
        [
            Header::INVOICE_NUMBER   => '1',
            Header::CUSTOMER_NAME    => null,
            Header::CUSTOMER_EMAIL   => null,
            Header::CUSTOMER_CONTACT => '9999998881',
            Header::AMOUNT           => 500,
            Header::DESCRIPTION      => 'Test payment link',
            Header::EXPIRE_BY        => Carbon::now(Timezone::IST)->addDays(1)->format('d/m/Y H:i:s'),
            Header::PARTIAL_PAYMENT  => 'YES',
        ],
        [
            Header::INVOICE_NUMBER   => '2',
            Header::CUSTOMER_NAME    => null,
            Header::CUSTOMER_EMAIL   => null,
            Header::CUSTOMER_CONTACT => '9999998882',
            Header::AMOUNT           => 500,
            Header::DESCRIPTION      => 'Test payment link',
            Header::EXPIRE_BY        => Carbon::now(Timezone::IST)->addDays(2)->format('d/m/Y H:i:s'),
            Header::PARTIAL_PAYMENT  => 'YES',
        ],
        [
            Header::INVOICE_NUMBER   => '3',
            Header::CUSTOMER_NAME    => null,
            Header::CUSTOMER_EMAIL   => null,
            Header::CUSTOMER_CONTACT => '9999998885',
            Header::AMOUNT           => 500,
            Header::DESCRIPTION      => 'Test payment link',
            Header::EXPIRE_BY        => Carbon::now(Timezone::IST)->addDays(3)->getTimestamp(),
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
                    'batch_total'   => 4,
                    'issued_count'  => 1,
                    'paid_count'    => 2,
                    'expired_count' => 1,
                ],
            ],
        ],
    ],

    'testPaymentLinkStatsOfBatchInputData'  => [
        'attributes' => [
            [
                'invoiceAttributes' => [
                    'id'                    => '1000001invoice',
                    'batch_id'              => '00000000000001',
                    'order_id'              => '100000001order',
                    'status'                => 'issued',
                ],
                'orderAttributes'   => [
                    'id'                    => '100000001order'
                ],
            ],
            [
                'invoiceAttributes' => [
                    'id'                    => '1000002invoice',
                    'batch_id'              => '00000000000001',
                    'order_id'              => '100000002order',
                    'status'                => 'paid',
                ],
                'orderAttributes'   => [
                    'id'                    => '100000002order'
                ],
            ],
            [
                'invoiceAttributes' => [
                    'id'                    => '1000003invoice',
                    'batch_id'              => '00000000000001',
                    'order_id'              => '100000003order',
                    'status'                => 'paid',
                ],
                'orderAttributes'   => [
                    'id'                    => '100000003order'
                ],
            ],
            [
                'invoiceAttributes' => [
                    'id'                    => '1000004invoice',
                    'batch_id'              => '00000000000001',
                    'order_id'              => '100000004order',
                    'status'                => 'expired',
                ],
                'orderAttributes'   => [
                    'id'                    => '100000004order'
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
        'request'   => [
            'url'    => '/batches',
            'method' => 'get',
            'content'=> [
                'type'        => 'payment_link',
                'with_config' => '1',
            ],
        ],
        'response'  => [
            'content'     => [
                'entity'        => 'collection',
                'count'         => 2,
                'items'         => [
                    [
                        'id'        => 'batch_00000000000002',
                        'type'      => 'payment_link',
                        'status'    => 'created',
                        'config'    => [
                            'sms_notify'    => '0',
                            'email_notify'  => '0',
                        ],
                    ],
                    [
                        'id'        => 'batch_00000000000001',
                        'type'      => 'payment_link',
                        'status'    => 'created',
                        'config'    => [
                            'sms_notify'    => '1',
                            'email_notify'  => '0',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
