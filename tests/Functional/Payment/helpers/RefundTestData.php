<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testRefund' => [
        'request' => [
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
                'amount' => 50000,
                'currency' => 'INR',
            ],
        ],
    ],

    'testUploadRefundFile' => [
        'request' => [
            'url' => '/batches',
            'method' => 'post',
            'content' => [
                'type' => 'refund',
            ],
        ],
        'response' => [
            'content' => [
                    'id' => 'rfnd_file_6JaB3AUIkCh9kL',
                    'entity' => 'refund_file',
                    'status' =>  'CREATED',
                    'created_at' => 1474032787,
            ],
        ],
    ],

    'testDownloadRefundFile' => [
        'request' => [
            'method' => 'post',
            'content' => [
                'type' => 'refund',
            ],
        ],
        'response' => [

        ],
    ],

    'testUploadRefundFileException' => [
        'request' => [
            'url' => '/batches',
            'method' => 'post',
            'content' => [
                'type' => 'refund',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file does not contain proper values',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FILE_VALIDATION,
        ],
    ],

    'testGetAllRefundFiles' => [
        'request' => [
            'url' => '/batches',
            'method' => 'get',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                    'id' => 'rfnd_file_6JaB3AUIkCh9kL',
                    'entity' => 'refund_file',
                    'status' =>  'CREATED',
                    'created_at' => 1474032787,
            ],
        ],
    ],

    'testRetryRefundFiles' => [
        'request' => [
            'method' => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                    'id' => 'rfnd_file_6JaB3AUIkCh9kL',
                    'entity' => 'refund_file',
                    'status' =>  'CREATED',
                    'created_at' => 1474032787,
            ],
        ],
    ],

    'testRetryRefundFilesWithException' => [
        'request' => [
            'method' => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file is already processed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FILE_ALREADY_PROCESSED,
        ],
    ],

    'testProcessRefundFile' => [
        'request' => [
            'url' => '/batches/process',
            'method' => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                    'id' => 'rfnd_file_6JaB3AUIkCh9kL',
                    'entity' => 'refund_file',
                    'status' =>  'CREATED',
                    'created_at' => 1474032787,
            ],
        ],
    ],


    'testMultipleRefunds' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
                'amount' => 50000,
                'amount_refunded' => 50000,
                'refund_status' => 'full',
                'status' => 'refunded',
                'currency' => 'INR',
            ],
        ],
    ],

    'testRefundWithHigherAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED
        ],
    ],

  'testMultipleRefundsWithHigherAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_UNREFUNDED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_UNREFUNDED
        ],
    ],

    'testRefundOnRefundedPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FULLY_REFUNDED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FULLY_REFUNDED
        ],
    ],

    'testRefundByMerchantOnAuthorizedPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED
        ],
    ],

    // 'testRefundByAdminOnAuthorizedPayment' => [
    //     'response' => [
    //         'content' => [
    //             'entity' => 'refund',
    //         ],
    //     ],
    // ],

    'testRefundWithNegativeAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testRefundWithZeroAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testRefundWithSpacedAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testRefundWithBlankAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testRefundOfMultipleAuthorizedPaymentsForOrder' => [
        'request' => [
            'method'    => 'post',
            'url'       => '/orders/payments/refund',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'total_orders' => 2,
                'order_level_details' => [
                    [
                        'total_payments' => 2,
                        'total_captured_payments' => 0,
                        'refund_details' => [],
                    ],
                    [
                        'total_payments' => 3,
                        'total_captured_payments' => 1,
                        'refund_details' => [
                            'total_authorized_payments' => 2,
                            'total_refunded_payments' => 2,
                            'total_failed_refunds' => 0,
                        ],
                    ],
                ],
            ],
        ],
    ],
];
