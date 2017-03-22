<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Terminal\Shared;

return [
    'testCustomerIdNotSent' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The customer id field is required when wallet is openwallet.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCustomerDoesNotExistForMerchant' => [
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testPayFromWallet' => [
        'payment' => [
            'amount'        => 2000,
            'status'        => 'authorized',
            'terminal_id'   => Shared::OPENWALLET_RAZORPAY_TERMINAL
        ],
        'customerBalance' => [
            'balance'       => 1000,
        ],
        'customerTransaction' => [
            'amount'        => 2000,
            'debit'         => 2000,
            'credit'        => 0,
            'balance'       => 1000
        ],
    ],

    'testRefundWalletPayment' => [
        'customerBalance' => [
            'balance'       => 3000,
        ],
        'customerTransaction' => [
            'amount'        => 1000,
            'debit'         => 0,
            'credit'        => 1000,
            'balance'       => 3000
        ],
    ],

];
