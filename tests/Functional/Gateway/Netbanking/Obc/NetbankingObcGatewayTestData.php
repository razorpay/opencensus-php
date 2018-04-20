<?php

namespace RZP\Tests\Functional\Gateway\Obc;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;

return [
    'testPayment' => [
        'entity'          => 'netbanking',
        'amount'          => 50000,
        'status'          => 'Y',
        'bank_payment_id' => '9999999999',
        'account_number'  => '1234567890'
    ],

    'testPaymentFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'netbankingPaymentFailed' => [
        'entity'          => 'netbanking',
        'amount'          => 50000,
        'status'          => 'N',
        'bank_payment_id' => '9999999999',
        'account_number'  => '1234567890'
    ],

    'netbankingPaymentFailedVerifySuccess' => [
        'entity'          => 'netbanking',
        'amount'          => 50000,
        'status'          => 'Y',
        'bank_payment_id' => '9999999999',
        'account_number'  => '1234567890'
    ],

    'netbankingVerify' => [
        'entity'          => 'netbanking',
        'amount'          => 50000,
        'status'          => 'Y',
        'bank_payment_id' => '9999999999',
        'account_number'  => '1234567890'
    ],

    'testVerifyMismatch' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => PaymentVerificationException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],

    'testPaymentAmountMismatch' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\RuntimeException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],

    'testAuthFailedVerifySuccess' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\PaymentVerificationException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],
];
