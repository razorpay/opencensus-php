<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testDisputeCreate' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1102444800',
                'amount'               => 100,
                'deduct_at_onset'      => 1,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'        => '10000000000000',
                'amount'             => 100,
                'currency'           => 'INR',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_description' => 'This is a serious fraud',
            ],
        ],
    ],

    'testDisputeCreateWithExtraFields' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1102444800',
                'amount'               => 100,
                'deduct_at_onset'      => 1,
                'phase'                => 'chargeback',
                'status'               => 'something',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'status is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testDisputeCreateOnDisputedPayment' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'gateway_dispute_code' => '4342',
                'raised_on'            => '946684800',
                'expires_on'           => '1102444800',
                'amount'               => 100,
                'deduct_at_onset'      => 1,
                'phase'                => 'chargeback',
                'reason_code'          => 'processed_expired_card',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment already has an open dispute',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_UNDER_DISPUTE,
        ],
    ],

    'testDisputeCreateWithAmountGreaterThanPayment' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'gateway_dispute_code' => '4342',
                'raised_on'            => '946684800',
                'expires_on'           => '1102444800',
                'amount'               => 1000060,
                'deduct_at_onset'      => 1,
                'phase'                => 'chargeback',
                'reason_code'          => 'processed_expired_card',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Disputed amount cannot be greater than payment amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_DISPUTE_AMOUNT_GREATER_THAN_PAYMENT_AMOUNT,
        ],
    ],

    'testDisputeCreateWithAmountLessThanMin' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1102444800',
                'amount'               => 10,
                'deduct_at_onset'      => 1,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Minimum transaction amount allowed is Re. 1',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeCreateWithInvalidPhase' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1102444800',
                'amount'               => 1000,
                'deduct_at_onset'      => 1,
                'phase'                => 'dispute',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid dispute phase: dispute',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
