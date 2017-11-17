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
                'merchant_id'        => '10000000000000',
                'amount'             => 100,
                'currency'           => 'INR',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_description' => 'This is a serious fraud',
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
                'merchant_id'        => '10000000000000',
                'amount'             => 100,
                'currency'           => 'INR',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_description' => 'This is a serious fraud',
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
                'merchant_id'        => '10000000000000',
                'amount'             => 100,
                'currency'           => 'INR',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_description' => 'This is a serious fraud',
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
                'merchant_id'        => '10000000000000',
                'amount'             => 100,
                'currency'           => 'INR',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_description' => 'This is a serious fraud',
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
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'The server encountered an error. The incident has been reported to admins.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
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
                'merchant_id'        => '10000000000000',
                'amount'             => 100,
                'currency'           => 'INR',
                'phase'              => 'chargeback',
                'status'             => 'open',
                'reason_description' => 'This is a serious fraud',
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

    'testDisputeCreateWithInvalidMerchantEmail2' => [
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

    'testDisputeEdit' => [
        'request' => [
            'method'  => 'patch',
            'content' => [
                'status'                 => 'under_review',
                'expires_on'             => '1912162918',
                'gateway_dispute_status' => 'processing'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'amount'      => 1000000,
                'currency'    => 'INR',
                'phase'       => 'chargeback',
                'status'      => 'under_review'
            ],
        ],
    ],

    'testDisputeEditWon' => [
        'request' => [
            'method'  => 'patch',
            'content' => [
                'status'                 => 'won',
                'expires_on'             => '1912162918',
                'gateway_dispute_status' => 'processing'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'amount'      => 1000000,
                'currency'    => 'INR',
                'phase'       => 'chargeback',
                'status'      => 'won'
            ],
        ],
    ],

    'testDisputeEditClose' => [
        'request' => [
            'method'  => 'patch',
            'content' => [
                'status'                 => 'closed',
                'expires_on'             => '1912162918',
                'gateway_dispute_status' => 'processing'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'amount'      => 1000000,
                'currency'    => 'INR',
                'phase'       => 'chargeback',
                'status'      => 'closed'
            ],
        ],
    ],

    'testDisputeEditDeductOnLost' => [
        'request' => [
            'method'  => 'patch',
            'content' => [
                'status' => 'lost',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'amount'      => 1000000,
                'currency'    => 'INR',
                'phase'       => 'chargeback',
                'status'      => 'lost'
            ],
        ],
    ],

    'testDisputeEditDoNotDeductOnLostIfDeducted' => [
        'request' => [
            'method'  => 'patch',
            'content' => [
                'status' => 'lost',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'amount'      => 1000000,
                'currency'    => 'INR',
                'phase'       => 'chargeback',
                'status'      => 'lost'
            ],
        ],
    ],

    'testDisputeEditInvalidStatus' => [
        'request' => [
            'method'  => 'patch',
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
                    'description' => 'Not a valid dispute status',
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
            'method'  => 'patch',
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
            'method'  => 'patch',
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
            'method'  => 'patch',
            'content' => [
                'status'        => 'won'
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDisputeReversalLostLogic' => [
        'request' => [
            'method'  => 'patch',
            'content' => [
                'status'        => 'lost'
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDisputeEditForNoInitialParent' => [
        'request' => [
            'method'  => 'patch',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDisputeEditWithExistingParent' => [
        'request' => [
            'method'  => 'patch',
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
            'method'  => 'patch',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDisputeEditReplaceParentWithAlreadyLinkedParent' => [
        'request' => [
            'method'  => 'patch',
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
            'method'  => 'patch',
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
            'method'  => 'patch',
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
            'method'  => 'patch',
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
            'method'  => 'patch',
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

    'testDisputeMerchantDocumentUpload' => [
        'request' => [
            'content' => [
                'comments'      => [],
            ],
            'method' => 'patch',
            'files' => [],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
];
