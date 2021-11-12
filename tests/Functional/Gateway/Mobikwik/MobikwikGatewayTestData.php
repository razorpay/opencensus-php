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
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'mobikwik',
        'terminal_id'       => '1000MobiKwikTl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
    ],

    'testPowerWalletPayment'     => [
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
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'mobikwik',
        'terminal_id'       => '1000MobiKwikTl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
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
        'wallet'            => 'mobikwik',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'mobikwik',
        'terminal_id'       => '1000MobiKwikTl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => 1
    ],

    'testPowerWalletOtpRetryPayment'     => [
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
                    'description' => "You've entered an incorrect OTP too many times. Try again in sometime.",
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
                '_' => [
                    'source' => 'checkoutjs'
                ]
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

    'testPaymentMobikwikEntity' => [
        'action'        => 'authorize',
        'method'        => 'wallet',
//        'orderid' => '',
//        'merchantname' => '',
        'email'         => 'a@b.com',
        'amount'        => 50000,
        'cell'          => '9918899029',
        'showmobile'    => null,
        'statuscode'    => '0',
        'statusmessage' => 'Transaction completed Successfully',
//        'refid',
//        'ispartial'
        'refund_id'     => null,
        'entity'        => 'mobikwik',
    ],

    'testMobikwikWallet'        => [
        'merchant_id' => '10000000000000',
        'amount'      => 50000,
        'method'      => 'wallet',
        'wallet'      => 'mobikwik',
        'status'      => 'captured',
        'gateway'     => 'mobikwik',
        'terminal_id' => '1000MobiKwikTl',
        'signed'      => false,
        'verified'    => null,
        'entity'      => 'payment',
    ],

    'testMobikwikWalletEntity'  => [
        'action'        => 'authorize',
        'method'        => 'wallet',
//        'orderid' => '',
//        'merchantname' => '',
        'email'         => 'a@b.com',
        'amount'        => 50000,
        'cell'          => '9918899029',
        'showmobile'    => null,
        'statuscode'    => '0',
//        'refid',
//        'ispartial'
        'refund_id'     => null,
        'entity'        => 'mobikwik',
    ],

    'testPayment3dsecureFailed' => [
        'request'   => [
            'content' => [
                'card' => [
                    'number' => '4012001036275556',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
            'gateway_error_code'  => null
        ],
    ],

    'testPaytmWhenNotEnabled'   => [
        'request'   => [
            'url'     => '/payments',
            'content' => [
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_WALLET_NOT_ENABLED_FOR_MERCHANT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_ENABLED_FOR_MERCHANT
        ],
    ],

    'testPowerWalletVerifyFailedPayment'   => [
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

    'testOtpResendOnFailedPayment' => [
        'request'   => [
            'method'    => 'POST',
            'content'   => [
                '_' => [
                    'source' => 'checkoutjs'
                ]
            ]
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED
        ],
    ],

    'testRefundPayment'         => [
        'action'        => 'refund',
        'method'        => 'wallet',
//        'orderid' => '',
//        'merchantname' => '',
        'email'         => 'a@b.com',
        'amount'        => 50000,
        'cell'          => null,
        'showmobile'    => null,
        'statuscode'    => '0',
        'statusmessage' => 'Some message',
//        'refid',
//        'ispartial'
//        'refund_id' => null,
        'entity'        => 'mobikwik',
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

    'testNonExistingWalletUserPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE
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
];
