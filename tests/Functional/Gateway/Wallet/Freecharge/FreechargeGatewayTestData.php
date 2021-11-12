<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment'               => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'wallet',
        'status'            => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'wallet'            => 'freecharge',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'wallet_freecharge',
        'terminal_id'       => '100FrchrgeTmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testDebitFailedPayment'     => [
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
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        ],
    ],

    'testApplicationErrorOccurred'     => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Your payment didn't go through due to a temporary issue. Any debited amount will be refunded in 4-5 business days.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],


    'testPaymentWithOtpAttempts' => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'wallet',
        'status'            => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'wallet'            => 'freecharge',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'wallet_freecharge',
        'terminal_id'       => '100FrchrgeTmnl',
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
        ],
    ],


    'testExpiredOtpPaymentRedirection' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_OTP_EXPIRED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_EXPIRED,
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
                    'description' => "You've entered an incorrect OTP too many times. Try again in sometime.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED,
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

    'testInsufficientBalancePayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Your payment could not be completed due to insufficient wallet balance. Try another payment method.",
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

    'testPaymentWalletEntity' => [
        'action'                => 'authorize',
        'amount'                => '50000',
        'wallet'                => 'freecharge',
        'received'              => true,
        'email'                 => 'a@b.com',
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'status_code'           => 'SUCCESS',
        'refund_id'             => null,
        'entity'                => 'wallet',
    ],

    'testFailedPaymentWalletEntity' => [
        'action'                => 'authorize',
        'amount'                => '19999',
        'wallet'                => 'freecharge',
        'received'              => false,
        'email'                 => 'a@b.com',
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'entity'                => 'wallet',
    ],

    'testTopupPayment'               => [
        'action'                => 'authorize',
        'amount'                => '100000',
        'wallet'                => 'freecharge',
        'received'              => true,
        'email'                 => 'a@b.com',
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'status_code'           => 'SUCCESS',
        'refund_id'             => null,
        'entity'                => 'wallet',
        'reference2'            => 'true',
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

    'testVerifyFailedPaymentOnGatewayFailure'   => [
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
        'wallet'                => 'freecharge',
        'email'                 => 'a@b.com',
        'amount'                => '50000',
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'status_code'           => 'SUCCESS',
        'entity'                => 'wallet',
    ],

    'testPartialRefundPayment'       => [
        'action'                => 'refund',
        'wallet'                => 'freecharge',
        'email'                 => 'a@b.com',
        'amount'                => '25000',
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'status_code'           => 'SUCCESS',
        'entity'                => 'wallet',
    ],

    'otpRetryRequest' => [
        'request'   => [
            'method'    => 'POST',
            'content'   => [
                'otpId' => '1asda2345',
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
                    'description' => "The payment has already been processed",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED
        ],
    ],
];
