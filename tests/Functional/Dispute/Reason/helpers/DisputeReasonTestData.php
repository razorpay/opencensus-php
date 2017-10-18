<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testReasonCreate' => [
        'request' => [
            'url'     => '/disputes/reasons',
            'method'  => 'post',
            'content' => [
                'network'             => 'Visa',
                'code'                => 'rzp_code',
                'description'         => 'rzp defined description',
                'gateway_code'        => 'gateway_code',
                'gateway_description' => 'gateway defined description',
            ],
        ],
        'response' => [
            'content' => [
                'code'        => 'rzp_code',
                'description' => 'rzp defined description',
            ],
        ],
    ],

    'testDisputeCreateWithMissingArgument' => [
        'request' => [
            'url'     => '/disputes/reasons',
            'method'  => 'post',
            'content' => [
                'network'             => 'Visa',
                'code'                => 'rzp_code',
                'description'         => 'rzp defined description',
                'gateway_description' => 'gateway defined description',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The gateway code field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeCreateWithInvalidNetwork' => [
        'request' => [
            'url'     => '/disputes/reasons',
            'method'  => 'post',
            'content' => [
                'network'             => 'india',
                'code'                => 'rzp_code',
                'description'         => 'rzp defined description',
                'gateway_code'        => 'gateway_code',
                'gateway_description' => 'gateway defined description',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Network is invalid: india',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeCreateWithExtraLongDescription' => [
        'request' => [
            'url'     => '/disputes/reasons',
            'method'  => 'post',
            'content' => [
                'network'             => 'Visa',
                'code'                => 'rzp_code',
                'description'         => 'rzp defined description',
                'gateway_code'        => 'gateway_code',
                'gateway_description' => 'gateway defined description which is terribly long.
                                            Makes you wonder what they were thinking. How did
                                            they even do that. It is so hard to even mock. I
                                            am not sure this is long enough.',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The gateway description may not be greater than 255 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ]
];
