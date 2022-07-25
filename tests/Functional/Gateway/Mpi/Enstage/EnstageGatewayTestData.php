<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testSuccessfullyEnrolledCard' => [
        'entity'               => 'mpi',
        'action'               => 'authorize',
        'amount'               => 50000,
        'currency'             => 'INR',
        'cavv'                 => 'AAABA5IAAGmTFAYTlAAAAAAAAAA',
        'accID'                => '201611181642092180hE7iE9oZ',
        'enrolled'             => 'Y',
        'eci'                  => '05',
        'response_code'        => '000',
        'response_description' => 'Success',
        'gateway_payment_id'   => 'TVBJWElENXdYN3ZVOGlQMm1FM2Y',
        'gateway'              => 'mpi_enstage',
    ],

    'testAuthenticationError' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => "Your payment could not be completed due to incorrect OTP or verification details. Try another payment method or contact your bank for details.",
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_PAYMENT_AUTHENTICATION_ERROR,
        ],
    ],

    'testInvalidCheckSum' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => RZP\Exception\RuntimeException::class,
            'internal_error_code'   => 'SERVER_ERROR_RUNTIME_ERROR',
        ],

    ],

    'testAuthenticationFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
        ],
    ],
];
