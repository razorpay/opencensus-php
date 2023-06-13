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

    'testRefundWhenDisabledOnMerchant' => [
        'request'   => [
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_REFUND_NOT_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REFUND_NOT_ALLOWED,
        ],
    ],

    'testRefundOnDisabledMethod' => [
        'request'   => [
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
        ],
    ],

    'testRefundWhenDisabledOnMerchantForCards' => [
        'request'   => [
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CARD_REFUND_NOT_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_REFUND_NOT_ALLOWED,
        ],
    ],

    'testNetBankRefundWhenDisabledOnMerchantForCards' => [
        'request'  => [
        ],
        'response' => [
            'content' => [
                'entity'   => 'refund',
                'amount'   => 50000,
                'currency' => 'INR',
            ],
        ],
    ],

    'testFailedVoidRefundGatewayReversalAbsent' => [
        'request' => [
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_REVERSAL_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_REVERSAL_NOT_SUPPORTED,
        ],
    ],

    'testSuccessfulPartialRefundOnCapturedPaymentWithVoidRefund' => [
        'request' => [
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
                'amount' => 25000,
                'currency' => 'INR',
            ],
        ],
    ],

    'testSuccessfulVoidRefund' => [
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

    'testSuccessfulVoidRefundForMYR' => [
        'request' => [
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
                'amount' => 50000,
                'currency' => 'MYR',
            ],
        ],
    ],

  'testFailVoidPartialRefund' => [
        'request' => [
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_REFUND_PARTIAL_VOID_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REFUND_PARTIAL_VOID_NOT_SUPPORTED,
        ],
    ],

  'testSuccessfulRefundOnCapturedPaymentWithVoidRefund' => [
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

    'testVoidRefundFeatureDeactivated' => [
        'request' => [
        ],
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
        ],
    ],

    'testRefundEditStatus' => [
        'request'  => [
            'content' => [
                'status'    => 'initiated',
                'reference1' => 'abcd'
            ],
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'status' => 'initiated',
            ],
        ],
    ],

    'testRefundFetchDetailsForCustomerFromRefundIdAndPaymentId' => [
        'request' => [
            'method' => 'GET',
            'url' => '/customer/refund',
            'content' => [
                'refund_id' => 'dummy',
                'captcha' => 'dummy',
                'mode' => 'test',
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testRefundFetchDetailsForCustomerFromReservationId' => [
        'request' => [
            'method' => 'GET',
            'url' => '/customer/refund',
            'content' => [
                'reservation_id' => 'dummy',
                'captcha' => 'dummy',
                'mode' => 'test',
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testRefundEditStatusToFailedFromInitiated' => [
        'request'  => [
            'content' => [
                'status'    => 'failed',
                'reference1' => 'abcdFailed'
            ],
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'status' => 'failed',
            ],
        ],
    ],

    'testRefundEditStatustoInitiatedFromFailed' => [
        'request'  => [
            'content' => [
                'status'    => 'initiated',
                'reference1' => 'abcdInitiated'
            ],
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'status' => 'initiated',
            ],
        ],
    ],

    'testRefundEditStatusToProcessedFromFailed' => [
        'request'  => [
            'content' => [
                'status'    => 'processed',
                'reference1' => 'abcdProcessed'
            ],
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'status' => 'processed',
            ],
        ],
    ],
    'testRefundEditStatusWithoutReference' => [
        'request'  => [
            'content' => [
                'status'    => 'initiated'
            ],
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'status' => 'initiated',
            ],
        ],
    ],
    'testRefundEditInvalidStatus' => [
        'request'  => [
            'content' => [
                'status' => 'abcd',
            ],
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected status is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRefundEditStatusFailed' => [
        'request'  => [
            'content' => [
                'status' => 'initiated',
            ],
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Refund can not be updated to this state',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REFUND_INVALID_STATE_UPDATE
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
                    'description' => PublicErrorDescription::BAD_REQUEST_TOTAL_REFUND_AMOUNT_IS_GREATER_THAN_THE_PAYMENT_AMOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TOTAL_REFUND_AMOUNT_IS_GREATER_THAN_THE_PAYMENT_AMOUNT
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

    'testRefundByMYMerchantOnAuthorizedPayment' => [
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

    'testRefundByMerchantForPosPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED
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

    'testRefundWithAmountLessThanINR1' => [
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

    'testFetchRefundsProxyAuthExpanded' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testFetchRefundsProxyAuth' => [
        'request' => [],
        'response'  => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testCreateRefundProxyAuth' => [
        'request'   => [],
        'response'  => [
            'content' => [
                'entity' => 'refund',
                'amount' => 1000000,
                'currency' => 'INR',
            ],
            'status_code' => 200,
        ],
    ],

    'testPaymentRefundCreateDataProxyAuth' => [
        'request'   => [
            'method'  => 'get',
            'url'     => '/payments',
            'content' => [
                'dashboard_flag' => [
                    'refund_create_data',
                ],
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testPaymentRefundCreateDataProxyAuthOnDeletedTerminal' => [
        'request'   => [
            'method'  => 'get',
            'url'     => '/payments',
            'content' => [
                'dashboard_flag' => [
                    'refund_create_data',
                ],
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreateRefundProxyAuthInvalidRole' => [
        'request'   => [],
        'response'  => [
            'content' => [
                'error'       => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
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

    'testRefundDirectPaymentMultipleDisputesNonFraudOpen' => [
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

    'tpvPaymentNetbankingEntity' => [
        'bank_payment_id' => '99999999',
        'received'        => true,
        'bank_name'       => 'SBIN',
        'status'          => 'Ok',
    ],

    'nonTpvPaymentNetbankingEntity' => [
        'bank_payment_id' => '99999999',
        'received'        => true,
        'bank_name'       => 'SBIN',
        'status'          => 'Ok',
    ],

    'testFetchRefundReversal' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/refunds',
            'content' => [
                'expand' => ['reversal'],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'tpvPayment' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank'           => 'SBIN',
                'account_number' => '04030403040304',
                'payer_name'     => 'test',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],

    'paymentOrderAccountDetailsAvailable' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank'           => 'SBIN',
                'account_number' => '04030403040304',
                'payer_name'     => 'test',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],

    'testRefundSettledBy' => [
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

    'testRefundSettledByWithZeroBalance' => [
        'request'   => [
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the refund operation. You can add funds to your account from your Razorpay dashboard or capture new payments.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE
        ]
    ],

    'testDirectSettlementRefundSettledBy' => [
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

    'testDirectSettlementRefundSettledByWithZeroBalance' => [
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

    'testDirectSettlementInstantRefundSettledByWithZeroBalance' => [
        'request'   => [
            'content' => [
                'speed' => 'optimum',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the refund operation. You can add funds to your account from your Razorpay dashboard or capture new payments.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE
        ]
    ],

    'testFetchRefundPublicStatus' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/refunds/',
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
                'status' => 'pending'
            ],
        ],
    ],


    'testRefundWithZeroBalance' => [
        'request'   => [
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the refund operation. You can add funds to your account from your Razorpay dashboard or capture new payments.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE
        ]
    ],

    'testRefundWithZeroBalanceRefundFlow' => [
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

    'testRefundWithZeroBalanceWithReserveBalanceCrossingThreshold' => [
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

    'testRefundWithNegativeBalance' => [
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

    'testRefundWithNegativeBalanceMultipleBreach' => [
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

    'testRefundWithNegativeBalanceAndReserveBalance' => [
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

    'testRefundWithReserveBalance' => [
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

    'testRefundWithZeroRefundCredits' => [
        'request'   => [
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough credits to carry out the refund operation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS
        ]
    ],

    'testRefundWithZeroRefundCreditsRefundFlow' => [
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

    'testRefundWithNegativeRefundCredits' => [
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

    'testRefundWithNegativeRefundCreditsAndReserveBalance' => [
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

    'testRefundWithZeroBalanceInternationalCurrency' => [
        'request' => [
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
                'amount' => 200,
                'currency' => 'USD',
            ],
        ],
    ],

    'testRefundWithNegativeBalanceAndInternationalCurrency' => [
        'request' => [
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
                'amount' => 200,
                'currency' => 'USD',
            ],
        ],
    ],

    'testRefundWithNegativeRefundCreditsAndInternationalCurrency' => [
        'request' => [
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
                'amount' => 200,
                'currency' => 'USD',
            ],
        ],
    ],

    'testRefundWithNegativeAndReserveBalanceAndInternationalCurrency' => [
        'request'   => [
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your Negative Balance Limit has reached its maximum. Please Add funds to your account.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED
        ]
    ],

    'testInstantRefundsSupportedOnNonRZPOrg' => [
        'request'   => [
            'content' => [
                'speed' => 'optimum',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'refund',
                'amount'          => 50000,
                'currency'        => 'INR',
                'notes'           => [],
                'receipt'         => null,
                'acquirer_data'   => [
                    'arn' => null
                ],
                'batch_id'        => null,
                'status'          => 'pending',
                'speed_processed' => 'instant',
                'speed_requested' => 'optimum'
            ],
            'status_code' => 200,
        ],
    ],

    'testInstantRefundsSupportedOnNonRZPOrgWithDefaultPricing' => [
        'request'   => [
            'content' => [
                'speed' => 'optimum',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'refund',
                'amount'          => 50000,
                'currency'        => 'INR',
                'notes'           => [],
                'receipt'         => null,
                'acquirer_data'   => [
                    'arn' => null
                ],
                'batch_id'        => null,
                'status'          => 'pending',
                'speed_processed' => 'instant',
                'speed_requested' => 'optimum'
            ],
            'status_code' => 200,
        ],
    ],

    'testInstantRefundsNotSupportedForFeatureNotEnabledMerchants' => [
        'request'   => [
            'content' => [
                'speed' => 'optimum',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Instant refund not supported for the payment',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_INSTANT_REFUND_NOT_SUPPORTED
        ]
    ],

    'testFailVoidRefundOnReverseUnsupportedAcquirer' => [
        'request' => [
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_REVERSAL_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_REVERSAL_NOT_SUPPORTED,
        ],
    ],

    'testSuccessVoidRefundOnReverseSupportedAcquirer' => [
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

    'testFailVoidRefundOnNullAcquirer' => [
        'request' => [
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_REVERSAL_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_REVERSAL_NOT_SUPPORTED,
        ],
    ],

    'scroogeRetryWithVerify' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/scrooge/refunds/retry/with_verify',
            'content' => [
                'refund_ids' => []
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'scroogeRetryWithoutVerify' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/scrooge/refunds/retry/without_verify',
            'content' => [
                'refund_ids' => []
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'scroogeRetryViaSourceFundTransfers' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/scrooge/refunds/retry/source_fund_transfers',
            'content' => [
                'refund_ids' => []
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'scroogeRetryViaCustomFundTransfers' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/scrooge/refunds/retry/custom_fund_transfers',
            'content' => [
                'refund_ids' => []
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'scroogeRetryViaCustomFundTransfersBatch' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/scrooge/refunds/retry/custom_fund_transfers/batch',
            'content' => [
                'refund_ids' => []
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'updateRefundNotes' => [
        'request' => [
            'method'  => 'patch',
            'url'     => '/refunds/{id}',
            'content' => [
                'notes' => [
                    'scrooge' => 'welcome'
                ]
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testRefundFallbackWithZeroBalance' => [
        'request'   => [
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Insufficient balance to issue refund. Please add refund credits through \'My Account\' section or capture new payments.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE_FALLBACK
        ]
    ],

    'testRefundFallbackWithZeroBalanceAndNonZeroCredits' => [
        'request'   => [
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the refund operation. You can add funds to your account from your Razorpay dashboard or capture new payments.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE
        ]
    ],

    'testRefundFallbackWithZeroRefundCredits' => [
        'request'   => [
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough credits to carry out the refund operation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS
        ]
    ],

    'testCronRetrySuccessForNormalRefundReversalWithCredits' => [
        'request' => [
            'url' => '/ledger_outbox/retry',
            'method' => 'POST',
            'content' => [
                'limit'        =>  1,
            ]
        ],
        'response' => [
            'content' => [
                'successful entries count' => 1,
                'failed entries count' =>  0,
            ],
        ]
    ],

    'testCronRetryForNormalRefundReversalWithCreditsUpdateRetryCount' => [
        'request' => [
            'url' => '/ledger_outbox/retry',
            'method' => 'POST',
            'content' => [
                'limit'        =>  1,
            ]
        ],
        'response' => [
            'content' => [
                'successful entries count' => 0,
                'failed entries count' =>  1,
            ],
        ]
    ],

    'testCronRetryFailureForNormalRefundReversalWithCreditsWithDuplicateError' => [
        'request' => [
            'url' => '/ledger_outbox/retry',
            'method' => 'POST',
            'content' => [
                'limit'        =>  1,
            ]
        ],
        'response' => [
            'content' => [
                'successful entries count' => 0,
                'failed entries count' =>  1,
            ],
        ]
    ],

    'testCronRetryFailureForNormalRefundReversalWithCreditsWithExhaustedRetries' => [
        'request' => [
            'url' => '/ledger_outbox/retry',
            'method' => 'POST',
            'content' => [
                'limit'        =>  1,
            ]
        ],
        'response' => [
            'content' => [
                'successful entries count' => 0,
                'failed entries count' =>  1,
            ],
        ]
    ],

    'testVirtualRefundReversalFaliureWithInvalidInput' => [
        'request' => [
            'url' => '/refunds/reversal_create',
            'method' => 'POST',
            'content' => [
                "journal_id"               => "LReSY2s4HTYIOx",
                "payment_id"               => "LRX0c0kDMUfCsC",
                "refund_id"                => "LRei1RkDqc1WBd",
                "merchant_id"              => "10000000000000",
                "speed_decisioned"         => "normal",
                "base_amount"              => "10000",
                "tax"                      => "0",
                "fee"                      => "0",
                "fee_only_reversal"        =>  0,
                "currency"                 => "INR",
                "created_at"               => "1678851765",
            ]
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testVirtualRefundReversalFaliureWithReverseShadowNotEnabled' => [
        'request' => [
            'url' => '/refunds/reversal_create',
            'method' => 'POST',
            'content' => [
                "journal_id"               => "LReSY2s4HTYIOx",
                "payment_id"               => "LRX0c0kDMUfCsC",
                "refund_id"                => "LRei1RkDqc1WBd",
                "merchant_id"              => "10000000000000",
                "speed_decisioned"         => "normal",
                "base_amount"              => "10000",
                "tax"                      => "0",
                "fee"                      => "0",
                "fee_only_reversal"        => 0,
                "currency"                 => "INR",
                "created_at"               => "1678851765",
                "gateway"                  => "hdfc",
            ]
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_REFUND_REVERSAL_NOT_APPLICABLE
        ],
    ],

    'testVirtualRefundReversalFaliureWithDuplicateReversal' => [
        'request' => [
            'url' => '/refunds/reversal_create',
            'method' => 'POST',
            'content' => [
                "journal_id"               => "LReSY2s4HTYIOx",
                "payment_id"               => "LRX0c0kDMUfCsC",
                "refund_id"                => "LRei1RkDqc1WBd",
                "merchant_id"              => "10000000000000",
                "speed_decisioned"         => "normal",
                "base_amount"              => "10000",
                "tax"                      => "0",
                "fee"                      => "0",
                "fee_only_reversal"        => 0,
                "currency"                 => "INR",
                "created_at"               => "1678851765",
                "gateway"                  => "hdfc",
            ]
        ],
        'response'  => [
            'content'     => [
                "refund_id" => "LRei1RkDqc1WBd",
                "reversal_id" => "LRei1RkDqc1KPT",
                "is_duplicate" => true
            ],
            'status_code' => 200,
        ],
    ],

    'testVirtualRefundReversalSuccess' => [
        'request' => [
            'url' => '/refunds/reversal_create',
            'method' => 'POST',
            'content' => [
                'journal_id'               => 'LP6jffEeZejP3v',
                'payment_id'               => 'LRX0c0kDMUfCsC',
                'refund_id'                => 'LRei1RkDqc1WBd',
                'merchant_id'              => '10000000000000',
                'speed_decisioned'         => 'normal',
                'base_amount'              => '10000',
                'fee'                      => '0',
                'tax'                      => '0',
                'fee_only_reversal'        => 0,
                'currency'                 => 'INR',
                'created_at'               => '1678851765',
                'gateway'                  => 'hdfc',
            ]
        ],
        'response'  => [
            'content'     => [
                "refund_id" => "LRei1RkDqc1WBd",
                "is_duplicate" => false
            ],
            'status_code' => 200,
        ],
    ],

    'testKafkaAckSuccessForVirtualRefundReversalEvent' => [
        'request' => [
            'url' => '/refunds/reversal_create',
            'method' => 'POST',
            'content' => [
                "journal_id"               => "LP6jffEeZejP3v",
                "payment_id"               => "LRX0c0kDMUfCsC",
                "refund_id"                => "LRei1RkDqc1WBd",
                "merchant_id"              => "10000000000000",
                "speed_decisioned"         => "normal",
                "base_amount"              => "10000",
                "tax"                      => "0",
                "fee"                      => "0",
                "fee_only_reversal"        => 0,
                "currency"                 => "INR",
                "created_at"               => "1678851765",
                "gateway"                  => "hdfc",
            ]
        ],
        'response'  => [
            'content'     => [
                "refund_id" => "LRei1RkDqc1WBd",
                "is_duplicate" => false
            ],
            'status_code' => 200,
        ],
    ],
];
