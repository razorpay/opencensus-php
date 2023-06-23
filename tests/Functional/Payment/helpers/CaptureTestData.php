<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCapture' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testCaptureWithOrderOutbox' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testBulkCapture' => [
        'response' => [
            'content' => [
                'count'   => 0,
                'success' => 0,
                'failure' => 0,
                'failure_payments' => []
            ],
        ],
    ],

    'testCaptureTwice' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED
        ],
    ],

    'testCaptureWithGatewayCapturedTrue' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23600,
                'tax'               => 3600,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testCaptureWithDifferentAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH
        ],
    ],

    'testCaptureWithLessAmountThanAuth' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 10000
                ],
            ],
    ],

    'testCaptureWithMoreAmountThanAuth' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH
        ],

    ],

    'testCaptureWithRandomId' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testCaptureAfterRefund' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED
        ],
    ],

    'testCaptureWithNoAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCaptureWithZeroAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCaptureWithNegativeAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCaptureWithMinAmountAllowedMinusOne' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCaptureFailedWithoutQueue' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => 'RZP\Exception\RuntimeException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR
        ],
    ],

    'testCaptureTimeoutWithoutQueueNonHdfc' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 504,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayTimeoutException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT
        ],
    ],

    'testCaptureWithMinAmountAllowed' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 100
                ],
            ],
    ],

    'testCaptureWithOverflowingAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testTransactionOnCaptureWithFeeCreditForPrepaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23600,
                'tax'               => 3600,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testCreditTransactionWithFeeCreditForPrepaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23600,
                'tax'               => 3600,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testCreditTransactionWithFeeCreditWithOldFlowForPrepaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23600,
                'tax'               => 3600,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],


    'testTransactionOnCaptureWithAmountCreditForPrepaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 24000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 0,
                'tax'               => 0,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureWithAmountCreditLessThanAmountForPrepaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testCreditTransactionWithAmountCreditForPrepaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 0,
                'tax'               => 0,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testCreditTransactionWithAmountCreditWithOldFlowForPrepaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 5000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 0,
                'tax'               => 0,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureWithPaymentFeeBearerCustomerMerchantFeeBearerCustomer' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23000,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureWithPaymentFeeBearerCustomerMerchantFeeBearerDynamic' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23000,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureWithAmountCreditForFeeBearerCustomer' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23000,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureWithFeeCreditForFeeBearerCustomer' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23000,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureWithAmountCreditForPostpaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 20000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 0,
                'tax'               => 0,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureWithFeeCreditForPostpaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23600,
                'tax'               => 3600,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testTransactionOnCaptureForPostpaid' => [
        'response' => [
            'content' => [
                'entity'            => 'payment',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'international'     => false,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => null,
                'bank'              => null,
                'wallet'            => null,
                'vpa'               => null,
                'notes'             => [],
                'fee'               => 23600,
                'tax'               => 3600,
                'error_code'        => null,
                'error_description' => null,
            ],
            'status_code' => 200,
        ]
    ],

    'testEmandateCaptureWithSufficientBalance' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testEmandateCaptureWithZeroBalance' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testEmandateCaptureWithZeroBalanceWithAutoRecurringType' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                  => 'RZP\Exception\LogicException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ]
    ],

    'testCaptureWithNegativeBalance' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testEmandateCaptureWithNegativeBalance' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testEmandateCaptureWithNegativeBalanceCrossingThreshold' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Negative Balance has crossed the negative limit threshold',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                  => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED,
        ]
    ],

    'testEmandateCaptureWithNegativeAndReserveBalance' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testEmandateCaptureWithSufficientFeeCredits' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testEmandateCaptureWithInSufficientFeeCredits' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testEmandateCaptureWithFeeCreditsAndReserveBalance' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testNachCaptureWithSufficientBalance' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testNachCaptureWithZeroBalance' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testNachCaptureWithZeroBalanceWithAutoRecurringType' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                  => 'RZP\Exception\LogicException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ]
    ],

    'testNachCaptureWithNegativeAndReserveBalance' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testNachCaptureWithSufficientFeeCredits' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testNachCaptureWithNegativeBalance' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testNachCaptureWithNegativeBalanceCrossingThreshold' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Negative Balance has crossed the negative limit threshold',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                  => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED,
        ]
    ],

    'testNachCaptureWithInSufficientFeeCredits' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testNachCaptureWithFeeCreditsAndReserveBalance' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testCaptureAddBalanceToNegativeBalance' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testNegativeBalanceReminderCreation' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testNegativeBalanceReminderCreationFromDisabledReminder' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 0
            ],
        ],
    ],

    'testNegativeBalanceReminderDeletion' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testNegativeBalanceDisabledReminderDeletion' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testNegativeBalanceReminderCreationFromNullReminderId' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testNegativeBalanceReminderDeletionWithException' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testNegativeBalanceReminderUpdateWithException' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],
];
