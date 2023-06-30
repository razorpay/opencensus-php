<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'createTransfer' => [
        'method'  => 'POST',
        'url'     => '/transfers',
        'content' => [],
    ],

    'patchTransfer' => [
        'method'  => 'PATCH',
        'url'     => '/transfers',
        'content' => [],
    ],

    'createAccountTransferRequest' => [
        'account'       => 'acc_10000000000001',
        'amount'        => 1000,
        'currency'      => 'INR',
        'notes'         => [
            'order_info'    => 'random_string',
            'version'       => 2,
            'roll_no'       => 'iec2011025',
            'student_name'  => 'student',
        ],
        'linked_account_notes' => ['roll_no', 'student_name'],
        'on_hold'       => '1',
        'on_hold_until' => 2122588614,
    ],

    'testTransferToAccountUsingAccountCode' => [
        'request' => [
            'url' => '/transfers',
            'method' => 'post',
            'content' => [
                'account_code' => 'code-007',
                'amount' => 10000,
                'currency' => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'recipient' => 'acc_10000000000001',
                'account_code' => 'code-007',
                'amount' => 10000,
                'currency' => 'INR',
            ],
        ],
    ],

    'createCustomerTransferRequest' => [
        'customer'      => 'cust_200000customer',
        'amount'        => 1000,
        'currency'      => 'INR',
        'notes'         => [
            'order_info'    => 'random_string',
            'version'       => 2,
        ],
        'on_hold'       => '1',
        'on_hold_until' => 2122588614,
    ],

    'patchAccountTransferRequest' => [
        'on_hold'       => '1',
        'on_hold_until' => 2122588614,
    ],

    'testFetchTransferReversals' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count' => 1,
            ]
        ],
    ],

    'testFetchSingleReversal' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testFetchSingleReversalProxyAuth' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testFetchMultipleReversals' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/reversals',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'count'         => 3,
                'items'         => [],
            ],
        ],
    ],

    'testLiveModeTransferToNonActivatedAccount' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED,
        ],
    ],

    'testDirectTransferAmountOverMaxAmount' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Amount exceeds the maximum amount allowed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testTransferInsufficientBalance' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE,
        ],
    ],

    'testTransferWithFeeInsufficientBalance' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE,
        ],
    ],

    'testTransferInvalidType' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Exactly one of account, account_code & customer to be passed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testTransferOnHoldUntilInvalid' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The on hold field is required when on hold until is present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPatchTransferOnHoldTxnSettled' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UPDATE_ON_HOLD_ALREADY_SETTLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_UPDATE_ON_HOLD_ALREADY_SETTLED,
        ],
    ],

    'testTransferOnHoldUntilOnHoldFalse' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The on_hold field must be set to 1, if on_hold_until is sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPatchTransferOnHoldUntilOnHoldFalse' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The on_hold field must be set to 1, if on_hold_until is sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRetrieveTransfer' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transfers',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'source'        => 'acc_10000000000000',
                'recipient'     => 'acc_10000000000001',
                'amount'        => 1000,
                'currency'      => 'INR',
            ],
        ],
    ],

    'testRetrieveMultipleTransfers' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transfers',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'count'         => 2,
                'items'         => [],
            ],
        ],
    ],

    'testReversalAmountExceedingTransferred' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSFER_REVERSAL_AMOUNT_GREATER_THAN_TRANSFERRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSFER_REVERSAL_AMOUNT_GREATER_THAN_TRANSFERRED,
        ],
    ],

    'testPartialReversalExceeding' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSFER_REVERSAL_AMOUNT_GREATER_THAN_UNREVERSED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSFER_REVERSAL_AMOUNT_GREATER_THAN_UNREVERSED,
        ],
    ],

    'testReversalWithInsufficientLinkedAccountBalance' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSFER_REVERSAL_INSUFFICIENT_BALANCE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSFER_REVERSAL_INSUFFICIENT_BALANCE,
        ],
    ],

    'testLiveTransferFundsOnHold' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
        ],
    ],

    'testPaymentAfterTransferReversal' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '', // set dynamically
            'content' => [
                'transfers' => [
                    [
                        'account'  => 'acc_10000000000001',
                        'amount'   => 1000,
                        'currency' => 'INR',
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 1000,
                        'currency'  => 'INR',
                    ]
                ],
            ],
        ],
    ],

    'testRetrieveLaTransfers' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/la-transfers',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [],
            ],
        ]
    ],

    'testFetchLaTransfer' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/la-transfers/%s',
            'content' => [],
        ],
        'response' => [
            'content' => [],
        ]
    ],

    'testLaFetchTransferReversals' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [],
        ]
    ],

    'testLaFetchReversals' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testLaFetchReversal' => [
        'request' => [
            'method' => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testLaNotesKeyMissing' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_LINKED_ACCOUNT_NOTES_KEY_MISSING,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_NOTES_KEY_MISSING,
        ],
    ],

    'testLinkedAccountValidation' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/la-transfers',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCOUNT_IS_NOT_LINKED_ACCOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCOUNT_IS_NOT_LINKED_ACCOUNT,
        ],
    ],

    'testRouteMerchantReversalAndCustomerRefund' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/transfers/%s/reversals',
            'content' => [
                'amount'          => 100,
                'customer_refund' => 1
            ],
        ],
        'response'  => [
            'content' => []
        ],
    ],

    'testLinkedAccountReversal' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/la-transfers/%s/reversal',
            'content' => [
                'amount'          => 100,
            ],
        ],
        'response'  => [
            'content' => []
        ],
    ],

    'testLinkedAccountReversalAndCustomerRefund' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/la-transfers/%s/reversal',
            'content' => [
                'amount'          => 100,
                'customer_refund' => 1
            ],
        ],
        'response'  => [
            'content' => []
        ],
    ],

    'testLinkedAccountReversalAndCustomerRefundOnPaymentForWhichPartialRefundNotSupported' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/la-transfers/%s/reversal',
            'content' => [
                'amount'          => 100,
                'customer_refund' => 1
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_PARTIAL_REFUND_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_PARTIAL_REFUND_NOT_SUPPORTED,
        ],
    ],

    'testLinkedAccountReversalWithoutPermission' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/la-transfers/%s/reversal',
            'content' => [
                'amount'          => 100,
                'customer_refund' => 1
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testLinkedAccountReversalInsufficientBalance' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/la-transfers/%s/reversal',
            'content' => [
                'amount'          => 100,
                'customer_refund' => 1
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSFER_REVERSAL_INSUFFICIENT_BALANCE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSFER_REVERSAL_INSUFFICIENT_BALANCE,
        ],
    ],

    'testLinkedAccountReversalInsufficientRefundCredits' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/la-transfers/%s/reversal',
            'content' => [
                'amount'          => 100,
                'customer_refund' => 1
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS,
        ],
    ],

    'testLinkedAccountReversalInvalidTransfer' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/la-transfers/%s/reversal',
            'content' => [
                'amount'          => 100,
                'customer_refund' => 1
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testTransferInsufficientBalanceWithNegativeBalance' => [
        'response' => [
            'content' => [
                'entity'            =>'transfer',
                'source'            =>'acc_10000000000000',
                'recipient'         =>'acc_10000000000001',
                'amount'            =>1000,
                'currency'          =>"INR",
                'amount_reversed'   =>0
            ],
            'status_code' => 200,
        ]
    ],

    'testTransferInsufficientBalanceWithNegativeAndReserveBalance' => [
        'response' => [
            'content' => [
                'entity'            =>'transfer',
                'source'            =>'acc_10000000000000',
                'recipient'         =>'acc_10000000000001',
                'amount'            =>1000,
                'currency'          =>"INR",
                'amount_reversed'   =>0
            ],
            'status_code' => 200,
        ]
    ],

    'testTransferInsufficientBalanceWithReserveBalance' => [
        'response' => [
            'content' => [
                'entity'            =>'transfer',
                'source'            =>'acc_10000000000000',
                'recipient'         =>'acc_10000000000001',
                'amount'            =>1000,
                'currency'          =>"INR",
                'amount_reversed'   =>0
            ],
            'status_code' => 200,
        ]
    ],

    'testTransferInsufficientBalanceWithNegativeBalanceCrossingThreshold' => [
        'response' => [
            'content' => [
                'entity'            =>'transfer',
                'source'            =>'acc_10000000000000',
                'recipient'         =>'acc_10000000000001',
                'amount'            =>10000,
                'currency'          =>"INR",
                'amount_reversed'   =>0
            ],
            'status_code' => 200,
        ]
    ],

    'testTransferWithFeeInsufficientBalanceWithNegativeBalance' => [
        'response' => [
            'content' => [
                'entity'            =>'transfer',
                'source'            =>'acc_10000000000000',
                'recipient'         =>'acc_10000000000001',
                'amount'            =>1000,
                'currency'          =>"INR",
                'amount_reversed'   =>0
            ],
            'status_code' => 200,
        ]
    ],

    'testTransferWithFeeInsufficientBalanceWithReserveBalance' => [
        'response' => [
            'content' => [
                'entity'            =>'transfer',
                'source'            =>'acc_10000000000000',
                'recipient'         =>'acc_10000000000001',
                'amount'            => 1000,
                'currency'          =>"INR",
                'amount_reversed'   =>0
            ],
            'status_code' => 200,
        ]
    ],

    'testTransferWithFeeInsufficientBalanceWithNegativeAndReserveBalance' => [
        'response' => [
            'content' => [
                'entity'            =>'transfer',
                'source'            =>'acc_10000000000000',
                'recipient'         =>'acc_10000000000001',
                'amount'            =>1000,
                'currency'          =>"INR",
                'amount_reversed'   =>0
            ],
            'status_code' => 200,
        ]
    ],

    'testFetchTransferProxyAuth' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testCreateReversalFromBatch' => [
        'request' => [
            'method' => 'post',
            'content' => [
                'amount'    => 200,
            ],
        ],
        'response' => [
            'content' => [
                'amount'    => 200,
            ],
        ],
    ],

    'testCreateDirectTransferWithIKeyHeader' => [
        'request'   => [
            'method'   => 'POST',
            'url'      => '/transfers',
            'content'   => [
                'account'       => 'acc_10000000000001',
                'amount'        => 1000,
                'currency'      => 'INR',
                'notes'         => [
                    'order_info'    => 'random_string',
                    'version'       => 2,
                    'roll_no'       => 'iec2011025',
                    'student_name'  => 'student',
                ],
                'linked_account_notes' => ['roll_no', 'student_name'],
                'on_hold'       => '1',
                'on_hold_until' => 2122588614,
            ],
        ],
        'response'  =>  [
            'content' => [
                'entity' => 'transfer',
                'status' => 'processed',
                'source' => 'acc_10000000000000',
                'recipient' => 'acc_10000000000001',
                'amount' => 1000,
                'currency' => 'INR',
                'notes' =>  [
                                'order_info' => 'random_string',
                                'version' => 2,
                                'roll_no' => 'iec2011025',
                                'student_name' => 'student',
                            ],
                'linked_account_notes' =>
                    [
                        'roll_no',
                        'student_name',
                    ],
                'on_hold' => true,
                'on_hold_until' => 2122588614,
            ],
        ],
    ],

    'testCreateDirectTransferWithIKeyInProgress' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/transfers',
            'server'  => [
                'HTTP_X-Transfer-Idempotency'  => 'unique-ikey'
            ],
            'content' => [
                'account'       => 'acc_10000000000001',
                'amount'        => 1000,
                'currency'      => 'INR',
                'notes'         => [
                    'order_info'    => 'random_string',
                    'version'       => 2,
                    'roll_no'       => 'iec2011025',
                    'student_name'  => 'student',
                ],
                'linked_account_notes' => ['roll_no', 'student_name'],
                'on_hold'       => '1',
                'on_hold_until' => 2122588614,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Request failed because another request is in progress with the same Idempotency Key',
                    'reason'      => 'transfer_request_with_same_idempotency_key_in_progress'
                ],
            ],
            'status_code' => 409,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_CONFLICT_ANOTHER_OPERATION_PROGRESS_SAME_IDEM_KEY,
        ],
    ],

    'testCreateTwoDirectTransfersWithSameIKeyDiffRequest' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/transfers',
            'content' => [
                'account'       => 'acc_10000000000001',
                'amount'        => 2000,
                'currency'      => 'INR',
                'notes'         => [
                    'order_info'    => 'Same Ikey with different request body',
                    'version'       => 3,
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Different request body sent for the same Idempotency Header',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_SAME_IDEM_KEY_DIFFERENT_REQUEST,
        ],
    ],

    'testCreateDirectTransferWithPartnerAuthForMarketplace' => [
        'request'   => [
            'method'   => 'POST',
            'url'      => '/transfers',
            'content'   => [
                'account'       => 'acc_10000000000001',
                'amount'        => 1000,
                'currency'      => 'INR',
                'notes'         => [
                    'order_info'    => 'random_string',
                    'version'       => 2,
                    'roll_no'       => 'iec2011025',
                    'student_name'  => 'student',
                ],
                'linked_account_notes' => ['roll_no', 'student_name'],
                'on_hold'       => '1',
                'on_hold_until' => 2122588614,
            ],
        ],
        'response'  =>  [
            'content' => [
                'entity' => 'transfer',
                'status' => 'processed',
                'source' => 'acc_10000000000000',
                'recipient' => 'acc_10000000000001',
                'amount' => 1000,
                'currency' => 'INR',
                'notes' =>  [
                    'order_info' => 'random_string',
                    'version' => 2,
                    'roll_no' => 'iec2011025',
                    'student_name' => 'student',
                ],
                'linked_account_notes' =>
                    [
                        'roll_no',
                        'student_name',
                    ],
                'on_hold' => true,
                'on_hold_until' => 2122588614,
            ],
        ],
    ],

    'testCreateDirectTransferWithOAuthForMarketplace' => [
        'request'   => [
            'method'   => 'POST',
            'url'      => '/transfers',
            'content'   => [
                'account'       => 'acc_10000000000001',
                'amount'        => 1000,
                'currency'      => 'INR',
                'notes'         => [
                    'order_info'    => 'random_string',
                    'version'       => 2,
                    'roll_no'       => 'iec2011025',
                    'student_name'  => 'student',
                ],
                'linked_account_notes' => ['roll_no', 'student_name'],
                'on_hold'       => '1',
                'on_hold_until' => 2122588614,
            ],
        ],
        'response'  =>  [
            'content' => [
                'entity' => 'transfer',
                'status' => 'processed',
                'source' => 'acc_10000000000000',
                'recipient' => 'acc_10000000000001',
                'amount' => 1000,
                'currency' => 'INR',
                'notes' =>  [
                    'order_info' => 'random_string',
                    'version' => 2,
                    'roll_no' => 'iec2011025',
                    'student_name' => 'student',
                ],
                'linked_account_notes' =>
                    [
                        'roll_no',
                        'student_name',
                    ],
                'on_hold' => true,
                'on_hold_until' => 2122588614,
            ],
        ],
    ],

    'testCreateDirectTransferWithOAuthForMarketplaceWithAppLevelFeature' => [
        'request'   => [
            'method'   => 'POST',
            'url'      => '/transfers',
            'content'   => [
                'account'       => 'acc_10000000000001',
                'amount'        => 1000,
                'currency'      => 'INR',
                'notes'         => [
                    'order_info'    => 'random_string',
                    'version'       => 2,
                    'roll_no'       => 'iec2011025',
                    'student_name'  => 'student',
                ],
                'linked_account_notes' => ['roll_no', 'student_name'],
                'on_hold'       => '1',
                'on_hold_until' => 2122588614,
            ],
        ],
        'response'  =>  [
            'content' => [
                'entity' => 'transfer',
                'status' => 'processed',
                'source' => 'acc_10000000000000',
                'recipient' => 'acc_10000000000001',
                'amount' => 1000,
                'currency' => 'INR',
                'notes' =>  [
                    'order_info' => 'random_string',
                    'version' => 2,
                    'roll_no' => 'iec2011025',
                    'student_name' => 'student',
                ],
                'linked_account_notes' =>
                    [
                        'roll_no',
                        'student_name',
                    ],
                'on_hold' => true,
                'on_hold_until' => 2122588614,
            ],
        ],
    ],

    'testCreateDirectTransferEntityOriginWithPartnerAuthForMarketplace' => [
    'request'   => [
        'method'   => 'POST',
        'url'      => '/transfers',
        'content'   => [
            'account'       => 'acc_10000000000001',
            'amount'        => 1000,
            'currency'      => 'INR',
            'notes'         => [
                'order_info'    => 'random_string',
                'version'       => 2,
                'roll_no'       => 'iec2011025',
                'student_name'  => 'student',
            ],
            'linked_account_notes' => ['roll_no', 'student_name'],
            'on_hold'       => '1',
            'on_hold_until' => 2122588614,
        ],
    ],
    'response'  =>  [
        'content' => [
            'entity' => 'transfer',
            'status' => 'processed',
            'source' => 'acc_10000000000000',
            'recipient' => 'acc_10000000000001',
            'amount' => 1000,
            'currency' => 'INR',
            'notes' =>  [
                'order_info' => 'random_string',
                'version' => 2,
                'roll_no' => 'iec2011025',
                'student_name' => 'student',
            ],
            'linked_account_notes' =>
                [
                    'roll_no',
                    'student_name',
                ],
            'on_hold' => true,
            'on_hold_until' => 2122588614,
        ],
    ],
],

    'testTransferResponseEntityOriginWithPartnerAuthForMarketplace' => [
        'request'   => [
            'method'   => 'GET',
            'url'      => '/transfers/{id}?transfer_type=platform',
            'content'   => [],
        ],
        'response'  =>  [
            'content' => [
                'entity' => 'transfer',
                'status' => 'processed',
                'source' => 'pay_abacad',
                'recipient' => 'acc_10000000000001',
                'amount' => 1000,
                'currency' => 'INR',
                'partner_details' =>  [
                    'name' => 'partner_test',
                    'id' => '10000000000003',
                    'email' => 'testmail@mail.info',
                ],
            ],
        ],
    ],

    'testFetchMultipleNonPlatformTransfers' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transfers',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'count'         => 2,
                'items'         => [
                    [
                        "id"                        => 'trf_LhV9fg1fXklNUG',
                        "recipient"                 => 'acc_10000000000004',
                        "currency"                  => "INR",
                        "amount"                    => 1000,
                        'status'                    => 'processed',
                        'amount_reversed'           => 0,
                        'notes'                     => [],
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'on_hold'                   => false,
                        'on_hold_until'             => null,
                        'recipient_settlement_id'   => null,
                        'linked_account_notes'      => [],
                    ],
                    [
                        "id"                        => 'trf_LhV9fg1fXagWCN',
                        "recipient"                 => 'acc_10000000000004',
                        "currency"                  => "INR",
                        "amount"                    => 1000,
                        'status'                    => 'processed',
                        'amount_reversed'           => 0,
                        'notes'                     => [],
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'on_hold'                   => false,
                        'on_hold_until'             => null,
                        'recipient_settlement_id'   => null,
                        'linked_account_notes'      => [],
                    ]
                ],
            ],
        ],
    ],

    'testFetchMultiplePlatformTransfers' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transfers?transfer_type=platform',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'count'         => 2,
                'items'         => [
                    [
                        "id"                        => 'trf_LhV9fg1fXklNUG',
                        "recipient"                 => 'acc_10000000000001',
                        "currency"                  => "INR",
                        "amount"                    => 1000,
                        'status'                    => 'processed',
                        'amount_reversed'           => 0,
                        'notes'                     => [],
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'on_hold'                   => false,
                        'on_hold_until'             => null,
                        'recipient_settlement_id'   => null,
                        'linked_account_notes'      => [],
                    ],
                    [
                        "id"                        => 'trf_LhV9fg1fXagWCN',
                        "recipient"                 => 'acc_10000000000001',
                        "currency"                  => "INR",
                        "amount"                    => 1000,
                        'status'                    => 'processed',
                        'amount_reversed'           => 0,
                        'notes'                     => [],
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'on_hold'                   => false,
                        'on_hold_until'             => null,
                        'recipient_settlement_id'   => null,
                        'linked_account_notes'      => [],
                    ]
                ],
            ],
        ],
    ],

    'testFetchMultiplePlatformTransfersWithSource' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transfers?transfer_type=platform&source=pay_LpodrylYxBEsvd',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'count'         => 2,
                'items'         => [
                    [
                        "id"                        => 'trf_LhV9fg1fXklNUG',
                        "recipient"                 => 'acc_10000000000001',
                        "currency"                  => "INR",
                        "amount"                    => 1000,
                        'status'                    => 'processed',
                        'amount_reversed'           => 0,
                        'notes'                     => [],
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'on_hold'                   => false,
                        'on_hold_until'             => null,
                        'recipient_settlement_id'   => null,
                        'linked_account_notes'      => [],
                    ],
                    [
                        "id"                        => 'trf_LhV9fg1fXagWCN',
                        "recipient"                 => 'acc_10000000000001',
                        "currency"                  => "INR",
                        "amount"                    => 1000,
                        'status'                    => 'processed',
                        'amount_reversed'           => 0,
                        'notes'                     => [],
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'on_hold'                   => false,
                        'on_hold_until'             => null,
                        'recipient_settlement_id'   => null,
                        'linked_account_notes'      => [],
                    ]
                ],
            ],
        ],
    ],

    'testFetchMultiplePlatformTransfersWithInvalidSource' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transfers?transfer_type=platform&source=pay_LpodrylYxBFsvd',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'count'         => 0,
                'items'         => [],
            ],
        ],
    ],

    'testCreateDirectTransferWithPartnerAuthForInvalidPartnerType' => [
        'request'   => [
            'method'   => 'POST',
            'url'      => '/transfers',
            'content'   => [
                'account'       => 'acc_10000000000001',
                'amount'        => 1000,
                'currency'      => 'INR',
                'notes'         => [
                    'order_info'    => 'random_string',
                    'version'       => 2,
                    'roll_no'       => 'iec2011025',
                    'student_name'  => 'student',
                ],
                'linked_account_notes' => ['roll_no', 'student_name'],
                'on_hold'       => '1',
                'on_hold_until' => 2122588614,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid partner action',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION
        ],
    ],

    'testCreateDirectTransferWithPartnerAuthForInvalidPartnerMerchantMapping' => [
        'request'   => [
            'method'   => 'POST',
            'url'      => '/transfers',
            'content'   => [
                'account'       => 'acc_10000000000001',
                'amount'        => 1000,
                'currency'      => 'INR',
                'notes'         => [
                    'order_info'    => 'random_string',
                    'version'       => 2,
                    'roll_no'       => 'iec2011025',
                    'student_name'  => 'student',
                ],
                'linked_account_notes' => ['roll_no', 'student_name'],
                'on_hold'       => '1',
                'on_hold_until' => 2122588614,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The partner does not have access to the merchant',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testDebugRoute' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/transfer_debug',
            'content' => [
                'option'    => 'payment_transfer',
                'data'      => [
                    'abcd1234567890',
                ],
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],

    'testLiveModeTransferToSuspendedAccount' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSFER_NOT_ALLOWED_TO_SUSPENDED_LINKED_ACCOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_SUSPENDED,
        ],
    ],
];
