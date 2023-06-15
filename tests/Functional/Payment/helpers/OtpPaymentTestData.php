<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testHeadlessOtpAuthenticationPaymentWithout3ds' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => 'The gateway request to submit payment information timed out. Please submit your details again'
                ],
            ],
            'status_code' => 504,
        ],
        'exception' => [
            'class'               => \RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => 'GATEWAY_ERROR_REQUEST_TIMEOUT',
        ]
    ],

    'testHeadlessOtpAuthenticationWithNoTerminal' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.'
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => \RZP\Exception\RuntimeException::class,
            'internal_error_code' => 'SERVER_ERROR_RUNTIME_ERROR',
        ]
    ],

    'testOtpAuthPaymentWithCardNotSupported' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The otp authentication type is not applicable on the given card'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ]
    ],

    'testIvrAuthenticationPaymentWithHeadlessFeature' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The otp authentication type is not applicable on the given card'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ]
    ],

    'testHeadlessRedirectInvalidAuthType' => [
        'request' => [
            'url' => '',
            'method' => 'POST',
        ],
        'response'  => [
            'content'     => [

            ],
        ],
    ],
    'testIciciOtpAuth' => [
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
    'testIciciIncorrectOtp' => [
        'request'   => [
            'method'    => 'POST',
            'content'   => [
                'type'  => 'otp',
                'otp'   => '111111'
            ]
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 400,
        ],
    ],
];
