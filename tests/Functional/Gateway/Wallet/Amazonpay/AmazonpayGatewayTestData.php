<?php

return [
    'testPayment' => [
        'action'               => 'authorize',
        'amount'               => '50000',
        'wallet'               => 'amazonpay',
        'received'             => true,
        'email'                => 'a@b.com',
        'contact'              => '+919918899029',
        'gateway_merchant_id'  => 'random_dummy_value',
        'response_code'        => '001',
        'refund_id'            => null,
        'entity'               => 'wallet',
        'status_code'          => 'SUCCESS',
        'response_description' => 'Txn Success',
        'gateway_payment_id'   => 'S04-3441699-5326071'
    ],

    'testPaymentFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => \RZP\Error\PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "You've entered an incorrect OTP. Try again.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => \RZP\Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        ],
    ],

    'testPaymentFailedWallet' => [
        'action'               => 'authorize',
        'amount'               => '50000',
        'wallet'               => 'amazonpay',
        'received'             => true,
        'email'                => 'a@b.com',
        'contact'              => '+919918899029',
        'gateway_merchant_id'  => 'random_dummy_value',
        'response_code'        => '229',
        'refund_id'            => null,
        'entity'               => 'wallet',
        'status_code'          => 'FAILED',
        'response_description' => '3d Secure Verification Failed',
        'gateway_payment_id'   => 'S04-3441699-5326071'
    ],

    'testPaymentSignatureVerificationFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => \RZP\Error\PublicErrorCode::GATEWAY_ERROR,
                    'description' => \RZP\Error\PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => \RZP\Error\ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
        ],
    ],

    'testPaymentSignatureVerificationFailureWallet' => [
        'action'               => 'authorize',
        'amount'               => '50000',
        'wallet'               => 'amazonpay',
        'received'             => false,
        'email'                => 'a@b.com',
        'contact'              => '+919918899029',
        'gateway_merchant_id'  => 'random_dummy_value',
        'response_code'        => null,
        'refund_id'            => null,
        'entity'               => 'wallet',
        'status_code'          => null,
        'response_description' => null,
        'gateway_payment_id'   => null,
        'reference1'           => null,
        'date'                 => null,
    ],

    'testPaymentVerify' => [
        'payment'        => [
            'verified' => 1,
        ],
        'gateway'        => [
            'status'         => 'status_match',
            'gateway'        => 'wallet_amazonpay',
            'amountMismatch' => false,
            'apiSuccess'     => true,
            'gatewaySuccess' => true,
            'gatewayPayment' => [
                'action'               => 'authorize',
                'amount'               => '50000',
                'wallet'               => 'amazonpay',
                'received'             => true,
                'email'                => 'a@b.com',
                'contact'              => '+919918899029',
                'gateway_merchant_id'  => 'random_dummy_value',
                'response_code'        => '001',
                'refund_id'            => null,
                'status_code'          => 'SUCCESS',
                'response_description' => 'Txn Success',
                'gateway_payment_id'   => 'S04-3441699-5326071'
            ]
        ],
    ],

    'testVerifyFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => \RZP\Error\PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => \RZP\Error\PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code' => \RZP\Error\ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
            'api_success'         => true,
            'gateway_success'     => false,
        ],
    ],

    'testVerifyMutlipleVerifyTablesTwoSuccess' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => \RZP\Error\PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code' => \RZP\Error\ErrorCode::SERVER_ERROR_MULTIPLE_SUCCESS_TRANSACTIONS_IN_VERIFY,
            'api_success'         => true,
            'gateway_success'     => false,
        ],
    ],

    'testPaymentRefundInitiated' => [
        'action'               => 'refund',
        'amount'               => '50000',
        'wallet'               => 'amazonpay',
        'received'             => true,
        'email'                => 'a@b.com',
        'contact'              => '+919918899029',
        'gateway_merchant_id'  => 'random_dummy_value',
        'response_code'        => null,
        'entity'               => 'wallet',
        'status_code'          => 'pending',
        'response_description' => null,
        'gateway_payment_id'   => null,
        'reference1'           => null,
        'reference2'           => 'ebe60e12-a824-4002-bfe4-1c783bd95c12',
        'date'                 => null,
        'gateway_refund_id'    => 'S04-2665653-4222901-R066827',
    ],

    'testPaymentRefundInitiationFailed' => [
        'action'               => 'refund',
        'amount'               => '50000',
        'wallet'               => 'amazonpay',
        'received'             => true,
        'email'                => 'a@b.com',
        'contact'              => '+919918899029',
        'gateway_merchant_id'  => 'random_dummy_value',
        'response_code'        => null,
        'entity'               => 'wallet',
        'status_code'          => 'declined',
        'response_description' => null,
        'gateway_payment_id'   => null,
        'reference1'           => null,
        'reference2'           => 'ebe60e12-a824-4002-bfe4-1c783bd95c12',
        'date'                 => null,
        'gateway_refund_id'    => 'S04-2665653-4222901-R066827',
    ],

    'testPaymentRefundInitiateEmptyResult' => [
        'action'               => 'refund',
        'amount'               => '50000',
        'wallet'               => 'amazonpay',
        'received'             => false,
        'email'                => 'a@b.com',
        'contact'              => '+919918899029',
        'gateway_merchant_id'  => 'random_dummy_value',
        'response_code'        => null,
        'entity'               => 'wallet',
        'status_code'          => null,
        'response_description' => null,
        'gateway_payment_id'   => null,
        'reference1'           => null,
        'reference2'           => null,
        'date'                 => null,
        'gateway_refund_id'    => null,
    ],

    'testPaymentRefundInitiateEmptyStatus' => [
        'action'               => 'refund',
        'amount'               => '50000',
        'wallet'               => 'amazonpay',
        'received'             => false,
        'email'                => 'a@b.com',
        'contact'              => '+919918899029',
        'gateway_merchant_id'  => 'random_dummy_value',
        'response_code'        => null,
        'entity'               => 'wallet',
        'status_code'          => null,
        'response_description' => null,
        'gateway_payment_id'   => null,
        'reference1'           => null,
        'reference2'           => null,
        'date'                 => null,
        'gateway_refund_id'    => null,
    ],

    'testPaymentRefundInitiateMultiplePending' => [
        'action'               => 'refund',
        'amount'               => '50000',
        'wallet'               => 'amazonpay',
        'received'             => true,
        'email'                => 'a@b.com',
        'contact'              => '+919918899029',
        'gateway_merchant_id'  => 'random_dummy_value',
        'response_code'        => null,
        'entity'               => 'wallet',
        'status_code'          => 'pending',
        'response_description' => null,
        'gateway_payment_id'   => null,
        'reference1'           => null,
        'reference2'           => 'ebe60e12-a824-4002-bfe4-1c783bd95c12',
        'date'                 => null,
        'gateway_refund_id'    => 'S04-2665653-4222901-R066827',
    ],
];
