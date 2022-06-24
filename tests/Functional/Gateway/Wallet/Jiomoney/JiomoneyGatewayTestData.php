<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

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
        'wallet'            => 'jiomoney',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'wallet_jiomoney',
        'terminal_id'       => '1000JioMnyTmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
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

    'testPaymentFailureFlow' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        ],
    ],

    'testSuccessfulPaymentWithMissingChecksum' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\RuntimeException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR
        ],
    ],

    'testSuccessfulPaymentWithTamperedChecksum' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\RuntimeException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
            'error_description'   => 'Failed checksum verification',
        ],
    ],

    'testRefundPayment' => [
        'action'               => 'refund',
        'wallet'               => 'jiomoney',
        'email'                => 'a@b.com',
        'amount'               => '50000',
        'contact'              => '9918899029',
        'gateway_merchant_id'  => 'random_id',
        'status_code'          => '000',
        'response_code'        => 'SUCCESS',
        'response_description' => 'APPROVED',
        'entity'               => 'wallet'
    ],

    'testRefundFailedPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_REFUND_FAILED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_REFUND_FAILED
        ]
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

    'testVerifyLateAuthorizedPayment'   => [
        'response'  => [
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

    'testRefundFailedPaymentEntity' => [
        'action'               => 'refund',
        'wallet'               => 'jiomoney',
        'email'                => 'a@b.com',
        'amount'               => '50000',
        'contact'              => '9918899029',
        'gateway_merchant_id'  => 'random_id',
        'status_code'          => '500',
        'response_code'        => 'FAILED',
        'response_description' => 'NA',
        'entity'               => 'wallet'
    ],

    'testPartialRefundPayment' => [
        'action'               => 'refund',
        'wallet'               => 'jiomoney',
        'email'                => 'a@b.com',
        'amount'               => '25000',
        'contact'              => '9918899029',
        'gateway_merchant_id'  => 'random_id',
        'status_code'          => '000',
        'response_code'        => 'SUCCESS',
        'response_description' => 'APPROVED',
        'entity'               => 'wallet'
    ],

    'testPaymentWalletEntity' => [
        'action'               => 'authorize',
        'amount'               => '50000',
        'wallet'               => 'jiomoney',
        'received'             => true,
        'email'                => 'a@b.com',
        'contact'              => '9918899029',
        'gateway_merchant_id'  => 'random_id',
        'status_code'          => '000',
        'response_code'        => 'SUCCESS',
        'response_description' => 'APPROVED',
        'entity'               => 'wallet'
    ],

    'testFailedPaymentWalletEntity' => [
        'action'               => 'authorize',
        'amount'               => '50000',
        'wallet'               => 'jiomoney',
        'received'             => false,
        'email'                => 'a@b.com',
        'contact'              => '9918899029',
        'gateway_merchant_id'  => 'random_id',
        'status_code'          => '500',
        'response_code'        => 'FAILED',
        'response_description' => 'NA',
        'entity'               => 'wallet'
    ],

    'testAuthorizedPaymentRefund' => [
        'amount'    => 50000,
        'currency'  => 'INR',
        'entity'    => 'refund',
        'admin'     => true,
    ],

    'testPaymentWithCallbackVerifyStatusFailure'   => [
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => "Your payment didn't go through due to a temporary issue. Any debited amount will be refunded in 4-5 business days."
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR
        ],
    ],

    'testPaymentWithCallbackVerifyAmountMismatchFailure'   => [
    'response'  => [
        'content' => [
            'error' => [
                'code'        => PublicErrorCode::GATEWAY_ERROR,
                'description' => "Payment processing failed due to error at bank or wallet gateway"
            ],
        ],
        'status_code' => 502,
    ],
    'exception' => [
        'class'               => Rzp\Exception\GatewayErrorException::class,
        'internal_error_code' => ErrorCode::GATEWAY_ERROR_AMOUNT_TAMPERED
    ],
],
];
