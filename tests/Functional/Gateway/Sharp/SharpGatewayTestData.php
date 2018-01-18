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
];