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

    'testBajajFinservEmiPartialRefundTest' => [
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

    'testBajajFinservEmiFailedRefundTest' => [
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

    'testBajajFinservEmiFullRefundTest' => [
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
            'url'    => '/payments/captured/verify',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'not_applicable' => 0,
                'locked_count'   => 0,
                'authorized'     => 0,
                'success'        => 1,
                'timeout'        => 0,
                'error'          => 0,
                'unknown'        => 0
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
