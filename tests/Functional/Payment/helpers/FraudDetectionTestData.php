<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testBlockedBin' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                    => RZP\Exception\BadRequestException::class,
            'internal_error_code'      => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
            'public_error_description' => ''
        ],
    ],

    'testFraudDetected' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                    => RZP\Exception\BadRequestException::class,
            'internal_error_code'      => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
            'public_error_description' => ''
        ],
    ],

    'testFraudDetectedWithInvalidEmailTld' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFraudDetectedByShieldWebsiteMismatch' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This business is not allowed to accept payments on this website. We suggest not going ahead with the payment.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                    => RZP\Exception\BadRequestException::class,
            'internal_error_code'      => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_WEBSITE_MISMATCH,
        ],
    ],

    'runFraudDetectionForAppUrl' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 200,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
        ],
    ],

    'testFraudDetectionFailedByShieldDetectedByMaxMind' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
        ],
    ],

    'testInternationalCardFraudFailedByShield' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
        ],
    ],

    'testFraudDetectedNotificationToOps' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                    => RZP\Exception\BadRequestException::class,
            'internal_error_code'      => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
        ],
    ],
];
