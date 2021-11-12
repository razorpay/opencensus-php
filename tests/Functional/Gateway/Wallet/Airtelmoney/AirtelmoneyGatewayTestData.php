<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment'               => [
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
        'wallet'            => 'airtelmoney',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'wallet_airtelmoney',
        'terminal_id'       => '100ArtlMnyTmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testPaymentFailureFlow' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL
        ],
    ],

    'testPaymentWalletEntity' => [
        'action'                => 'authorize',
        'amount'                => '50000',
        'wallet'                => 'airtelmoney',
        'received'              => true,
        'email'                 => 'a@b.com',
        'contact'               => '+919918899029',
        'gateway_merchant_id'   => 'random_id',
        'status_code'           => 'SUC',
        'refund_id'             => null,
        'entity'                => 'wallet',
    ],

    'testFailedPaymentWalletEntity' => [
        'action'                => 'authorize',
        'amount'                => '1999',
        'wallet'                => 'airtelmoney',
        'received'              => true,
        'email'                 => 'a@b.com',
        'contact'               => '+919918899029',
        'gateway_merchant_id'   => 'random_id',
        'status_code'           => 'FAL',
        'refund_id'             => null,
        'entity'                => 'wallet',
    ],

    'testRefundFailedPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_CREDIT_LESS_THAN_DEBIT,
        ],
    ],

    'testVerifyFailedPayment'   => [
        'response'  => [
            'content'     => [
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

    'testRefundPayment'       => [
        'action'                => 'refund',
        'wallet'                => 'airtelmoney',
        'email'                 => 'a@b.com',
        'amount'                => '50000',
        'contact'               => '+919918899029',
        'gateway_merchant_id'   => 'random_id',
        'status_code'           => 'SUC',
        'entity'                => 'wallet',
    ],

    'testPartialRefundPayment'       => [
        'action'                => 'refund',
        'wallet'                => 'airtelmoney',
        'email'                 => 'a@b.com',
        'amount'                => '25000',
        'contact'               => '+919918899029',
        'gateway_merchant_id'   => 'random_id',
        'status_code'           => 'SUC',
        'entity'                => 'wallet',
    ],

    'testRefundFailedPaymentEntity' => [
        'action'                => 'refund',
        'wallet'                => 'airtelmoney',
        'email'                 => 'a@b.com',
        'amount'                => '2999',
        'contact'               => '+919918899029',
        'gateway_merchant_id'   => 'random_id',
        'status_code'           => 'FAL',
        'entity'                => 'wallet',
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

    'testFailedVerify' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => "Your payment didn't go through due to a temporary issue. Any debited amount will be refunded in 4-5 business days.",
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID ,
        ],
    ],

    'testUndefinedHashFailedPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => "Your payment has been cancelled. Try again or complete the payment later.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_NETBANKING_PAYMENT_PAGE,
        ],
    ],

    'testUndefinedHashSuccessPayment' => [
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
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
        ],
    ]

];
