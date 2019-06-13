<?php

namespace RZP\Tests\Functional\Gateway\Obc;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\LogicException;
use RZP\Exception\PaymentVerificationException;

return [
    'testPayment' => [
        'amount'          => 50000,
        'status'          => 'Y',
        'bank_payment_id' => '9999999999',
        'account_number'  => '1234567890',
        'received'        => true,
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

    'testAmountTampering' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => LogicException::class,
            'internal_error_code'   => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'netbankingPaymentFailed' => [
        'amount'          => 50000,
        'status'          => 'N',
        'bank_payment_id' => '9999999999',
        'account_number'  => '1234567890'
    ],

    'netbankingPaymentFailedVerifySuccess' => [
        'amount'          => 50000,
        'status'          => 'Y',
        'bank_payment_id' => '9999999999',
        'account_number'  => '1234567890'
    ],

    'netbankingVerify' => [
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

    // When verify callback failure happens, the gateway entity is not updated
    'testVerifyCallbackFailureEntity' => [
        'amount'          => 50000,
        'action'          => 'authorize',
        'bank'            => 'ORBC',
        'bank_payment_id' => '9999999999',
        'status'          => 'Y',
        'reference1'      => null,
        'received'        => true,
    ],

    'testVerifyCallbackFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
        ],
    ],
];
