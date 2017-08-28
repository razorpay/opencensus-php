<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testSuccessful13DigitPanTxn' => [
        'merchant_id' => "10000000000000",
        'amount' => 50000,
        'fee' => 1000,
        'service_tax' => 0,
        'tax' => 0,
        'pricing_rule_id' => null,
        'debit' => 0,
        'credit' => 49000,
        'currency' => "INR",
        'balance' => 1049000,
        'gateway_amount' => null,
        'gateway_fee' => 0,
        'gateway_service_tax' => 0,
        'api_fee' => 0,
        'gratis' => false,
        'fee_credits' => 0,
        'escrow_balance' => 0,
        'channel' => "kotak",
        'admin' => true,
    ],

    'testInvalidMessage' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'ID mismatch',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testBlankMessage' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The message field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testInvalidVersion' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The message. v e res.version must be at least 3 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEmptyVersion' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The message. v e res.version must be at least 3 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEmptyVersionShort' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The message. v e res.version must be at least 3 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
