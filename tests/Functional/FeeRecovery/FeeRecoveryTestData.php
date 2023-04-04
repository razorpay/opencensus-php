<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateFeeRecoveryPayout' => [
        'request'  => [
            'url'    => '/payouts/fee_recovery',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 590,
                'currency'        => 'INR',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'rzp_fees',
                'status'          => 'processing',
                'mode'            => 'IFT',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testCreateFeeRecoveryPayoutWithFailedToReversedCase' => [
        'request'  => [
            'url'    => '/payouts/fee_recovery',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 1180,
                'currency'        => 'INR',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'rzp_fees',
                'status'          => 'processing',
                'mode'            => 'IFT',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testCreateFeeRecoveryPayoutLowBalance' => [
        'request'  => [
            'url'    => '/payouts/fee_recovery',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 590,
                'currency'        => 'INR',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'rzp_fees',
                'status'          => 'queued',
                'mode'            => 'IFT',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testCreateFeeRecoveryPayoutSkipWorkflow' => [
        'request'  => [
            'url'    => '/payouts/fee_recovery',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 590,
                'currency'        => 'INR',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'rzp_fees',
                'status'          => 'processing',
                'mode'            => 'IFT',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testFeeRecoveryPayoutCron' => [
        'request'  => [
            'url'    => '/payouts/fee_recovery/process',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success'   => true,
            ],
        ],
    ],

    'testFeeRecoveryPayoutCronNextAndLastRunUpdate' => [
        'request'  => [
            'url'    => '/payouts/fee_recovery/process',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success'   => true,
            ],
        ],
    ],

    'testProcessQueuedPayoutFeeRecoveryCreated' => [
        'request'  => [
            'url'    => '/payouts/queued/process/new',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testApprovePendingPayoutFeeRecoveryCreated' => [
        'request'  => [
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateFeeRecoveryScheduleTaskForMerchant' => [
        'request'  => [
            'url'    => '/schedules/tasks/fee_recovery',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'method'        => null,
                'international' => 0,
                'type'          => 'fee_recovery',
                'merchant_id'   => '10000000000000',
                'entity_type'   => 'balance',
            ],
        ],
    ],

    'testUpdateFeeRecoveryScheduleTaskForMerchant' => [
        'request'  => [
            'url'    => '/schedules/tasks/fee_recovery',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'method'        => null,
                'international' => 0,
                'type'          => 'fee_recovery',
                'merchant_id'   => '10000000000000',
                'entity_type'   => 'balance',
            ],
        ],
    ],

    'testCreateFeeRecoveryScheduleTaskForRecentlyActivatedMerchant' => [
        'request'  => [
            'url'    => '/schedules/tasks/fee_recovery',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'method'        => null,
                'international' => 0,
                'type'          => 'fee_recovery',
                'merchant_id'   => '10000000000000',
                'entity_type'   => 'balance',
            ],
        ],
    ],

    'testCreateManualRecoveryAfterFiveRetryFail' => [
        'request'  => [
            'url'       => '/payouts/fee_recovery/manual',
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testCreateManualRecoveryIncorrectAmount' => [
        'request'  => [
            'url'       => '/payouts/fee_recovery/manual',
            'method'    => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FEE_RECOVERY_MANUAL_AMOUNT_MISMATCH,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FEE_RECOVERY_MANUAL_AMOUNT_MISMATCH,
        ],
    ],

    'testCreateManualRecoveryWhereRecoveryAlreadyInProgress' => [
        'request'  => [
            'url'       => '/payouts/fee_recovery/manual',
            'method'    => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FEE_RECOVERY_MANUAL_COLLECTION_FOR_PAYOUT_INVALID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FEE_RECOVERY_MANUAL_COLLECTION_FOR_PAYOUT_INVALID,
        ],
    ],

    'testCreateManualRecoveryWhenWrongIdsPassedInInput' => [
        'request'  => [
            'url'       => '/payouts/fee_recovery/manual',
            'method'    => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FEE_RECOVERY_MANUAL_COLLECTION_FOR_PAYOUT_INVALID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FEE_RECOVERY_MANUAL_COLLECTION_FOR_PAYOUT_INVALID,
        ],
    ],

    'testCreateFeeRecoveryRetryManualAfterThreeFailures' => [
        'request'  => [
            'url'    => '/payouts/fee_recovery_retry/manual',
            'method' => 'POST',
            'content' => [
                'previous_recovery_payout_id'  => 'last_fee_recovery_payout_id',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 590,
                'currency'        => 'INR',
                'purpose'         => 'rzp_fees',
                'status'          => 'processing',
                'mode'            => 'IFT',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testCreateFeeRecoveryRetryManualFailAfterSuccess' => [
        'request'  => [
            'url'    => '/payouts/fee_recovery_retry/manual',
            'method' => 'POST',
            'content' => [
                'previous_recovery_payout_id'  => 'last_fee_recovery_payout_id',
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'Fee Recovery Retry failed'
            ],
        ],
    ],
];
