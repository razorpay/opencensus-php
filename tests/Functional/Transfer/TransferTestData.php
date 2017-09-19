<?php

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
        ],
        'on_hold'       => '1',
        'on_hold_until' => 1586055431,
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
        'on_hold_until' => 1586055431,
    ],

    'patchAccountTransferRequest' => [
        'on_hold'       => '1',
        'on_hold_until' => 1586055431,
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

    'testTransferInvalidType' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account field is required when customer is not present.',
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
];
