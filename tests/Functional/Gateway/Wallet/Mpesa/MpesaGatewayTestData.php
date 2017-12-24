<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testOtpPayment' => [
        'merchant_id'     => '10000000000000',
        'amount'          => 50000,
        'currency'        => 'INR',
        'base_amount'     => 50000,
        'status'          => 'captured',
        'two_factor_auth' => 'passed',
        'method'          => 'wallet',
        'wallet'          => 'mpesa',
        'gateway'         => 'wallet_mpesa',
        'terminal_id'     => '100VodaMpesaTl',
    ],

    'testOtpPaymentWalletEntity' => [
        'action'   => 'authorize',
        'received' => true,
        'wallet'   => 'mpesa',
        'amount'   => 50000
    ],

    'testAuthPayment' => [
        'merchant_id'     => '10000000000000',
        'amount'          => 50000,
        'currency'        => 'INR',
        'base_amount'     => 50000,
        'status'          => 'captured',
        'two_factor_auth' => 'passed',
        'method'          => 'wallet',
        'wallet'          => 'mpesa',
        'gateway'         => 'wallet_mpesa',
        'terminal_id'     => '100VodaMpesaTl',
    ],

    'testAuthFloatPayment' => [
        'merchant_id'     => '10000000000000',
        'amount'          => 50050,
        'currency'        => 'INR',
        'base_amount'     => 50050,
        'status'          => 'captured',
        'two_factor_auth' => 'passed',
        'method'          => 'wallet',
        'wallet'          => 'mpesa',
        'gateway'         => 'wallet_mpesa',
        'terminal_id'     => '100VodaMpesaTl',
    ],

    'testAuthPaymentWalletEntity' => [
        'action'               => 'authorize',
        'received'             => true,
        'wallet'               => 'mpesa',
        'amount'               => '500',
        'response_code'        => '100',
        'response_description' => 'SUCCESS'
    ],

    'testAuthFloatPaymentWalletEntity' => [
        'action'               => 'authorize',
        'received'             => true,
        'wallet'               => 'mpesa',
        'amount'               => '500.5',
        'response_code'        => '100',
        'response_description' => 'SUCCESS'
    ],

    'testAuthPaymentFailure' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'gateway_error_code'  => '106',
            'gateway_error_desc'  => 'Failure'
        ],
    ],

    'testOtpAuthFailure' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
            'gateway_error_code'  => '104',
            'gateway_error_desc'  => 'Invalid MSISDN'
        ],
    ],

    'testAuthPaymentVerify' => [
        'payment'                   => [
            'verified'              => 1
        ],
        'gateway'                   => [
            'apiSuccess'            => true,
            'gatewaySuccess'        => true,
            'status'                => 'status_match',
            'gateway'               => 'wallet_mpesa',
            'verifyResponseContent' => [
                'statusCode'        => '100',
                'reason'            => 'SUCCESS',
            ],
        ],
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
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
        ],
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
            'class'                 => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],

    'verifyFailedWalletEntity' => [
        'response_code' => '104',
        'response_description' => 'Invalid MSISDN',
    ],

    'verifySuccessWalletEntity' => [
        'response_code'        => '100',
        'wallet'               => 'mpesa',
        'action'               => 'authorize',
        'received'             => true,
        'response_description' => 'Success',
        'entity'               => 'wallet'
    ],

    'testRefundPayment' => [
        'entity' => 'refund',
        'amount' => 50000,
        'currency' => 'INR',
        'gateway_refunded' => true,
    ],

    'testPartialRefund' => [
        'entity' => 'refund',
        'amount' => 10000,
        'currency' => 'INR',
        'gateway_refunded' => true,
    ],

    'testMpesaUpperCaseError' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED,
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
            'class'                 => 'RZP\Exception\LogicException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'testAuthorizeFailedNullVerifyResponse' => [
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
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
        ],
    ],
];
