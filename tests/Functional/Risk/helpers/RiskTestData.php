<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testFraudDetected' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
            'public_error_description' => ''
        ],
    ],

    'testFetchMultiple' => [
        'request' => [
            'url' =>'/risk?',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 1,
            ]
        ]
    ],

    'testMarkSuspectedFraudPaymentConfirmed' => [
        'request' => [
            'url' =>'/risk/%s',
            'method'  => 'PATCH',
            'content' => [
                'reason' => 'PAYMENT_BLOCKED_BY_OPS',
                'fraud_type' => 'confirmed',
            ],
        ],
        'response' => [
            'content' => [
                'fraud_type' => 'confirmed',
            ],
            'status_code' => 200,
        ],
    ],

    'testFailRiskEdit' => [
        'request' => [
            'url' =>'/risk/%s',
            'method'  => 'PATCH',
            'content' => [
                'reason' => 'PAYMENT_BLOCKED_BY_GATEWAY',
                'fraud_type' => 'suspected',
            ],

        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'message' => 'Cannot edit confirmed risk entities',
        ],
    ],

    'testCreate' => [
        'request' => [
            'url' =>'/risk',
            'method'  => 'POST',
            'content' => [
                'fraud_type'  => 'confirmed',
                'reason'      => 'CHARGEBACK_RESOLVED',
                'source'      => 'manual',
            ],

        ],
        'response' => [
            'content' => [
                'fraud_type' => 'confirmed',
            ],
            'status_code' => 200,
        ],
    ],

    'testBlockedBin' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
            'public_error_description' => ''
        ],
    ],
];
