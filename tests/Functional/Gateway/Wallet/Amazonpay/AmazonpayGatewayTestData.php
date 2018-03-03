<?php

return [
    'testPayment' => [
        'action'               => 'authorize',
        'amount'               => '500.00',
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
                    'description' => \RZP\Error\PublicErrorDescription::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
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
        'amount'               => '500.00',
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
                    'code'        => \RZP\Error\PublicErrorCode::SERVER_ERROR,
                    'description' => \RZP\Error\PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\RuntimeException::class,
            'internal_error_code' => \RZP\Error\ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
            'message'             => 'Failed checksum verification'
        ],
    ],

    'testPaymentSignatureVerificationFailureWallet' => [
        'action'               => 'authorize',
        'amount'               => '500.00',
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
                'amount'               => '500.00',
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
                    'description' => \RZP\Error\PublicErrorDescription::SERVER_ERROR,
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
        'amount'               => '500',
        'wallet'               => 'amazonpay',
        'received'             => true,
        'email'                => 'a@b.com',
        'contact'              => '+919918899029',
        'gateway_merchant_id'  => 'random_dummy_value',
        'response_code'        => null,
        'entity'               => 'wallet',
        'status_code'          => 'Pending',
        'response_description' => null,
        'gateway_payment_id'   => null,
        'reference1'           => null,
        'date'                 => null,
        'gateway_refund_id'    => 'S04-2665653-4222901-R066827',
    ],

    'testPaymentRefundInitiationFailed' => [
        'action'               => 'refund',
        'amount'               => '500',
        'wallet'               => 'amazonpay',
        'received'             => true,
        'email'                => 'a@b.com',
        'contact'              => '+919918899029',
        'gateway_merchant_id'  => 'random_dummy_value',
        'response_code'        => null,
        'entity'               => 'wallet',
        'status_code'          => 'FAILED',
        'response_description' => null,
        'gateway_payment_id'   => null,
        'reference1'           => null,
        'date'                 => null,
        'gateway_refund_id'    => 'S04-2665653-4222901-R066827',
    ],

    'testPaymentRefundInitiateEmptyResult' => [
        'action'               => 'refund',
        'amount'               => '500',
        'wallet'               => 'amazonpay',
        'received'             => true,
        'email'                => 'a@b.com',
        'contact'              => '+919918899029',
        'gateway_merchant_id'  => 'random_dummy_value',
        'response_code'        => null,
        'entity'               => 'wallet',
        'status_code'          => 'FAILURE',
        'response_description' => null,
        'gateway_payment_id'   => null,
        'reference1'           => null,
        'date'                 => null,
        'gateway_refund_id'    => null,
    ],

    'testPaymentRefundInitiateEmptyStatus' => [
        'action'               => 'refund',
        'amount'               => '500',
        'wallet'               => 'amazonpay',
        'received'             => true,
        'email'                => 'a@b.com',
        'contact'              => '+919918899029',
        'gateway_merchant_id'  => 'random_dummy_value',
        'response_code'        => null,
        'entity'               => 'wallet',
        'status_code'          => 'FAILURE',
        'response_description' => null,
        'gateway_payment_id'   => null,
        'reference1'           => null,
        'date'                 => null,
        'gateway_refund_id'    => 'S04-2665653-4222901-R066827',
    ],

    'testPaymentRefundInitiateMultiplePending' => [
        'action'               => 'refund',
        'amount'               => '500',
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
        'date'                 => null,
        'gateway_refund_id'    => null,
    ],
];
