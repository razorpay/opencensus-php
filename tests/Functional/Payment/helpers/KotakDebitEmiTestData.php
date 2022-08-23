<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testKotakDebitEmiPaymentSuccess' => [
        'request'   => [
            'method'    => 'POST',
            'content'   => [
                'type'  => 'otp',
                'otp'   => '111111'
            ]
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],
    'testKotakDebitEmiPaymentIncorrectOtp' => [
        'request'   => [
            'method'    => 'POST',
            'content'   => [
                'type'  => 'otp',
                'otp'   => '111111'
            ]
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENTS_INVALID_OTP_TRY_NEW,
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 400,
        ],
    ],
    'testKotakDebitEmiPaymentOtpExceedLimit' => [
        'request'   => [
            'method'    => 'POST',
            'content'   => [
                'type'  => 'otp',
                'otp'   => '111111'
            ]
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED,
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 400,
        ],
    ],
];
