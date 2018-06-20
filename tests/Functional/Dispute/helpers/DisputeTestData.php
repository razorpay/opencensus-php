<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testDisputeCreate' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 100,
                'deduct_at_onset'      => 0,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'amount'             => 100,
                'amount_deducted'    => 0,
                'currency'           => 'INR',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_code'        => 'KFRER_R',
            ],
        ],
    ],

    'testDisputeCreateMerchantMail' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 100,
                'deduct_at_onset'      => 0,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'amount'             => 100,
                'amount_deducted'    => 0,
                'currency'           => 'INR',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_code'        => 'KFRER_R',
            ],
        ],
    ],

    'testDisputeCreateWithoutMerchantEmail' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 100,
                'deduct_at_onset'      => 0,
                'phase'                => 'chargeback',
                'skip_email'            => 1,
            ],
        ],
        'response' => [
            'content' => [
                'amount'             => 100,
                'amount_deducted'    => 0,
                'currency'           => 'INR',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_code'        => 'KFRER_R',
            ],
        ],
    ],

    'testDisputeCreatedWebhook' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id' => '4342frf34r',
                'raised_on'          => '946684800',
                'expires_on'         => 946684801,
                'amount'             => 50000,
                'deduct_at_onset'    => 0,
                'phase'              => 'chargeback',
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testDisputeCreatedWebhookEventData' => [
        'entity'   => 'event',
        'event'    => 'payment.dispute.created',
        'contains' => [
            'payment',
            'dispute',
        ],
        'payload' => [
            'payment' => [
                'entity' => [
                    'entity'     => 'payment',
                    'amount'     => 50000,
                    'currency'   => 'INR',
                    'status'     => 'captured',
                    'captured'   => true,
                ],
            ],
            'dispute' => [
                'entity' => [
                    'entity'             => 'dispute',
                    'amount'             => 50000,
                    'amount_deducted'    => 0,
                    'currency'           => 'INR',
                    'gateway_dispute_id' => '4342frf34r',
                    'respond_by'         => 946684801,
                    'status'             => 'open',
                    'reason_code'        => 'KFRER_R',
                ],
            ],
        ],
    ],

    'testDisputeLostEventData' => [
        'entity'   => 'event',
        'event'    => 'payment.dispute.lost',
        'contains' => [
            'payment',
            'dispute',
        ],
        'payload' => [
            'payment' => [
                'entity' => [
                    'entity'     => 'payment',
                    'amount'     => 1000000,
                    'currency'   => 'INR',
                    'status'     => 'captured',
                    'captured'   => true,
                ],
            ],
            'dispute' => [
                'entity' => [
                    'entity'             => 'dispute',
                    'amount'             => 1000000,
                    'amount_deducted'    => 1000000,
                    'currency'           => 'INR',
                    'status'             => 'lost',
                    'reason_code'        => 'SOMETHING_BAD',
                ],
            ],
        ],
    ],

    'testDisputeWonEventData' => [
        'entity'   => 'event',
        'event'    => 'payment.dispute.won',
        'contains' => [
            'payment',
            'dispute',
        ],
        'payload' => [
            'payment' => [
                'entity' => [
                    'entity'     => 'payment',
                    'amount'     => 1000000,
                    'currency'   => 'INR',
                    'status'     => 'captured',
                    'captured'   => true,
                ],
            ],
            'dispute' => [
                'entity' => [
                    'entity'             => 'dispute',
                    'amount'             => 1000000,
                    'amount_deducted'    => 0,
                    'currency'           => 'INR',
                    'status'             => 'won',
                    'reason_code'        => 'SOMETHING_BAD',
                ],
            ],
        ],
    ],

    'testDisputeWonEventPostDeductData' => [
        'entity'   => 'event',
        'event'    => 'payment.dispute.won',
        'contains' => [
            'payment',
            'dispute',
        ],
        'payload' => [
            'payment' => [
                'entity' => [
                    'entity'     => 'payment',
                    'amount'     => 1000000,
                    'currency'   => 'INR',
                    'status'     => 'captured',
                    'captured'   => true,
                ],
            ],
            'dispute' => [
                'entity' => [
                    'entity'             => 'dispute',
                    'amount'             => 1000000,
                    'amount_deducted'    => 0,
                    'currency'           => 'INR',
                    'status'             => 'won',
                    'reason_code'        => 'SOMETHING_BAD',
                ],
            ],
        ],
    ],

    'testDisputeClosedEventData' => [
        'entity'   => 'event',
        'event'    => 'payment.dispute.closed',
        'contains' => [
            'payment',
            'dispute',
        ],
        'payload' => [
            'payment' => [
                'entity' => [
                    'entity'     => 'payment',
                    'amount'     => 1000000,
                    'currency'   => 'INR',
                    'status'     => 'captured',
                    'captured'   => true,
                ],
            ],
            'dispute' => [
                'entity' => [
                    'entity'             => 'dispute',
                    'amount'             => 1000000,
                    'amount_deducted'    => 0,
                    'currency'           => 'INR',
                    'status'             => 'closed',
                    'reason_code'        => 'SOMETHING_BAD',
                ],
            ],
        ],
    ],

    'testDisputeCreateWithDeduct' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 100,
                'deduct_at_onset'      => 1,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'amount'             => 100,
                'amount_deducted'    => 100,
                'currency'           => 'INR',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_code'        => 'KFRER_R',
            ],
        ],
    ],

    'testDisputeCreateWithDeductWithoutEnoughBalance' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'deduct_at_onset'      => 1,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Merchant does not have enough balance for negative adjustment',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE_FOR_ADJUSTMENT,
        ],
    ],

    'testDisputeCreateWithoutReason' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 100,
                'deduct_at_onset'      => 1,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'reason_id should be sent in the request to create a dispute.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeCreateWithExtraFields' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
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
                'expires_on'           => '1912162918',
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
                'expires_on'           => '1912162918',
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
                'expires_on'           => '1912162918',
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
                'expires_on'           => '1912162918',
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

    'testDisputeCreateWithParent' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 100,
                'deduct_at_onset'      => 0,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'amount'             => 100,
                'amount_deducted'    => 0,
                'currency'           => 'INR',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_code'        => 'KFRER_R',
            ],
        ],
    ],

    'testDisputeCreateWithDuplicateParent' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 100,
                'deduct_at_onset'      => 0,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The parent dispute is linked to another dispute entity.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeCreateWithNonArrayMerchantEmail' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 100,
                'deduct_at_onset'      => 0,
                'phase'                => 'chargeback',
                'merchant_emails'      => 'wrongEmail',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The merchant emails must be an array.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeCreateWithInvalidMerchantEmail' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 100,
                'deduct_at_onset'      => 0,
                'phase'                => 'chargeback',
                'merchant_emails'      => ['right@email.com', 'andWrong'],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The merchant_emails.1 must be a valid email address.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeCreateWithWhitespaceMerchantEmail' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 100,
                'deduct_at_onset'      => 0,
                'phase'                => 'chargeback',
                'merchant_emails'      => [' '],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The merchant_emails.0 field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeCreateNonTransactionalPhaseDeductAtOnset' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 100,
                'deduct_at_onset'      => 1,
                'phase'                => 'fraud',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Deduct at onset cannot be done for disputes in phase fraud',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeEdit' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'                 => 'under_review',
                'expires_on'             => '1912162918',
                'gateway_dispute_status' => 'processing'
            ],
        ],
        'response' => [
            'content' => [
                'amount'          => 1000000,
                'amount_deducted' => 0,
                'currency'        => 'INR',
                'phase'           => 'chargeback',
                'status'          => 'under_review'
            ],
        ],
    ],

    'testDisputeEditWon' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'                 => 'won',
                'expires_on'             => '1912162918',
                'gateway_dispute_status' => 'processing'
            ],
        ],
        'response' => [
            'content' => [
                'amount'          => 1000000,
                'amount_deducted' => 0,
                'currency'        => 'INR',
                'phase'           => 'chargeback',
                'status'          => 'won'
            ],
        ],
    ],

    'testDisputeEditWonPostDeduct' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'                 => 'won',
                'expires_on'             => '1912162918',
                'gateway_dispute_status' => 'processing'
            ],
        ],
        'response' => [
            'content' => [
                'amount'          => 1000000,
                'amount_deducted' => 1000000,
                'currency'        => 'INR',
                'phase'           => 'chargeback',
                'status'          => 'won'
            ],
        ],
    ],

    'testDisputeEditClose' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'                 => 'closed',
                'expires_on'             => '1912162918',
                'gateway_dispute_status' => 'processing'
            ],
        ],
        'response' => [
            'content' => [
                'amount'          => 1000000,
                'amount_deducted' => 0,
                'currency'        => 'INR',
                'phase'           => 'chargeback',
                'status'          => 'closed'
            ],
        ],
    ],

    'testDisputeEditDeductOnLost' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status' => 'lost',
            ],
        ],
        'response' => [
            'content' => [
                'amount'          => 1000000,
                'amount_deducted' => 1000000,
                'currency'        => 'INR',
                'phase'           => 'chargeback',
                'status'          => 'lost'
            ],
        ],
    ],

    'testMerchantEditWhenDisputeUnderReview' => [
        'request'   => [
            'method'  => 'post',
            'content' => [
                'accept_dispute' => true
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Disputes can only be modified when in open status',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantEditAcceptAndSubmit' => [
        'request'   => [
            'method'  => 'post',
            'content' => [
                'submit'         => true,
                'accept_dispute' => true
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only one of the fields `accept_dispute` and `submit` can be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeEditDoNotDeductOnLostIfDeducted' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status' => 'lost',
            ],
        ],
        'response' => [
            'content' => [
                'amount'          => 1000000,
                'amount_deducted' => 1000000,
                'currency'        => 'INR',
                'phase'           => 'chargeback',
                'status'          => 'lost'
            ],
        ],
    ],

    'testDisputeEditInvalidStatus' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'                 => 'review',
                'expires_on'             => '1912162918',
                'gateway_dispute_status' => 'processing'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid dispute status: review',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeEditExtraInput' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'                 => 'under_review',
                'expires_on'             => '1912162918',
                'gateway_dispute_status' => 'processing',
                'phase'                  => 'chargeback'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'phase is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testDisputeEditClosed' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'                 => 'under_review',
                'expires_on'             => '1912162918',
                'gateway_dispute_status' => 'processing',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CANNOT_UPDATE_CLOSED_DISPUTE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CANNOT_UPDATE_CLOSED_DISPUTE,
        ],
    ],

    'testDisputeReversalWinLogic' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'        => 'won'
            ],
        ],
        'response' => [
            'content' => [
                'amount_deducted' => 10100,
                'amount_reversed' => 10100,
            ],
        ],
    ],

    'testDisputeReversalLostLogic' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'        => 'lost'
            ],
        ],
        'response' => [
            'content' => [
                'amount_deducted' => 10100
            ],
        ],
    ],

    'testDisputeEditForNoInitialParent' => [
        'request' => [
            'method'  => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDisputeEditWithExistingParent' => [
        'request' => [
            'method'  => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testDisputeEditReplaceParent' => [
        'request' => [
            'method'  => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDisputeEditReplaceParentWithAlreadyLinkedParent' => [
        'request' => [
            'method'  => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The parent dispute is linked to another dispute entity.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeLostPartiallyAccepted' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'                    => 'lost',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDisputeLostPartiallyAcceptedForNoOnsetDeduct' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'                    => 'lost',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDisputeLostPartiallyAcceptedWithInvalidAcceptedAmount' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'                    => 'lost',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Accepted chargeback amount cannot be greater than disputed amount.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeLostPartiallyAcceptedWithZeroAcceptedAmount' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'                    => 'lost',
                'accepted_amount'           => 0,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The accepted amount must be at least 100.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testNonTransactionalDisputeInvalidClose' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'        => 'lost'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Non-transactional disputes can only be closed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeFetchForMerchant' => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/disputes',
        ],
        'response'  => [
            'content'       => [
                'count'         => 2,
                'items'         => [
                    [
                        'amount'            => 1000000,
                        'currency'          => 'INR',
                        'reason_code'       => 'SOMETHING_BAD',
                        'status'            => 'open',
                        'phase'             => 'chargeback',
                    ],
                    [
                        'amount'            => 1000000,
                        'currency'          => 'INR',
                        'reason_code'       => 'SOMETHING_BAD',
                        'status'            => 'open',
                        'phase'             => 'chargeback',
                    ],
                ]
            ],
        ],
    ],

    'testDisputeFetchForAdmin'    => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/admin/dispute?expand[]=payment',
        ],
        'response'  => [
            'content'       => [
                'count'         => 2,
                'items'         => [
                    [
                        'amount'            => 1000000,
                        'currency'          => 'INR',
                        'reason_code'       => 'SOMETHING_BAD',
                        'status'            => 'open',
                        'phase'             => 'chargeback',
                        'payment'           => [
                            'status'          => 'captured',
                            'amount_refunded' => 0,
                        ],
                    ],
                    [
                        'amount'            => 1000000,
                        'currency'          => 'INR',
                        'reason_code'       => 'SOMETHING_BAD',
                        'status'            => 'open',
                        'phase'             => 'chargeback',
                        'payment'           => [
                            'status'          => 'captured',
                            'amount_refunded' => 0,
                        ],
                    ],
                ]
            ],
        ],
    ],

    'testFetchMerchantDetails' => [
        'request' => [
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'amount'        => 1000000,
                'currency'      => 'INR',
                'reason_code'   => 'SOMETHING_BAD',
                'status'        => 'open',
                'phase'         => 'chargeback',
                'respond_by'    => 12345678,
            ],
            'status_code' => 200,
        ],
    ],

    'testEditDisputeMerchantDocumentUploadByProxy' => [
        'request' => [
            'content' => [
                'upload_files'  =>  [
                    [
                        'name'      => 'myfile1.png',
                        'category'  => 'explanation_letter',
                    ],
                    [
                        'name'      => 'myfile2.pdf',
                        'category'  => 'delivery_proof',
                    ],
                ],
            ],
            'method' => 'post',
            'files' => [],
        ],
        'response' => [
            'content' => [
                'entity'      => 'dispute',
                'amount'      => 1000000,
                'currency'    => 'INR',
                'reason_code' => 'SOMETHING_BAD',
                'status'      => 'open',
                'phase'       => 'chargeback',
                'files'       => [
                    'entity' => 'collection',
                    'count'  => 2,
                    'items'  => [
                        [
                            'file_id'  => 'rzp_file_mock_id_1000000_explanation_letter',
                            'name'     => 'myfile1.png',
                            'category' => 'explanation_letter',
                        ],
                        [
                            'file_id'  => 'rzp_file_mock_id_1000000_delivery_proof',
                            'name'     => 'myfile2.pdf',
                            'category' => 'delivery_proof',
                        ],
                    ]
                ],
            ],
        ],
    ],

    'testDisputeFetchWithFiles' => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/disputes',
        ],
        'response'  => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'amount'      => 1000000,
                        'currency'    => 'INR',
                        'reason_code' => 'SOMETHING_BAD',
                        'status'      => 'open',
                        'phase'       => 'chargeback',
                        'files'       => [
                            'entity' => 'collection',
                            'count'  => 2,
                            'items'  => [
                                [
                                    'file_id'  => 'rzp_file_mock_id_1000000_explanation_letter',
                                    'name'     => 'myfile1.png',
                                    'category' => 'explanation_letter',
                                ],
                                [
                                    'file_id'  => 'rzp_file_mock_id_1000000_delivery_proof',
                                    'name'     => 'myfile2.pdf',
                                    'category' => 'delivery_proof',
                                ],
                            ]
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testEditDisputeFileUploadSaveForLater' => [
        'request' => [
            'content' => [
                'upload_files'  =>  [
                    [
                        'name'      => 'myfile1.png',
                        'category'  => 'explanation_letter',
                    ],
                    [
                        'name'      => 'myfile2.pdf',
                        'category'  => 'delivery_proof',
                    ],
                ],
            ],
            'method' => 'post',
            'files' => [],
        ],
        'response' => [
            'content' => [
                'entity'        => 'dispute',
                'amount'        => 1000000,
                'currency'      => 'INR',
                'reason_code'   => 'SOMETHING_BAD',
                'status'        => 'open',
                'phase'         => 'chargeback',
                'files'         => [],
            ],
        ],
    ],

    'testEditDisputeFileUploadSaveForLaterAfterSave' => [
        'request' => [
            'content' => [
                'upload_files'  =>  [
                    [
                        'name'      => 'myfile1.png',
                        'category'  => 'instant_services',
                    ],
                    [
                        'name'      => 'myfile2.pdf',
                        'category'  => 'others',
                    ],
                ],
            ],
            'method' => 'post',
            'files' => [],
        ],
        'response' => [
            'content' => [
                'entity'        => 'dispute',
                'amount'        => 1000000,
                'currency'      => 'INR',
                'reason_code'   => 'SOMETHING_BAD',
                'status'        => 'under_review',
                'phase'         => 'chargeback',
                'files'         => [],
            ],
        ],
    ],

    'testEditDisputeMerchantAcceptDispute' => [
        'request' => [
            'content' => [
                'accept_dispute'    => true,
            ],
            'method' => 'post',
        ],
        'response' => [
            'content' => [
                'amount'            => 10100,
                'status'            => 'lost',
                'phase'             => 'chargeback',
            ],
        ],
    ],

    'testEditDisputeMerchantAcceptDisputeForNonTransactional' => [
        'request' => [
            'content' => [
                'accept_dispute'    => true,
            ],
            'method' => 'post',
        ],
        'response' => [
            'content' => [
                'amount'            => 10100,
                'status'            => 'closed',
                'phase'             => 'fraud',
            ],
        ],
    ],
];
