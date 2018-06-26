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
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE,
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
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
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
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED,
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

    'testValidateVpaFailure' => [
        'request'   => [
            'url'       => '/payment/validate/vpa',
            'method'    => 'post',
            'content'   => [
                'vpa' => 'failure@razorpay',
            ]
        ],
        'response'  => [
            'content' => [
                'vpa'       => 'failure@razorpay',
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
];
