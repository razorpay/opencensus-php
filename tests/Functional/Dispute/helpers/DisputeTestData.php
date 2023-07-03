<?php

use RZP\Error\Error;
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

    'testDisputeCreateWithInternalRespondBy' => [
        'request'  => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'  => '4342frf34r',
                'raised_on'           => '946684800',
                'expires_on'          => '1912162918',
                'amount'              => 100,
                'deduct_at_onset'     => 0,
                'phase'               => 'chargeback',
                'internal_respond_by' => 1600000000,
            ],
        ],
        'response' => [
            'content' => [
                'internal_respond_by' => 1600000000,
            ],
        ],
    ],

    'testDomesticDisputeCreateWithExcessGatewayAmount' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'gateway_amount'       => 1000,
                'gateway_currency'     => 'INR',
                'deduct_at_onset'      => 0,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Dispute gateway amount cannot exceed payment amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testInternationalDisputeCreateAudInr' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'gateway_amount'       => 10000,
                'gateway_currency'     => 'INR',
                'deduct_at_onset'      => 0,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'amount'             => 909,
                'amount_deducted'    => 0,
                'currency'           => 'AUD',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_code'        => 'KFRER_R',
            ],
        ],
    ],

    'testLostInternationalDispute' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status' => 'lost',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDisputeCreateWAmountAndGatewayAmount' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 100,
                'gateway_amount'       => 100,
                'gateway_currency'     => 'INR',
                'deduct_at_onset'      => 0,
                'phase'                => 'chargeback',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amount and gateway_amount cannot be sent together',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeCreateWithSkipEmail' => [
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
                    'status'     => 'refunded',
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

    'testDisputeCreateWithDeductRefundRecoveryMethod' => [
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

                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeCreateWithDeductAdjustmentRecoveryMethod' => [
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

    'testDisputeCreateWithDeductAtOnsetMerchantValidationFailure' => [
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
                    'description' => 'Deduct At Onset Dispute can not be created for EXCLUDE_DEDUCT_DISPUTE feature enable Merchant',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeCreateWithDeductAtOnsetGovernmentMerchantValidationFailure' => [
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
                    'description' => 'Deduct At Onset Dispute cannot be created when category2 is government',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeCreateWithDeductAdjustmentRecoveryMethodWithoutEnoughBalance' => [
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
            'content' => [],
            'status_code' => 200,
        ]
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

    'testDisputeEditDeductForNoBalance' => [
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

    'testDisputeEditWithDeductAtOnsetToInternalStatusLostMerchantNotDebited' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'internal_status'        => 'lost_merchant_not_debited',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid internal status provided as merchant is already debited for dispute',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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

    'testDeductAtOnsetRefundedAmountForWin' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'        => 'won'
            ],
        ],
        'response' => [
            'content' => [
                'amount_deducted' => 5000,
                'amount_reversed' => 5000,
                'status' => 'won',
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

    'testDisputeLostPartiallyAcceptedAdjustmentRecoveryMethod' => [
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

    'testDisputeLostPartiallyAcceptedRefundRecoveryMethod' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'                    => 'lost',
                'recovery_method'           => 'refund',
                'internal_status'           => 'lost_merchant_debited',
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

    'testDisputeLostPartiallyAcceptedWithNonInrPayment' => [
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
                    'description' => 'Partial dispute accept not supported for non-inr payments.',
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


    'testDisputeFetchProxyAuth' => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/disputes/disp_1000000dispute?expand[]=transaction.settlement',
        ],
        'response'  => [
            'content' => [
                    'id' => "disp_1000000dispute",
                    'entity' => "dispute",
                    'amount' => 1000000,
                    'currency' => "INR",
                    'amount_deducted' => 1000000,
                    'gateway_dispute_id' => NULL,
                    'reason_code' => "SOMETHING_BAD",
                    'reason_description' => "Something went wrong",
                    'status' => "open",
                    'phase' => "chargeback",
                    'comments' => NULL,
                    'reason'   => [
                        'code'                  => 'KFRER_R',
                        'description'           => 'This is a serious fraud',
                        'network'               => 'Visa',
                        'gateway_code'          => '8fjf',
                        'gateway_description'   => 'Fraud on merchant side',
                    ]
                ]
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
                        'amount'              => 1000000,
                        'currency'            => 'INR',
                        'reason_code'         => 'SOMETHING_BAD',
                        'status'              => 'open',
                        'internal_status'     => 'open',
                        'internal_respond_by' => null,
                        'phase'               => 'chargeback',
                        'payment'             => [
                            'status'          => 'captured',
                            'amount_refunded' => 0,
                        ],
                    ],
                    [
                        'amount'              => 1000000,
                        'currency'            => 'INR',
                        'reason_code'         => 'SOMETHING_BAD',
                        'status'              => 'open',
                        'internal_status'     => 'open',
                        'internal_respond_by' => null,
                        'phase'               => 'chargeback',
                        'payment'             => [
                            'status'          => 'captured',
                            'amount_refunded' => 0,
                        ],
                    ],
                ]
            ],
        ],
    ],

    'testDisputeFetchForAdminInternalStatusParam'    => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/admin/dispute?internal_status=open',
        ],
        'response'  => [
            'content'       => [
                'count'         => 1,
                'items'         => [
                    [
                        'internal_status'     => 'open',
                    ],
                ]
            ],
        ],
    ],

    'testDisputeFetchForAdminGatewayFilter'   => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/admin/dispute?gateway=sharp',
        ],
        'response'  => [
            'content'       => [
                'count'         => 2,
                'items'         => [
                    [
                        'amount'              => 5000,
                    ],
                    [
                        'amount'              => 5000,
                    ],
                ],
            ],
        ],
    ],

    'testDisputeFetchLifecycleForAdmin' => [
        'request' => [
            'url'       => '/admin/dispute/',
            'method'    => 'get',
        ],
        'response' => [
            'content' => [
                'lifecycle' => [
                    [
                        'admin_id'    => 'RzrpySprAdmnId',
                        'merchant_id' => null,
                        'user_id'     => null,
                        'auth_type'   => 'privilege',
                        'app'         => 'admin_dashboard',
                        'change'      => [
                            'new' => [
                                "entity"             => "dispute",
                                "amount"             => 100,
                                "currency"           => "INR",
                                "amount_deducted"    => 0,
                                "gateway_dispute_id" => "4342frf34r",
                                "reason_code"        => "dummy_reason",
                                "reason_description" => "This is a serious fraud",
                                "status"             => "open",
                                "phase"              => "chargeback",
                            ],
                            'old' => null,
                        ],
                    ],
                    [
                        'admin_id'    => 'RzrpySprAdmnId',
                        'merchant_id' => '10000000000000',
                        'user_id'     => null,
                        'auth_type'   => 'private',
                        'app'         => 'admin_dashboard',
                        'change'      => [
                            'old' => [
                                'status'                => 'open',
                                'internal_status'       => 'open',
                                'deduction_source_type' => null,
                            ],
                            'new' => [
                                'status'                => 'lost',
                                'internal_status'       => 'lost_merchant_debited',
                                'deduction_source_type' => 'adjustment',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],



    'testDisputeFetchForAdminInternalRespondByPrioritize'    => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/admin/dispute?order_by_internal_respond=1',
        ],
        'response'  => [
            'content'       => [
                'count'         => 2,
                'items'         => [
                    [
                        'internal_respond_by' => 1500000000,
                    ],
                    [
                        'internal_respond_by' => 1600000000,
                    ],
                ]
            ],
        ],
    ],

    'testDisputeFetchForAdminGatewayDisputeNetwork'    => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/admin/dispute?gateway_dispute_source=network',
        ],
        'response'  => [
            'content'       => [
                'count'         => 1,
                'items'         => [
                    [
                        'gateway_dispute_id' => '4342frf34r',
                    ],
                ]
            ],
        ],
    ],

    'testDisputeFetchForAdminGatewayDisputeCustomer'    => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/admin/dispute?gateway_dispute_source=customer',
        ],
        'response'  => [
            'content'       => [
                'count'         => 1,
                'items'         => [
                    [
                        'gateway_dispute_id' => 'DISPUTE1348184',
                    ],
                ]
            ],
        ],
    ],

    'testDisputeFetchDeductionReversalSetFilter'    => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/admin/dispute?deduction_reversal_at_set=1',
        ],
        'response'  => [
            'content'       => [
                'count'         => 1,
                'items'         => [
                    [

                    ],
                ]
            ],
        ],
    ],

    'testDisputeFetchDeductAtOnsetFilter'    => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/admin/dispute?deduct_at_onset=1',
        ],
        'response'  => [
            'content'       => [
                'count'         => 1,
                'items'         => [
                    [
                        'deduct_at_onset' => true,
                    ],
                ]
            ],
        ],
    ],

    'testDisputeFetchCountProxyAuth'    => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/disputes-count?status=open',
        ],
        'response'  => [
            'content'       => [
                'count'         => 3,
            ],
        ],
    ],

    'testDisputeFetchForAdminInternalRespondByParam'    => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/admin/dispute?internal_respond_by_from=1550000000&internal_respond_by_to=1650000000',
        ],
        'response'  => [
            'content'       => [
                'count'         => 1,
                'items'         => [
                    [
                        'internal_status'     => 'open',
                    ],
                ]
            ],
        ],
    ],

    'testDisputeFetchForAdminDeductionReversalAtParam'    => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/admin/dispute?deduction_reversal_at_from=1550000000&deduction_reversal_at_to=1650000000',
        ],
        'response'  => [
            'content'       => [
                'count'         => 1,
                'items'         => [
                    [
                        'deduction_reversal_at' => 1600000000
                    ],
                ]
            ],
        ],
    ],


    'testDisputeFetchForAdminRestricted'    => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/admin/dispute?expand[]=payment',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
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

    'testDisputeFileInvalidDelete' => [
        'request' => [
            'method' => 'delete',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Files can be deleted only when dispute is open',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchFiles' => [
        'request' => [
            'method' => 'get',
            'url'    => '/disputes/disp_1000000dispute/files'
            ],
        'response'  => [
            'content'       => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'id'            => 'file_1cXSLlUU8V9sXl',
                        'type'          => 'explanation_letter',
                        'entity_type'   => 'dispute',
                        'entity_id'     => '1000000dispute',
                        'name'          => 'myfile1.png',
                        'location'      => 'dispute/10000000000000/1000000dispute/myfile1.png',
                        'bucket'        => 'test_bucket',
                        'mime'          => 'text/csv',
                        'extension'     => 'csv',
                        'merchant_id'   => '10000000000000',
                        'store'         => 's3',
                    ],
                    [
                        'id'            => 'file_1cXSLlUU8V9sXm',
                        'type'          => 'delivery_proof',
                        'entity_type'   => 'dispute',
                        'entity_id'     => '1000000dispute',
                        'name'          => 'myfile2.pdf',
                        'location'      => 'dispute/10000000000000/1000000dispute/myfile2.pdf',
                        'bucket'        => 'test_bucket',
                        'mime'          => 'text/csv',
                        'extension'     => 'csv',
                        'merchant_id'   => '10000000000000',
                        'store'         => 's3',
                    ],

                ],
            ],
            'status_code'   => 200,
        ],
    ],

    'testDisputeEditLostWithoutDeductionEventData' => [
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
                    'amount_deducted'    => 0,
                    'currency'           => 'INR',
                    'status'             => 'lost',
                    'reason_code'        => 'SOMETHING_BAD',
                ],
            ],
        ],
    ],

    'testDisputeEditInternalRespondBy' => [
        'request' => [
            'method'  => 'post',
            'content' => [
               'internal_respond_by' => '1700000000',
            ],
        ],
        'response' => [
            'content' => [
                'internal_respond_by' => 1700000000,
            ],
        ],
    ],

    'testDisputeEditInvalidInternalStatusValues' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'internal_status' => 'Represented',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => "Represented is not a valid value for 'internal_status'",
                ],
            ],
            'status_code' => 400,
        ],

        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeEditInvalidStatusValues' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status' => 'Under_review',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => "Not a valid dispute status: Under_review",
                ],
            ],
            'status_code' => 400,
        ],

        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeEditDeductionSourceTypeAndId' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'deduction_source_type' => 'adjustment',
                'deduction_source_id'   => '',
                'skip_deduction'        => true,
                'internal_status'       => 'lost_merchant_debited',
            ],
        ],
        'response' => [
            'content' => [
                'deduction_source_type' => 'adjustment',
                'deduction_source_id'   => '',
                'internal_status'       => 'lost_merchant_debited'
            ],
        ],
    ],

    'testDisputeEditDeductionSourceTypeAndIdValidationFailures' => [
        'request' => [
            'method'  => 'post',
            'content' => [

            ],
        ],

        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeEditWithStatusAndInternalStatusValidCombinations' => [
        'request'  => [
            'method'  => 'post',
            'content' => [
                'status' => 'under_review',
            ],
        ],
        'response' => [
            'content' => [
                'status'          => 'under_review',
                'internal_status' => 'under_review',
            ],
        ],
    ],

    'testDisputeEditWithStatusAndInternalStatusInvalidCombinations' => [
        'request'  => [
            'method'  => 'post',
            'content' => [
                'status' => 'under_review',
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisputeEditLostWithoutDeduction' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'         => 'lost',
                'skip_deduction' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'amount'          => 1000000,
                'amount_deducted' => 0,
                'currency'        => 'INR',
                'phase'           => 'chargeback',
                'status'          => 'lost',
                'internal_status' => 'lost_merchant_not_debited',
            ],
        ],
    ],

    'testDisputeEditLostWithDeductionWithDeductionTypeNotSpecified' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'         => 'lost',
                'skip_deduction' => false,
            ],
        ],
        'response' => [
            'content' => [

                'currency'        => 'INR',
                'phase'           => 'chargeback',
                'status'          => 'lost',
                'internal_status' => 'lost_merchant_debited',
            ],
        ],
    ],

    'testDisputeEditLostWithDeductionViaAdjustment' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'             => 'lost',
                'skip_deduction'     => false,
                'recovery_method'    => 'adjustment',
                'internal_status'    => 'lost_merchant_debited',
            ],
        ],
        'response' => [
            'content' => [
                'currency'              => 'INR',
                'phase'                 => 'chargeback',
                'status'                => 'lost',
                'internal_status'       => 'lost_merchant_debited',
                'deduction_source_type' => 'adjustment',
            ],
        ],
    ],

    'testDisputeEditLostWithDeductionViaRefund' => [
        'request' => [
            'method'  => 'post',
            'content' => [
                'status'             => 'lost',
                'skip_deduction'     => false,
                'recovery_method'    => 'refund',
                'internal_status'    => 'lost_merchant_debited',
            ],
        ],
        'response' => [
            'content' => [

                'currency'        => 'INR',
                'phase'           => 'chargeback',
                'status'          => 'lost',
                'internal_status' => 'lost_merchant_debited',
                'deduction_source_type' => 'refund',

            ],
        ],
    ],

    'testPhaseBasedBulkCreateDisputes' => [
        'request' => [
            'url' => '/disputes/bulk_create',
        'method' => 'post',
            'files' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testBulkCreateDisputesWithoutReasonCode' => [
        'request' => [
            'url' => '/disputes/bulk-create/internal',
            'method' => 'post',
            'files' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testPhaseBasedBulkCreateMails' => [
        'request' => [
            'url' => '/disputes/merchant_emails/initiate',
            'method' => 'post'
        ],
        'response' => [
            'content' => [
                'success'        => true,
                'total_disputes' => 2,
            ],
        ],
    ],

    'testBulkDisputeCreateMailAttachment' => [
        'request' => [
            'url' => '/disputes/merchant_emails/initiate',
            'method' => 'post'
        ],
        'response' => [
            'content' => [
                'success'        => true,
                'total_disputes' => 1,
            ],
        ],
    ],

    'testBulkDisputeNewFormat' => [
        'request' => [
            'url' => '/disputes/bulk-create',
            'method' => 'post',
            'files' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testBulkDisputeEdit' => [
        'request' => [
            'url' => '/disputes/bulk-edit',
            'method' => 'post',
            'files' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testBulkLostDisputeEdit' => [
        'request' => [
            'url' => '/disputes/bulk-edit',
            'method' => 'post',
            'files' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testBulkDisputeEditValidationFailure' => [
        'request' => [
            'url' => '/disputes/bulk-edit',
            'method' => 'post',
            'files' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDisputeReasonFetch' => [
        'request'   => [
            'method'        => 'get',
            'url'           => '/dispute_reasons_internal',
        ],
        'response'  => [
            'content'       => [
                'gateway_code'        => '8fjf',
                'gateway_description' => 'Fraud on merchant side',
                'code'                => 'KFRER_R',
                'description'         => 'This is a serious fraud',
                'network'             => 'Visa',
            ],
        ],
    ],

    'testRearchDisputeCreate' => [
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

    'testFreshdeskWebhookPaymentFailedCase' => [
        'request' => [
            'url'       => '/fd/disputes',
            'method'    => 'post',
            'content'   => [
                "freshdesk_webhook" => [
                    "ticket_id" => 123,
                    "ticket_cf_requestor_category" => "Customer",
                    "ticket_contact_name" => "ahshasd",
                    "ticket_contact_email" => "testFreshdeskWebhookPaymentFailedCase@gmail.com",
                    "ticket_contact_phone" => "9999999999"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],

    'testFreshdeskWebhookPaymentNotCapturedCase' => [
        'request' => [
            'url'       => '/fd/disputes',
            'method'    => 'post',
            'content'   => [
                "freshdesk_webhook" => [
                    "ticket_id" => 123,
                    "ticket_cf_requestor_category" => "Customer",
                    "ticket_contact_name" => "ahshasd",
                    "ticket_contact_email" => "testFreshdeskWebhookPaymentNotCapturedCase@gmail.com",
                    "ticket_contact_phone" => "9999999999"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],

    'testFreshdeskWebhookPaymentFullyRefundedCase' => [
        'request' => [
            'url'       => '/fd/disputes',
            'method'    => 'post',
            'content'   => [
                "freshdesk_webhook" => [
                    "ticket_id" => 123,
                    "ticket_cf_requestor_category" => "Customer",
                    "ticket_contact_name" => "ahshasd",
                    "ticket_contact_email" => "testFreshdeskWebhookPaymentFullyRefundedCase@gmail.com",
                    "ticket_contact_phone" => "9999999999"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],

    'testFreshdeskWebhookPaymentAlreadyDisputedCase' => [
        'request' => [
            'url'       => '/fd/disputes',
            'method'    => 'post',
            'content'   => [
                "freshdesk_webhook" => [
                    "ticket_id" => 123,
                    "ticket_cf_requestor_category" => "Customer",
                    "ticket_contact_name" => "ahshasd",
                    "ticket_contact_email" => "testFreshdeskWebhookPaymentAlreadyDisputedCase@gmail.com",
                    "ticket_contact_phone" => "9999999999"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],

    'testFreshdeskWebhookMerchantDisabledCase' => [
        'request' => [
            'url'       => '/fd/disputes',
            'method'    => 'post',
            'content'   => [
                "freshdesk_webhook" => [
                    "ticket_id" => 123,
                    "ticket_cf_requestor_category" => "Customer",
                    "ticket_contact_name" => "ahshasd",
                    "ticket_contact_email" => "testFreshdeskWebhookMerchantDisabledCase@gmail.com",
                    "ticket_contact_phone" => "9999999999"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],

    'testFreshdeskWebhookCreateDisputeCase' => [
        'request' => [
            'url'       => '/fd/disputes',
            'method'    => 'post',
            'content'   => [
                "freshdesk_webhook" => [
                    "ticket_id" => 123,
                    "ticket_cf_requestor_category" => "Customer",
                    "ticket_contact_name" => "ahshasd",
                    "ticket_contact_email" => "testFreshdeskWebhookCreateDisputeCase@gmail.com",
                    "ticket_contact_phone" => "9999999999"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],

    'testFreshdeskWebhookReportFraud' => [
        'request' => [
            'url'       => '/fd/disputes',
            'method'    => 'post',
            'content'   => [
                "freshdesk_webhook" => [
                    "ticket_id" => 123,
                    "ticket_cf_requestor_category" => "Customer",
                    "ticket_contact_name" => "ahshasd",
                    "ticket_contact_email" => "testFreshdeskWebhookReportFraud@gmail.com",
                    "ticket_contact_phone" => "9999999999"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],

    'testFreshdeskWebhookPaymentNotExists' => [
        'request' => [
            'url'       => '/fd/disputes',
            'method'    => 'post',
            'content'   => [
                "freshdesk_webhook" => [
                    "ticket_id" => 123,
                    "ticket_cf_requestor_category" => "Customer",
                    "ticket_contact_name" => "ahshasd",
                    "ticket_contact_email" => "testFreshdeskWebhookPaymentNotExists@gmail.com",
                    "ticket_contact_phone" => "9999999999"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],

    'testFreshdeskWebhookReasonCodeNotValidForSubcategory' => [
        'request' => [
            'url'       => '/fd/disputes',
            'method'    => 'post',
            'content'   => [
                "freshdesk_webhook" => [
                    "ticket_id" => 123,
                    "ticket_cf_requestor_category" => "Customer",
                    "ticket_contact_name" => "ahshasd",
                    "ticket_contact_email" => "testFreshdeskWebhookReasonCodeNotValidForSubcategory@gmail.com",
                    "ticket_contact_phone" => "9999999999"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'success' => false,
            ]
        ],
    ],

    'testFreshdeskWebhookDisputeCreationForPaymentsOlderThanSixMonths' => [
        'request' => [
            'url'       => '/fd/disputes',
            'method'    => 'post',
            'content'   => [
                "freshdesk_webhook" => [
                    "ticket_id" => 123,
                    "ticket_cf_requestor_category" => "Customer",
                    "ticket_contact_name" => "ahshasd",
                    "ticket_contact_email" => "testFreshdeskWebhookDisputeCreationSixMonthsOld@gmail.com",
                    "ticket_contact_phone" => "9999999999"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],

    'testBulkDisputeCreateMailSubject' => [
        'request' => [
            'url'    => '/disputes/merchant_emails/initiate',
            'method' => 'post',
        ],
        'response' => [
            'content' => [
                'success'        => true,
                'total_disputes' => 1,
            ],
        ],
    ],


    'testDeductionReversalCron' => [
        'request' => [
            'url'    => '/disputes/deduction_reversal_cron',
            'method' => 'post',
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testPaymentIdNotFound' => [
        'request'  => [
            'url'     => '/dispute/chargeback_automation/hitachi',
            'method'  => 'POST',
            'content' => [
                [
                    "network"               => "mastercard",
                    "arn"                   => "741107512600331562950201",
                    "rrn"                   => "7411075126003315629502",
                    "amt"                   => "4224",
                    "txn_date"              => "04/09/2021",
                    "reason_code"           => "4855",
                    "fulfilment_tat"        => "04/09/2021",
                    "currency"              => "356",
                    "dispute_type"          => "ARB",
                ]
            ],
        ],
        'response' => [
            'content'     => [
                'items' =>
                    [
                        [
                            'success' => false,
                            'error'   => [
                                Error::DESCRIPTION => 'payment Id not found',
                            ],
                        ]
                    ]
            ],
            'status_code' => 200,
        ],
    ],

    'testDisputeTypeGoodFaith' => [
        'request'  => [
            'url'     => '/dispute/chargeback_automation/hitachi',
            'method'  => 'POST',
            'content' => [
                [
                    "network"               => "Mastercard",
                    "arn"                   => "741107512600331562950201",
                    "rrn"                   => "7411075126003315629502",
                    "amt"                   => "4224",
                    "txn_date"              => "04/09/2021",
                    "reason_code"           => "10.3",
                    "fulfilment_tat"        => "04/09/2021",
                    "currency"              => "356",
                    "dispute_type"          => "GOODFAITH",
                ]
            ],
        ],
        'response' => [
            'content'     => [
                'items' =>
                    [
                        [
                            'success' => false,
                            'error'   => [
                                Error::DESCRIPTION => 'dispute type GOODFAITH is not processed',
                            ],
                        ]
                    ]
            ],
            'status_code' => 200,
        ],
    ],

    'testChargebackSuccess' => [
        'request'     => [
            'url'     => '/dispute/chargeback_automation/hitachi',
            'method'  => 'POST',
            'content' => [
                [
                    "network"               => "Mastercard",
                    "arn"                   => "741107512600331562950201",
                    "rrn"                   => "7411075126003315629502",
                    "amt"                   => "4224",
                    "txn_date"              => "04/09/2021",
                    "reason_code"           => "10.3",
                    "fulfilment_tat"        => "04/09/2021",
                    "currency"              => "356",
                    "dispute_type"          => "ARB",
                ]
            ],
        ],
        'response'    => [
            'content' => [
                'items' =>
                    [
                        [
                            'Merchant deadline'                  => "03/02/2021",
                            'Initiation Date'                    => "01/02/2021",
                            'Initiation Status'                  => "",
                            'Representment Amount'               => "4224",
                            'Currency'                           => "INR",
                            'Merchant Name'                      => "enim",
                            'Upfront Debit [finops to update]'   => "",
                            'STATUS'                             => "",
                            'Status date'                        => "",
                            'Agent'                              => "",
                            'Ticket'                             => "",
                            'Chargeback Type'                    => "",
                            'Prearb approval status - Checker'   => "",
                            'Prearb approval Comments - Checker' => "",
                            'Comments'                           => "",
                            'Key Account'                        => "TempName",
                            'merchant_id'                        => "10000000000000",
                            'International Transaction'          => false,
                            'Transaction Status' => "captured",
                            'Base Amount'        => 1000000,
                            'Website'            => null,
                            'ME Deadline'        => "",
                            'Initiation date'    => "",
                            'Reason Category'    => "",
                            'No Debit list'      => "",
                            'ARN'                => "741107512600331562950201",
                            'Txn Date'           => "04/09/2021",
                            'RRN'                => "7411075126003315629502",
                            'Fullfilment date'   => "04/09/2021",
                            'Reason Code'        => "10.3",
                            'idempotent_id'      => null,
                            'success'            => true,
                        ]
                    ]
            ]
        ],
        'status_code' => 200,
    ],

    'testChargebackSuccessAndFailure' => [
        'request'     => [
            'url'     => '/dispute/chargeback_automation/hitachi',
            'method'  => 'POST',
            'content' => [
                [
                    "network"        => "Visa",
                    "arn"            => "741107512600331562950201",
                    "rrn"            => "7411075126003315629502",
                    "amt"            => "4224",
                    "txn_date"       => "04/09/2021",
                    "reason_code"    => "10.3",
                    "fulfilment_tat" => "04/09/2021",
                    "currency"       => "356",
                    "dispute_type"   => "ARB",
                ],
                [
                    "network"        => "Mastercard",
                    "arn"            => "741107512600331562950201",
                    "rrn"            => "7411075126003315629502",
                    "amt"            => "4224",
                    "txn_date"       => "04/09/2021",
                    "reason_code"    => "10.3",
                    "fulfilment_tat" => "04/09/2021",
                    "currency"       => "356",
                    "dispute_type"   => "GOODFAITH",
                ]
            ],
        ],
        'response'    => [
            'content' => [
                'items' =>
                    [
                        [
                            'Merchant deadline'                  => "03/02/2021",
                            'Initiation Date'                    => "01/02/2021",
                            'Initiation Status'                  => "",
                            'Representment Amount'               => "4224",
                            'Currency'                           => "INR",
                            'Merchant Name'                      => "enim",
                            'Upfront Debit [finops to update]'   => "",
                            'STATUS'                             => "",
                            'Status date'                        => "",
                            'Agent'                              => "",
                            'Ticket'                             => "",
                            'Chargeback Type'                    => "",
                            'Prearb approval status - Checker'   => "",
                            'Prearb approval Comments - Checker' => "",
                            'Comments'                           => "",
                            'Key Account'                        => "TempName",
                            'merchant_id'                        => "10000000000000",
                            'International Transaction'          => false,
                            'Transaction Status'                 => "captured",
                            'Base Amount'                        => 1000000,
                            'Website'                            => null,
                            'ME Deadline'                        => "",
                            'Initiation date'                    => "",
                            'Reason Category'                    => "",
                            'No Debit list'                      => "",
                            'ARN'                                => "741107512600331562950201",
                            'Txn Date'                           => "04/09/2021",
                            'RRN'                                => "7411075126003315629502",
                            'Fullfilment date'                   => "04/09/2021",
                            'Reason Code'                        => "10.3",
                            'idempotent_id'                      => null,
                            'success'                            => true,
                        ],
                        [
                            'success' => false,
                            'error'   => [
                                Error::DESCRIPTION => 'dispute type GOODFAITH is not processed',
                            ],
                        ]
                    ]
            ]
        ],
        'status_code' => 200,
    ],
];

