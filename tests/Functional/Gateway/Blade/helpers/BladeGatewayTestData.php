<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testSuccessful13DigitPanTxn' => [
        'merchant_id' => "10000000000000",
        'amount' => 50000,
        'fee' => 1000,
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
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => 'Payment processing failed due to error at bank or wallet gateway',
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
        ],
    ],

    'testBlankMessage' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => 'Payment processing failed due to error at bank or wallet gateway',
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
        ],
    ],

    'testInvalidVersion' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => 'Payment processing failed due to error at bank or wallet gateway',
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
        ],
    ],

    'testEmptyVersion' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => 'Payment processing failed due to error at bank or wallet gateway',
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEmptyVersionShort' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => 'Payment processing failed due to error at bank or wallet gateway',
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
