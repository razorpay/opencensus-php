<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testOtpSubmitPayment' => [
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

    'testWrongOtpSubmitPayment' => [
        'request'   => [
            'method'    => 'POST',
            'content'   => [
                'type'  => 'otp',
                'otp'   => '222222'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_OTP_INCORRECT_OR_EXPIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT_OR_EXPIRED,
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
        ],
    ],

    'testBajajFinservVerify' => [
        'request' => [
            'url'    => '/payments/{id}/verify',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'payment' => [
                    'status'   => 'authorized',
                    'verified' => 1,
                ]
            ],
        ],
    ],

    'testCapturePaymentForBajaj' => [
        'request' => [
            'method' => 'post',
            'content' => [
                'amount' => 10000
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],
];
