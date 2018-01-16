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

    'testRefundWithReceipt' => [
        'request' => [
            'content' => [
                'receipt' => '1234'
            ]
        ],
        'response' => [
            'content' => [
                'entity'   => 'refund',
                'amount'   => 50000,
                'currency' => 'INR',
                'receipt'  => '1234'
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

    'testRefundWithFloatAmountString' => [
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

    'testRefundWithFloatAmount' => [
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

    'testRefundAuthorizedPaymentsOfPaidOrders' => [
        'request' => [
            'method'    => 'post',
            'url'       => '/orders/payments/refund',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'count'      => 5,
                // 'total_time' => '1 secs',
                'failed_ids' => [],
            ],
        ],
    ],

    'testRefundCreateOnGatewayForMissingRefunds' => [
        'request' => [
            'method'    => 'post',
            'url'       => '/refunds/billdesk/create_record',
            'content'   => [],
        ],
        'response' => [
            'content'   => []
        ]
    ],

    'testCreateMissingRefundTransaction' => [
        'request' => [
            'method'    => 'post',
            'url'       => '/refunds/gateway_refunded/transaction',
            'content'   => [],
        ],
        'response' => [
            'content'   => [
                'total_count' => 1,
                'success_count' => 1,
                'failures_count' => 0,
                'failed_refunds' => [],
            ]
        ]
    ],

    'testRefundValidationOnWrongGateway' => [
        'request' => [
            'url'       => '/refunds/wallet_airtelmoney/validate',
            'content'   => [],
            'method'    => 'post',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_GATEWAY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_GATEWAY
        ],
    ],

    'testRefundDisputedPayment' => [
        'request'   => [],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_UNDER_DISPUTE_CANNOT_BE_REFUNDED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_UNDER_DISPUTE_CANNOT_BE_REFUNDED
        ],
    ],

    'testRefundIciciDebitCard' => [
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
];
