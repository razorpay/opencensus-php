<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Gateway\Wallet\Sbibuddy\ResponseCodeMap;

return [
    'testPayment'   => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'wallet',
        'status'            => 'captured',
        'two_factor_auth'   => 'passed',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'wallet'            => 'sbibuddy',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'wallet_sbibuddy',
        'terminal_id'       => '1000SbibdyTmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testPaymentWalletEntity' => [
        'action'               => 'authorize',
        // They give the response in Rupees
        'amount'               => '50000',
        'wallet'               => 'sbibuddy',
        'received'             => true,
        'email'                => 'a@b.com',
        'contact'              => '9918899029',
        'status_code'          => ResponseCodeMap::SUCCESS_CODE,
        'entity'               => 'wallet'
    ],

    'testRefundPayment' => [
        'action'               => 'refund',
        'wallet'               => 'sbibuddy',
        'email'                => 'a@b.com',
        'amount'               => '50000',
        'contact'              => '9918899029',
        'gateway_merchant_id'  => 'random_id',
        'status_code'          => ResponseCodeMap::SUCCESS_CODE,
        'entity'               => 'wallet'
    ],

    'testPartialRefundPayment' => [
        'action'               => 'refund',
        'wallet'               => 'sbibuddy',
        'email'                => 'a@b.com',
        'amount'               => '25000',
        'contact'              => '9918899029',
        'gateway_merchant_id'  => 'random_id',
        'gateway_payment_id'   => '987654321',
        'gateway_refund_id'    => '234',
        'status_code'          => ResponseCodeMap::SUCCESS_CODE,
        'entity'               => 'wallet'
    ],

    'testPaymentFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR
        ],
    ],

    'testFailedPaymentWalletEntity' => [
        'action'               => 'authorize',
        'amount'               => '50000',
        'wallet'               => 'sbibuddy',
        'received'             => true,
        'email'                => 'a@b.com',
        'contact'              => '9918899029',
        'gateway_merchant_id'  => 'random_id',
        'status_code'          => ResponseCodeMap::GENERAL_ERROR,
        'error_message'        => 'Error occured',
        'entity'               => 'wallet'
    ],

    'testInsufficientFundsPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment failed due to insufficient balance in wallet',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE
        ],
    ],


    'testRefundFailedPaymentEntity' => [
        'action'               => 'refund',
        'wallet'               => 'sbibuddy',
        'email'                => 'a@b.com',
        'amount'               => '50000',
        'contact'              => '9918899029',
        'gateway_merchant_id'  => 'random_id',
        'status_code'          => ResponseCodeMap::GENERAL_ERROR,
        'error_message'        => 'An error occured',
        'entity'               => 'wallet'
    ],

    'testAuthFailedVerifySuccessPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED
        ],
    ],

    'testAmountMismatchVerifyFailure' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\RuntimeException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR
        ],
    ],

    'testAuthFailedVerifyFailurePayment' => [
        'status'        => 'failed',
        'wallet'        => 'sbibuddy',
        'gateway'       => 'wallet_sbibuddy'
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
];
