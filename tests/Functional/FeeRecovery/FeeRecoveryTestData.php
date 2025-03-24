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

    'testCreatePayoutServiceFeeRecoveryPayout' => [
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

    'testCreateFeeRecoveryPayoutForAsyncPayoutProcessingEnabledMerchant' => [
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

    'testFeeRecoveryPayoutCronNextRunUpdateForNegativeAmount' => [
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

    'testCreateFeeRecoveryRetryManualByAdminAfterThreeFailures' => [
        'request'  => [
            'url'    => '/admin/fee_recovery_retry',
            'method' => 'POST',
            'content' => [
                'previous_recovery_payout_id'  => '',
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

    'testCreateFeeRecoveryRetryManualWrongRecoveryPayoutIdByAdminAfterThreeFailures' => [
        'request'  => [
            'url'    => '/admin/fee_recovery_retry',
            'method' => 'POST',
            'content' => [
                'previous_recovery_payout_id'  => 'DirectTransfer',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => Exception\DbQueryException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_DB_QUERY_FAILED,
        ],
    ],

    'testGetFeeRecoveryScheduleForBalanceViaAdminAction' => [
        'request'  => [
            'url'    => '/admin/fee_recovery_schedule_update',
            'method' => 'POST',
            'content' => [
                'action'  => 'dry_run',
                'balance_id' => '',
            ],
        ],
        'response' => [
            'content' => [
                'balance_id'=> '',
                'last_run_at'=> 1707244199,
                'next_run_at'=> 1707330599,
                'last_run'=> '2024-02-06T18:29:59.000000Z',
                'next_run'=> '2024-02-07T18:29:59.000000Z',
            ],
        ],
    ],

    'testUpdateFeeRecoveryScheduleForBalanceViaAdminAction' => [
        'request'  => [
            'url'    => '/admin/fee_recovery_schedule_update',
            'method' => 'POST',
            'content' => [
                'action'  => 'update',
                'balance_id' => '',
                'next_run_at' => 1708000000,
            ],
        ],
        'response' => [
            'content' => [
                'balance_id'=> '',
                'last_run_at'=> 1707244199,
                'next_run_at'=> 1708000000,
                'last_run'=> '2024-02-06T18:29:59.000000Z',
                'next_run'=> '2024-02-15T12:26:40.000000Z'
            ],
        ],
    ],

    'testUpdateFeeRecoveryScheduleValidationFailureViaAdminAction' => [
        'request'  => [
            'url'    => '/admin/fee_recovery_schedule_update',
            'method' => 'POST',
            'content' => [
                'action'  => 'update',
                'balance_id' => '',
                'last_run_at' => 1707000000,
                'next_run_at' => 1708000000,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'last_run_at is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'              => Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testGetFeeRecoveryAmount' => [
        'request'  => [
            'url'    => '/admin/fee_recovery_amount',
            'method' => 'POST',
            'content' => [
                'balance_id' => '',
                'from' => 1577864736,
                'to' => 1578469536,
            ],
        ],
        'response' => [
            'content' => [
                'amount'                => 590,
                'start_time'            => 1577864736,
                'end_time'              => 1578469536,
                'payout_count'          => 4,
                'failed_payout_count'   => 1,
                'reversal_count'        => 1

            ],
        ],
    ],

    'testCreateFeeRecoveryPayoutJobViaAdminAction' => [
        'request'  => [
            'url'    => '/admin/fee_recovery_payout',
            'method' => 'POST',
            'content' => [
                'balance_id' => '',
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
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

    'testFeeRecoveryLowBalanceAlert'   => [
        'request'   => [
            'url'       => '/fee_recovery_low_balance_cron',
            'method'    => 'POST',
            'content'   => [],
        ],
        'response'  => [
            'content'   => [
                'success'   => true,
            ]
        ]
    ],

    'testCreateFeeRecoveryPayoutCustomAmountByAdminAction' => [
        'request'  => [
            'url'    => '/admin/fee_recovery_payout/custom_amount',
            'method' => 'POST',
            'content' => [
                'amount' => 100,
                'balance_id' => '10000000000000',
                'narration' => 'Fee recovery for payouts',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'payout',
                'transaction_id' => null,
                'notes' => [],
                'fees' => 0,
                'tax' => 0,
                'status' => 'processing',
                'internal_status' => 'created',
                'pending_reason' => null,
                'utr' => null,
                'user_id' => null,
                'reference_id' => null,
                'batch_id' => null,
                'banking_account_id' => 'bacc_ABCde1234ABCde',
                'failure_reason' => null,
                'fee_type' => null,
                'origin' => 'api',
                'source_details' => [],
                'remarks' => null,
                'cancellation_user_id' => null,
                'cancellation_user' => [],
                'status_details' => [
                    'reason' => null,
                    'description' => null,
                    'source' => null,
                ],
                'merchant_id' => '10000000000000',
                'status_details_id' => null,
                'amount' => 100,
                'currency' => 'INR',
                'purpose' => 'rzp_fees',
                'mode' => 'IFT',
                'narration' => 'Fee recovery for payouts',
            ],
        ],
    ],

    'testFeeRecoveryZeroPricingFees' => [
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
    'testFeeRecoveryZeroPricingFeesExperimentDisabled' => [
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

    'testCreateFeeRecoveryPayoutExcludingRecoveredPayout' => [
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

    'testCreateFeeRecoveryPayoutExcludingRecoveredPayouts_WithMissingFeeRecoveryEntries' => [
        'request'  => [
            'url'    => '/payouts/fee_recovery',
            'method' => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => null,
                    'description' => PublicErrorDescription::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\LogicException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_LOGIC_ERROR_FEE_RECOVERY_ENTITY_MISSING,
        ],
    ],

];
