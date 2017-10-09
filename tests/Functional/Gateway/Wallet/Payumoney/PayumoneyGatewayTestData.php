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
        'wallet'            => 'payumoney',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'wallet_payumoney',
        'terminal_id'       => '100PayumnyTmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testPaymentWithOtpAttempts' => [
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
        'wallet'            => 'payumoney',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'wallet_payumoney',
        'terminal_id'       => '100PayumnyTmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => 1
    ],

    'testOtpRetryPayment'       => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
                    'action'      => 'RETRY'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
            'two_fa_error' => true,
        ],
    ],

    'testFailedPaymentWithRedirection' => [
        'error' => [
            'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
            'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_OTP_EXPIRED
        ],
    ],

    'testOtpRetrySuccessPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
                    'action'      => 'RETRY'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
            'two_fa_error' => true,
        ],
    ],

    'testOtpRetryExceededPayment' => [
        'request'   => [
            'method'    => 'POST',
            'content'   => [
                'type'  => 'otp',
                'otp'   => '121212'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED,
            'two_fa_error' => true,
        ],
    ],

    'testOtpResendPayment' => [
        'request'   => [
            'method'    => 'POST',
            'content'   => [

            ]
        ],
        'response'  => [
            'content'     => [
                'type' => 'otp',
                'request' => [
                    'method' => 'post'
                ],
            ],
            'status_code' => 200,
        ]
    ],

    'testOtpResendOnAuthorizedPayment' => [
        'request'   => [
            'method'    => 'POST',
            'content'   => []
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
        ],
    ],

    'testInsufficientBalancePayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE,
                    'action'      => 'TOPUP'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE,
        ],
    ],

    'testTopupFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testDebitFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testOtpGenerateFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testWalletLimitExceededPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_WALLET_PER_PAYMENT_AMOUNT_CROSSED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_PER_PAYMENT_AMOUNT_CROSSED,
        ],
    ],

    'testPaymentWalletEntity' => [
        'action'                => 'authorize',
        'amount'                => '50000',
        'wallet'                => 'payumoney',
        'received'              => true,
        'email'                 => 'a@b.com',
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'status_code'           => '0',
        'refund_id'             => null,
        'entity'                => 'wallet',
    ],

    'testTopupPayment'               => [
        'action'                => 'authorize',
        'amount'                => '100000',
        'wallet'                => 'payumoney',
        'received'              => true,
        'email'                 => 'a@b.com',
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'status_code'           => '0',
        'refund_id'             => null,
        'entity'                => 'wallet',
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

    'testVerifyPaymentMismatch' => [
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],

    'testRefundPayment'       => [
        'action'                => 'refund',
        'wallet'                => 'payumoney',
        'email'                 => 'a@b.com',
        'amount'                => '50000',
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'response_code'         => '',
        'response_description'  => 'Refund Initiated',
        'status_code'           => '0',
        'entity'                => 'wallet',
    ],

    'testPartialRefundPayment'      => [
        'action'                => 'refund',
        'wallet'                => 'payumoney',
        'email'                 => 'a@b.com',
        'amount'                => '10000',
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'gateway_refund_id'     => '123456',
        'response_code'         => '',
        'response_description'  => 'Refund Initiated',
        'status_code'           => '0',
        'entity'                => 'wallet',
    ],

    'otpRetryRequest' => [
        'request'   => [
            'method'    => 'POST',
            'content'   => [
                'type'  => 'otp',
                'otp'   => '111111'
            ]
        ]
    ],

    'topupData'     => [
        'request' => [
            'content'   => [],
            'method'    => 'POST'
        ],
        'response' => [
            'content' => [
                'type'  => 'first',
                'request' => [
                    'method'    => 'post',
                    'content'   => []
                ],
                'version'   => 1
            ]
        ]
    ],

    'topupDataAlreadyProcessed'  => [
        'request' => [
            'content'   => [],
            'method'    => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED
        ],
    ]
];
