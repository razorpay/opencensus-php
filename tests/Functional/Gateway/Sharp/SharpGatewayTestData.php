<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testRecurringHardDeclinePaymentAuthenticateCard' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_CARD_STOLEN_OR_LOST,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST,
        ],
    ],

    'testRecurringSoftDeclinePaymentAuthenticateCard' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE,
        ],
    ],

    'testPaymentFailed' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payments',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'gateway_error_code'  => null
        ],
    ],

    'testEmandatePaymentWithoutOrderId' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED,
            'gateway_error_code'  => null
        ],
    ],

    'testEmandatePaymentWithDifferentOrderAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH,
            'gateway_error_code'  => null
        ],
    ],

    'testAadhaarEmandatePaymentInvalidBank' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_INVALID_BANK_CODE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_BANK_CODE,
            'gateway_error_code'  => null
        ],
    ],

    'testOtpFlowInsufficientBalancePayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Your payment could not be completed due to insufficient wallet balance. Try another payment method.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE,
            'gateway_error_code'  => null
        ],
    ],

    'testOtpFlowIncorrectOtpPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment processing failed because of incorrect OTP',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
            'gateway_error_code'  => null
        ],
    ],

    'testOtpFlowOtpExpiredPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_OTP_EXPIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_EXPIRED,
            'gateway_error_code'  => null
        ],
    ],

    'testOtpFlowAttemptsExceededPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "You've entered an incorrect OTP too many times. Try again in sometime.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED,
            'gateway_error_code'  => null
        ],
    ],

    'testValidateVpaSuccess' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'success@razorpay',
            ]
        ],
        'response'  => [
            'content' => [
                'vpa'       => 'success@razorpay',
                'success'   => true,
            ],
        ]
    ],


    'testValidateVpaCardNumberLikeVpa' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => []
        ],
        'response'  => [
            'content' => [
                'success'   => true,
            ],
        ]
    ],

    'testValidateVpaFailure' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'invalidvpa@razorpay',
            ]
        ],
        'response'  => [
            'content' => [
                'vpa'       => 'invalidvpa@razorpay',
                'success'   => false,
            ],
        ]
    ],

    'testValidateVpaInvalid' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'razorpay',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        ],
    ],

    'testValidateVpaStrUpper' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'razorpay@Airtel',
            ]
        ],
        'response'  => [
            'content' => [
                'vpa'       => 'razorpay@Airtel',
                'success'   => true,
            ],
        ],
    ],

    'testValidateVpaForForbiddenMerchant' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'razorpay',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'googlePayPaymentCreateRequestData' => [
        'contact'       => '9876543210',
        'email'         => 'abc@gmail.com',
        'currency'      => 'INR',
        'method'        => 'card',
        'application'   => 'google_pay',
        '_'             => [
            'checkout_id'           => 'BY486x1wJh2nFj',
            'os'                    => 'android',
            'package_name'          => 'com.oyo.consumer',
            'platform'              => 'mobile_sdk',
            'cellular_network_type' => '4G',
            'data_network_type'     => 'cellular',
            'locale'                => 'en-',
            'library'               => 'custom',
            'library_version'       => '3.6.0'
        ],
    ],

    'testOtmInvalidExecute' => [
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UPI_MANDATE_INVALID_EXECUTION_TIME,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testHeadlessOtpPaymentBlockedCard' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your payment could not be completed as your card is blocked. Try another payment method or contact your bank for details. '
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => 'BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_BLOCKED_CARD',
        ]
    ],

    'testCardlessEmiPayment'               => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'cardless_emi',
        'status'            => 'authorized',
        'two_factor_auth'   => 'not_applicable',
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
        'gateway'           => 'sharp',
        'terminal_id'       => '1000SharpTrmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testPaylaterPayment' => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'paylater',
        'status'            => 'authorized',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'card_id'           => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+917602579721',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'sharp',
        'terminal_id'       => '1000SharpTrmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null,
        'wallet'            => 'icic'
    ],

    'testCardlessEmiPaymentSubProvider'  => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'cardless_emi',
        'status'            => 'authorized',
        'two_factor_auth'   => null,
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
        'wallet'            => 'kkbk',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'sharp',
        'terminal_id'       => '1000SharpTrmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testPaylaterPaymentSubProvider'  => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'paylater',
        'status'            => 'authorized',
        'two_factor_auth'   => null,
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
        'gateway'           => 'sharp',
        'terminal_id'       => '1000SharpTrmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null,
        'wallet'            => 'hdfc'
    ],
];
