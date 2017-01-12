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
        'on_hold'       => '1',
        'hold_until'    => 1586055431,
    ],

    'createCustomerTransferRequest' => [
        'customer'       => 'cust_200000customer',
        'amount'        => 1000,
        'currency'      => 'INR',
        'on_hold'       => '1',
        'hold_until'    => 1586055431,
    ],

    'patchAccountTransferRequest' => [
        'on_hold'       => '1',
        'hold_until'    => 1586055431,
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

    'testTransferHoldUntilInvalid' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The on hold field is required when hold until is present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testTransferHoldUntilOnHoldFalse' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The on_hold field must be set to 1, if hold_until is sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPatchTransferHoldUntilOnHoldFalse' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The on_hold field must be set to 1, if hold_until is sent',
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
                'source_id'     => 'acc_10000000000000',
                'to_id'         => 'acc_10000000000001',
                'amount'        => 1000,
                'currency'      => 'INR',
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
];
