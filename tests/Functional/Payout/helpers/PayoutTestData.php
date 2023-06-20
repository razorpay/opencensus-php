<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\Status as PayoutStatus;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\FundTransfer\Attempt\Status as FundTransferAttemptStatus;

return [
    'testCreatePayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithPayoutLimitFeatureFlagEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 49000000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 49000000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'NEFT',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithPayoutLimitExceededFeatureFlagEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 50000001000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount may not be greater than 50000000000.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutWithDecimalAmount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 53217.999999999,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'The amount must be an integer.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testErrorDescriptionForMinimumTransactionAmount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 23,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Minimum transaction amount should be 100 paise',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePayoutStatusToProcessedManuallyFailed' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts/id/manual/status',
            'content'   => [
                'status'                => 'processed',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Fts fund account id and type are required to move."
                 ."payout from initiated to processed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFundAccountTypeValidationForManualPayoutStatusUpdate' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts/id/manual/status',
            'content' => [
                'status'              => 'processed',
                'fts_fund_account_id' => '12345',
                'fts_account_type'    => 'DIRECT',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Fts Account Type can be either current or nodal',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutWithNarrationNull' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => null,
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithNarrationNullType' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithNarrationNullTypeWithFeatureEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'narration'       => 'null',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => null,
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutOnLiveMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithNewCreditsFlow' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateInterAccountPayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'inter_account_payout',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'purpose'         => 'inter_account_payout',
                'status'          => 'processing',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutfromEarlySettelmentInternalMerchant' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutReversedfromEarlySettelmentInternalMerchant' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutInitiatedToReversedEarlySettelmentInternalMerchant' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutReversedEarlySettelmentInternalMerchant' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],


    'testInterAccountPayoutReversal' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'inter_account_payout',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'inter_account_payout',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testPayoutReversalWithRewards' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testPayoutInitiatedInLedgerCron' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/ledger_service/create_journal_cron',
            'content' => [
                'entity' => 'payout',
            ],
        ],
        'response' => [
            'content' => [
                "success",
            ]
        ],
    ],

    'testPayoutReversalWithMultipleRewards'  => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithIKeyHeader' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithIKeyInProgress' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server'  => [
                'HTTP_X-Payout-Idempotency'  => 'samekey'
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'Request failed because another request is in progress with the same Idempotency Key',
                    'reason'      => 'idempotency_key_in_use'
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_ANOTHER_OPERATION_PROGRESS_SAME_IDEM_KEY,
        ],
    ],

    'testCreateTwoPayoutsWithSameIKeyDiffRequest' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Different request body sent for the same Idempotency Header',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_SAME_IDEM_KEY_DIFFERENT_REQUEST,
        ],
    ],

    'testCreatePayoutWithNarrationAsArray' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => [
                    'abc'   =>  'xyz',
                ],
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The narration must be a string.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testCompositePayoutWithNarrationAsArray' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'       => [
                    'abc'   =>  'xyz',
                ],
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Name of account holder',
                        'ifsc'           => 'ICIC0000104',
                        'account_number' => '3434000111000'
                    ],
                    'contact'      => [
                        'name'    => 'contact name',
                        'email'   => 'contact@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The narration must be a string.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],

    ],

    'testCreateCompositePayoutWithOldIfsc' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'abc and xyz',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Sagnik Saha',
                        'ifsc'           => 'HDFC0CKRMAL',
                        'account_number' => '3434000111000'
                    ],
                    'contact'      => [
                        'name'    => 'Sagnik S',
                        'email'   => 'sagnik.saha@razorpay.com',
                        'contact' => '9876543210',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                "entity"         => "payout",
                "fund_account"   => [
                    "contact"        => [
                        'entity'  => 'contact',
                        'name'    => 'Sagnik S',
                        'email'   => 'sagnik.saha@razorpay.com',
                        'contact' => '9876543210',
                        'type'    => 'employee',
                        'active'  => true,
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                    "entity"       => "fund_account",
                    "account_type" => "bank_account",
                    "bank_account" => [
                        "ifsc"           => "YESB0KUCB01",
                        "name"           => "Sagnik Saha",
                        "notes"          => [],
                        "account_number" => "3434000111000"
                    ],
                ],
                "amount"         => 2000000,
                "currency"       => "INR",
                "notes"          => [
                    "abc" => "xyz",
                ],
                "status"         => "processing",
                "purpose"        => "refund",
                "mode"           => "IMPS",
                "narration"      => "abc and xyz",
                "batch_id"       => null,
                "failure_reason" => null,
                'merchant_id'    => '10000000000000'
            ],
        ],
    ],

    'testCustomerWalletPayoutWithNarrationAsArray' => [
        'request'  => [
            'url'     => '/customers/cust_100000customer/payouts',
            'method'  => 'post',
            'content' => [
                'amount'          => 800,
                'purpose'         => 'refund',
                'fund_account_id' => 'fa_100000000000fa',
                'currency'        => 'INR',
                'narration'       => [
                    'abc'   =>  'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The narration must be a string.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],

    ],

    'testUpdatePayoutToSomeIntermediateStatus' => [
        'request' => [
            'method'    => 'PATCH',
            'content'   => [
                'status' => 'initiated',
            ],
            'url' => ''
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payout can be updated to only a final status',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutWithoutFundAccountId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => null,
                'mode'            => 'UPI',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The fund account id field is required when fund account is not present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateInterAccountPayoutByNonFinopsMerchant' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'inter_account_payout',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Purpose 'inter_account_payout' is an internal purpose used by Razorpay and cannot be accessed.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRxPayoutOnBankingHoliday' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'NEFT',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testRxPayoutOnNonBankingHolidayBeforeNEFTtimings' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'NEFT',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testRxPayoutOnNonBankingHolidayAfterNEFTtimings' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'NEFT',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testBalancesWithBearerAuth' => [
        'request' => [
            'method'    => 'GET',
            'content'   => [
                'type' => 'banking'
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' =>
                    [
                        [
                            'type'           => 'banking',
                            'account_type'   => 'shared',
                            'balance'        => 10000000,
                            'currency'       => 'INR',
                            'account_number' => 'XXXXXXXXXXXX6905',
                        ],
                    ],
            ]
        ]
    ],

    'testFetchPendingPayoutsAsOwnerSSWF' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/pending/summary',
            'content' => [
                'account_numbers' => []
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'header' => [
                'PHP_AUTH_PW' => 'RANDOM_DASH_PASSWORD_MERCHANT'
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testFetchNoPendingPayoutsAsOwnerSSWF' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/pending/summary',
            'content' => [
                'account_numbers' => []
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'header' => [
                'PHP_AUTH_PW' => 'RANDOM_DASH_PASSWORD_MERCHANT'
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testFetchPendingPayoutsAsOwnerSSWFValidationError' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/pending/summary',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'header' => [
                'PHP_AUTH_PW' => 'RANDOM_DASH_PASSWORD_MERCHANT'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account numbers field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testBulkRejectPayoutsAsOwnerSSWF' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/reject/bulk/owner',
            'content' => [
                'bulk_reject_as_owner' => true,
                'user_comment' => ''
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'header' => [
                'PHP_AUTH_PW' => 'RANDOM_DASH_PASSWORD_MERCHANT'
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testBulkRejectPayoutsAsOwnerSSWFValidationError' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/reject/bulk/owner',
            'content' => [
                'user_comment' => ''
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'header' => [
                'PHP_AUTH_PW' => 'RANDOM_DASH_PASSWORD_MERCHANT'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The bulk reject as owner field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testBulkRejectPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/reject/bulk',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'payout_ids' => [],
                'user_comment'    => 'Bulk Rejecting',
            ],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],

    // Create Undoable payout testcase
    'testCreateUndoablePayoutWithOtp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'pending_on_confirmation'
            ],
        ],
    ],

    'testCreateUndoablePayoutWithOtpInMobile' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
                'HTTP_X-Dashboard-User-id' => 'MerchantUser01'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithOtpAndUndoPayoutPreferenceFalse' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testGetOrphanPayouts'     =>  [
        'request' => [
            'url' => '/payout_outbox/orphan_payouts/count',
            'method' => 'post',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],

        'response' => [
            'content' => [
            ]
        ],
    ],

    'testGetOrphanPayoutsOutsideTimeRange'     =>  [
        'request' => [
            'url' => '/payout_outbox/orphan_payouts/count',
            'method' => 'post',
        ],

        'response' => [
            'content' => [
            ]
        ],
    ],

    'testDeleteOrphanPayouts'     =>  [
        'request' => [
            'url' => '/payout_outbox/orphan_payouts/delete',
            'method' => 'post',
            'content' => [
                'ids'           => ['123'],
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],

        'response' => [
            'content' => [
            ]
        ],
    ],


    // Undo payout testcases
    'testUndoPayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testUndoPayoutWithId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testUndoOnSamePayoutMultipleTimes' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testUndoPayoutWithInvalidId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testUndoPayoutWithValidIdPostExpiryTime' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    // Resume payout testcases
    'testResumePayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testResumeOnSamePayoutMultipleTimes' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testCreatePayoutWithOtp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithOtpWithProperContext' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithOtpWithInvalidParameters' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'token'           => 'BUIj3m2Nx2VvVj',
                'otp'             => '0007',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Verification failed because of incorrect OTP.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP
        ],
    ],

    'testCreatePayoutWithOtpWithIMPSLimit5L' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp' =>'0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 270,
                'fees'            => 1770,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithOtpOutOfIMPSLimit' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 60000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'otp' =>'0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Given method / mode cannot be used for the payout amount specified',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_AMOUNT_MODE_MISMATCH
        ],
    ],

    'testCreatePayoutInMerchantDashboard' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'otp' =>'0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
            ],
        ],
    ],

    'testCreateCompositePayoutWithOtp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/composite_payout_with_otp',
            'content' => [
                'otp'                       =>'0007',
                'token'                     => 'BUIj3m2Nx2VvVj',
                'mode'                      => 'UPI',
                'account_number'            => '2224440041626905',
                'amount'                    => 100,
                'currency'                  => 'INR',
                'purpose'                   => 'refund',
                'narration'                 => 'Batman',
                "queue_if_low_balance"      => true,
                'fund_account'   => [
                    'account_type' => 'vpa',
                    'vpa' => [
                        'address'           => 'test@ybl',
                    ],
                    'contact'      => [
                        'name'    => 'Shashi Kumar',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'UPI',
            ],
        ],
    ],


    'testCreateCompositePayoutWithOtpWithSecureContextIncorrectAmount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/composite_payout_with_otp',
            'content' => [
                'otp'                       =>'0007',
                'token'                     => 'BUIj3m2Nx2VvVj',
                'mode'                      => 'UPI',
                'account_number'            => '2224440041626905',
                'amount'                    => 100,
                'currency'                  => 'INR',
                'purpose'                   => 'refund',
                'narration'                 => 'Batman',
                "queue_if_low_balance"      => true,
                'fund_account'   => [
                    'account_type' => 'vpa',
                    'vpa' => [
                        'address'           => 'test@ybl',
                    ],
                    'contact'      => [
                        'name'    => 'Shashi Kumar',
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Verification failed because of incorrect OTP.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP
        ],
    ],


    'testCreateCompositePayoutWithOtpWithSecureContextIncorrectVpa' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/composite_payout_with_otp',
            'content' => [
                'otp'                       =>'0007',
                'token'                     => 'BUIj3m2Nx2VvVj',
                'mode'                      => 'UPI',
                'account_number'            => '2224440041626905',
                'amount'                    => 100,
                'currency'                  => 'INR',
                'purpose'                   => 'refund',
                'narration'                 => 'Batman',
                "queue_if_low_balance"      => true,
                'fund_account'   => [
                    'account_type' => 'vpa',
                    'vpa' => [
                        'address'           => 'test@ybl',
                    ],
                    'contact'      => [
                        'name'    => 'Shashi Kumar',
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Verification failed because of incorrect OTP.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP
        ],
    ],


    'testApprovePayoutWithBearerAuth' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testRejectPayoutWithBearerAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/reject',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'queue_if_low_balance'  => 0,
                'user_comment' => 'Rejecting',
                'force_reject' => false
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testApprovePayoutInAppleWatchWithBearerAuth' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testRejectPayoutInAppleWatchWithBearerAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/reject',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'queue_if_low_balance'  => 0,
                'user_comment' => 'Rejecting',
                'force_reject' => false
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testApprovePayoutWithComment' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testApprovePayoutWithNewWorkflowService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content' =>  [
                "entity" => "payout",
                "fund_account_id" => "fa_100000000000fa",
                "amount" => 10000,
                "currency" => "INR",
                "transaction_id" => null,
                "pending_on_user" => true,
                "workflow_history" => [
                    "config_id" => "FV0aQGxYU4kk4c",
                    "entity_type" => "payouts",
                    "title" => "title",
                    "description" => "[]",
                    "config_version" => "1",
                    "creator_id" => "10000000000000",
                    "creator_type" => "merchant",
                    "diff" => [
                        "old" => [
                            "amount" => null,
                            "merchant_id" => null
                        ],
                        "new" => [
                            "amount" => 10000,
                            "merchant_id" => "10000000000000"
                        ]
                    ],
                    "callback_details" => [
                        "state_callbacks" => [
                            "created" => [
                                "method" => "post",
                                "payload" => [
                                    "queue_if_low_balance" => true,
                                    "type" => "state_callbacks_created"
                                ],
                                "headers" => [
                                    "x-creator-id" => ""
                                ],
                                "service" => "api_live",
                                "type" => "basic",
                                "response_handler" => [
                                    "type" => "success_status_codes",
                                    "success_status_codes" => [
                                        201,
                                        200
                                    ]
                                ]
                            ],
                            "processed" => [
                                "method" => "post",
                                "payload" => [
                                    "queue_if_low_balance" => true,
                                    "type" => "state_callbacks_processed"
                                ],
                                "headers" => [
                                    "x-creator-id" => ""
                                ],
                                "service" => "api_live",
                                "type" => "basic",
                                "response_handler" => [
                                    "type" => "success_status_codes",
                                    "success_status_codes" => [
                                        201,
                                        200
                                    ]
                                ]
                            ]
                        ],
                        "workflow_callbacks" => [
                            "processed" => [
                                "domain_status" => [
                                    "approved" => [
                                        "method" => "post",
                                        "payload" => [
                                            "queue_if_low_balance" => true,
                                            "type" => "workflow_callbacks_approved"
                                        ],
                                        "headers" => [
                                            "x-creator-id" => ""
                                        ],
                                        "service" => "api_live",
                                        "type" => "basic",
                                        "response_handler" => [
                                            "type" => "success_status_codes",
                                            "success_status_codes" => [
                                                201,
                                                200
                                            ]
                                        ]
                                    ],
                                    "rejected" => [
                                        "method" => "post",
                                        "payload" => [
                                            "queue_if_low_balance" => true,
                                            "type" => "state_callbacks_rejected"
                                        ],
                                        "headers" => [
                                            "x-creator-id" => ""
                                        ],
                                        "service" => "api_live",
                                        "type" => "basic",
                                        "response_handler" => [
                                            "type" => "success_status_codes",
                                            "success_status_codes" => [
                                                201,
                                                200
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "status" => "initiated",
                    "domain_status" => "created",
                    "owner_id" => "10000000000000",
                    "owner_type" => "merchant",
                    "org_id" => "100000razorpay",
                    "states" => [
                        "Owner_Approval" => [
                            "workflow_id" => "FV58BuqLuCP4Cw",
                            "status" => "created",
                            "name" => "Owner_Approval",
                            "group_name" => "ABC",
                            "type" => "checker",
                            "rules" => [
                                "actor_property_key" => "role",
                                "actor_property_value" => "owner",
                                "count" => 1
                            ],
                            "pending_on_user" => true,
                        ]
                    ],
                    "type" => "payout-approval",
                    "pending_on_user" => true
                ],
                "notes" => [
                    "random_key1" => "Hello",
                    "random_key2" => "Hi"
                ],
                "fees" => 0,
                "tax" => 0,
                "status" => "pending",
                "purpose" => "refund",
                "utr" => null,
                "user_id" => null,
                "mode" => "NEFT",
                "reference_id" => null,
                "narration" => "Test Merchant Fund Transfer",
                "batch_id" => null,
                "cancelled_at" => null,
                "queued_at" => null,
                "banking_account_id" => "bacc_1000000lcustba",
                "initiated_at" => null,
                "processed_at" => null,
                "reversed_at" => null,
                "failed_at" => null,
                "rejected_at" => null,
                "failure_reason" => null,
                "fee_type" => null,
                "scheduled_at" => null,
                "scheduled_on" => null
            ]
        ],
    ],

    'testApprovePayoutWithNWFSWithQueuingDisabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
                'queue_if_low_balance'  => 0,
            ],
        ],
        'response' => [
            'content' =>  [
                "entity" => "payout",
                "fund_account_id" => "fa_100000000000fa",
                "amount" => 10000,
                "currency" => "INR",
                "transaction_id" => null,
                "pending_on_user" => true,
                "workflow_history" => [
                    "config_id" => "FV0aQGxYU4kk4c",
                    "entity_type" => "payouts",
                    "title" => "title",
                    "description" => "[]",
                    "config_version" => "1",
                    "creator_id" => "10000000000000",
                    "creator_type" => "merchant",
                    "diff" => [
                        "old" => [
                            "amount" => null,
                            "merchant_id" => null
                        ],
                        "new" => [
                            "amount" => 10000,
                            "merchant_id" => "10000000000000"
                        ]
                    ],
                    "callback_details" => [
                        "state_callbacks" => [
                            "created" => [
                                "method" => "post",
                                "payload" => [
                                    "type" => "state_callbacks_created"
                                ],
                                "headers" => [
                                    "x-creator-id" => ""
                                ],
                                "service" => "api_live",
                                "type" => "basic",
                                "response_handler" => [
                                    "type" => "success_status_codes",
                                    "success_status_codes" => [
                                        201,
                                        200
                                    ]
                                ]
                            ],
                            "processed" => [
                                "method" => "post",
                                "payload" => [
                                    "type" => "state_callbacks_processed"
                                ],
                                "headers" => [
                                    "x-creator-id" => ""
                                ],
                                "service" => "api_live",
                                "type" => "basic",
                                "response_handler" => [
                                    "type" => "success_status_codes",
                                    "success_status_codes" => [
                                        201,
                                        200
                                    ]
                                ]
                            ]
                        ],
                        "workflow_callbacks" => [
                            "processed" => [
                                "domain_status" => [
                                    "approved" => [
                                        "method" => "post",
                                        "payload" => [
                                            "type" => "workflow_callbacks_approved"
                                        ],
                                        "headers" => [
                                            "x-creator-id" => ""
                                        ],
                                        "service" => "api_live",
                                        "type" => "basic",
                                        "response_handler" => [
                                            "type" => "success_status_codes",
                                            "success_status_codes" => [
                                                201,
                                                200
                                            ]
                                        ]
                                    ],
                                    "rejected" => [
                                        "method" => "post",
                                        "payload" => [
                                            "type" => "state_callbacks_rejected"
                                        ],
                                        "headers" => [
                                            "x-creator-id" => ""
                                        ],
                                        "service" => "api_live",
                                        "type" => "basic",
                                        "response_handler" => [
                                            "type" => "success_status_codes",
                                            "success_status_codes" => [
                                                201,
                                                200
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "status" => "initiated",
                    "domain_status" => "created",
                    "owner_id" => "10000000000000",
                    "owner_type" => "merchant",
                    "org_id" => "100000razorpay",
                    "states" => [
                        "Owner_Approval" => [
                            "workflow_id" => "FV58BuqLuCP4Cw",
                            "status" => "created",
                            "name" => "Owner_Approval",
                            "group_name" => "ABC",
                            "type" => "checker",
                            "rules" => [
                                "actor_property_key" => "role",
                                "actor_property_value" => "owner",
                                "count" => 1
                            ],
                            "pending_on_user" => true,
                        ]
                    ],
                    "type" => "payout-approval",
                    "pending_on_user" => true
                ],
                "notes" => [
                    "random_key1" => "Hello",
                    "random_key2" => "Hi"
                ],
                "fees" => 0,
                "tax" => 0,
                "status" => "pending",
                "purpose" => "refund",
                "utr" => null,
                "user_id" => null,
                "mode" => "NEFT",
                "reference_id" => null,
                "narration" => "Test Merchant Fund Transfer",
                "batch_id" => null,
                "cancelled_at" => null,
                "queued_at" => null,
                "banking_account_id" => "bacc_1000000lcustba",
                "initiated_at" => null,
                "processed_at" => null,
                "reversed_at" => null,
                "failed_at" => null,
                "rejected_at" => null,
                "failure_reason" => null,
                "fee_type" => null,
                "scheduled_at" => null,
                "scheduled_on" => null
            ]
        ],
    ],

    'testApprovePayoutWithNWFSWithQueuingEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
                'queue_if_low_balance'  => 1,
            ],
        ],
        'response' => [
            'content' =>  [
                "entity" => "payout",
                "fund_account_id" => "fa_100000000000fa",
                "amount" => 10000,
                "currency" => "INR",
                "transaction_id" => null,
                "pending_on_user" => true,
                "workflow_history" => [
                    "config_id" => "FV0aQGxYU4kk4c",
                    "entity_type" => "payouts",
                    "title" => "title",
                    "description" => "[]",
                    "config_version" => "1",
                    "creator_id" => "10000000000000",
                    "creator_type" => "merchant",
                    "diff" => [
                        "old" => [
                            "amount" => null,
                            "merchant_id" => null
                        ],
                        "new" => [
                            "amount" => 10000,
                            "merchant_id" => "10000000000000"
                        ]
                    ],
                    "callback_details" => [
                        "state_callbacks" => [
                            "created" => [
                                "method" => "post",
                                "payload" => [
                                    "queue_if_low_balance" => true,
                                    "type" => "state_callbacks_created"
                                ],
                                "headers" => [
                                    "x-creator-id" => ""
                                ],
                                "service" => "api_live",
                                "type" => "basic",
                                "response_handler" => [
                                    "type" => "success_status_codes",
                                    "success_status_codes" => [
                                        201,
                                        200
                                    ]
                                ]
                            ],
                            "processed" => [
                                "method" => "post",
                                "payload" => [
                                    "queue_if_low_balance" => true,
                                    "type" => "state_callbacks_processed"
                                ],
                                "headers" => [
                                    "x-creator-id" => ""
                                ],
                                "service" => "api_live",
                                "type" => "basic",
                                "response_handler" => [
                                    "type" => "success_status_codes",
                                    "success_status_codes" => [
                                        201,
                                        200
                                    ]
                                ]
                            ]
                        ],
                        "workflow_callbacks" => [
                            "processed" => [
                                "domain_status" => [
                                    "approved" => [
                                        "method" => "post",
                                        "payload" => [
                                            "queue_if_low_balance" => true,
                                            "type" => "workflow_callbacks_approved"
                                        ],
                                        "headers" => [
                                            "x-creator-id" => ""
                                        ],
                                        "service" => "api_live",
                                        "type" => "basic",
                                        "response_handler" => [
                                            "type" => "success_status_codes",
                                            "success_status_codes" => [
                                                201,
                                                200
                                            ]
                                        ]
                                    ],
                                    "rejected" => [
                                        "method" => "post",
                                        "payload" => [
                                            "queue_if_low_balance" => true,
                                            "type" => "state_callbacks_rejected"
                                        ],
                                        "headers" => [
                                            "x-creator-id" => ""
                                        ],
                                        "service" => "api_live",
                                        "type" => "basic",
                                        "response_handler" => [
                                            "type" => "success_status_codes",
                                            "success_status_codes" => [
                                                201,
                                                200
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "status" => "initiated",
                    "domain_status" => "created",
                    "owner_id" => "10000000000000",
                    "owner_type" => "merchant",
                    "org_id" => "100000razorpay",
                    "states" => [
                        "Owner_Approval" => [
                            "workflow_id" => "FV58BuqLuCP4Cw",
                            "status" => "created",
                            "name" => "Owner_Approval",
                            "group_name" => "ABC",
                            "type" => "checker",
                            "rules" => [
                                "actor_property_key" => "role",
                                "actor_property_value" => "owner",
                                "count" => 1
                            ],
                            "pending_on_user" => true,
                        ]
                    ],
                    "type" => "payout-approval",
                    "pending_on_user" => true
                ],
                "notes" => [
                    "random_key1" => "Hello",
                    "random_key2" => "Hi"
                ],
                "fees" => 0,
                "tax" => 0,
                "status" => "pending",
                "purpose" => "refund",
                "utr" => null,
                "user_id" => null,
                "mode" => "NEFT",
                "reference_id" => null,
                "narration" => "Test Merchant Fund Transfer",
                "batch_id" => null,
                "cancelled_at" => null,
                "queued_at" => null,
                "banking_account_id" => "bacc_1000000lcustba",
                "initiated_at" => null,
                "processed_at" => null,
                "reversed_at" => null,
                "failed_at" => null,
                "rejected_at" => null,
                "failure_reason" => null,
                "fee_type" => null,
                "scheduled_at" => null,
                "scheduled_on" => null
            ]
        ],
    ],

    'testRejectPayoutWithNewWorkflowService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/reject',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content' => [
                "entity" => "payout",
                "fund_account_id" => "fa_100000000000fa",
                "amount" => 10000,
                "currency" => "INR",
                "transaction_id" => null,
                "pending_on_user" => true,
                "workflow_history" => [
                    "config_id" => "FV0aQGxYU4kk4c",
                    "entity_type" => "payouts",
                    "title" => "title",
                    "description" => "[]",
                    "config_version" => "1",
                    "creator_id" => "10000000000000",
                    "creator_type" => "merchant",
                    "diff" => [
                        "old" => [
                            "amount" => null,
                            "merchant_id" => null
                        ],
                        "new" => [
                            "amount" => 10000,
                            "merchant_id" => "10000000000000"
                        ]
                    ],
                    "callback_details" => [
                        "state_callbacks" => [
                            "created" => [
                                "method" => "post",
                                "payload" => [
                                    "queue_if_low_balance" => true,
                                    "type" => "state_callbacks_created"
                                ],
                                "headers" => [
                                    "x-creator-id" => ""
                                ],
                                "service" => "api_live",
                                "type" => "basic",
                                "response_handler" => [
                                    "type" => "success_status_codes",
                                    "success_status_codes" => [
                                        201,
                                        200
                                    ]
                                ]
                            ],
                            "processed" => [
                                "method" => "post",
                                "payload" => [
                                    "queue_if_low_balance" => true,
                                    "type" => "state_callbacks_processed"
                                ],
                                "headers" => [
                                    "x-creator-id" => ""
                                ],
                                "service" => "api_live",
                                "type" => "basic",
                                "response_handler" => [
                                    "type" => "success_status_codes",
                                    "success_status_codes" => [
                                        201,
                                        200
                                    ]
                                ]
                            ]
                        ],
                        "workflow_callbacks" => [
                            "processed" => [
                                "domain_status" => [
                                    "approved" => [
                                        "method" => "post",
                                        "payload" => [
                                            "queue_if_low_balance" => true,
                                            "type" => "workflow_callbacks_approved"
                                        ],
                                        "headers" => [
                                            "x-creator-id" => ""
                                        ],
                                        "service" => "api_live",
                                        "type" => "basic",
                                        "response_handler" => [
                                            "type" => "success_status_codes",
                                            "success_status_codes" => [
                                                201,
                                                200
                                            ]
                                        ]
                                    ],
                                    "rejected" => [
                                        "method" => "post",
                                        "payload" => [
                                            "queue_if_low_balance" => true,
                                            "type" => "state_callbacks_rejected"
                                        ],
                                        "headers" => [
                                            "x-creator-id" => ""
                                        ],
                                        "service" => "api_live",
                                        "type" => "basic",
                                        "response_handler" => [
                                            "type" => "success_status_codes",
                                            "success_status_codes" => [
                                                201,
                                                200
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "status" => "initiated",
                    "domain_status" => "created",
                    "owner_id" => "10000000000000",
                    "owner_type" => "merchant",
                    "org_id" => "100000razorpay",
                    "states" => [
                        "Owner_Approval" => [
                            "status" => "created",
                            "name" => "Owner_Approval",
                            "group_name" => "ABC",
                            "type" => "checker",
                            "rules" => [
                                "actor_property_key" => "role",
                                "actor_property_value" => "owner",
                                "count" => 1
                            ],
                            "pending_on_user" => true,
                            "created_at" => "1598377315",
                            "updated_at" => "1598377315"
                        ]
                    ],
                    "type" => "payout-approval",
                    "pending_on_user" => true
                ],
                "notes" => [
                ],
                "fees" => 0,
                "tax" => 0,
                "status" => "pending",
                "purpose" => "refund",
                "utr" => null,
                "user_id" => null,
                "mode" => "NEFT",
                "reference_id" => null,
                "narration" => "Test Merchant Fund Transfer",
                "batch_id" => null,
                "cancelled_at" => null,
                "queued_at" => null,
                "banking_account_id" => "bacc_1000000lcustba",
                "initiated_at" => null,
                "processed_at" => null,
                "reversed_at" => null,
                "failed_at" => null,
                "rejected_at" => null,
                "failure_reason" => null,
                "fee_type" => null,
                "scheduled_at" => null,
                "scheduled_on" => null
            ],
        ],
    ],

    'testRejectPayoutWithRejectCommentInWebhookWithWFS' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_internal/{id}/reject',
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '1234',
                'user_comment' => 'Rejecting',
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'rejected',
            ],
        ],
    ],

    'testRejectPayoutWithRejectCommentInWebhookWithoutCommentWithWFS' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_internal/{id}/reject',
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '1234',
                'user_comment' => null,
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'rejected',
            ],
        ],
    ],

    'testRejectPayoutWithRejectCommentInWebhook' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/reject',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '1234',
                'user_comment' => 'Rejecting',
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'rejected',
            ],
        ],
    ],

    'testRejectPayoutWithRejectCommentInWebhookWithoutComment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/reject',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '1234',
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'rejected',
            ],
        ],
    ],

    'testApprovePayoutCallbackFromNWFS' => [
        'request'  => [
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts_internal/{id}/approve',
            'content' => [
                'queue_if_low_balance'  => false,
                'type' => 'workflow_callbacks_approved',
            ],
        ],
        'response' => [
            'content' => [
                "entity" => "payout",
                "fund_account_id" => "fa_100000000000fa",
                "fund_account" => [
                    "id" => "fa_100000000000fa",
                    "entity" => "fund_account",
                    "contact_id" => "cont_1000001contact",
                    "account_type" => "bank_account",
                    "bank_account" => [
                        "ifsc" => "YESB0CMSNOC",
                        "bank_name" => "Yes Bank",
                        "name" => "random_name",
                        "notes" => [
                        ],
                        "account_number" => "2224440041626905"
                    ],
                    "batch_id" => null,
                    "active" => true,
                ],
                "amount" => 10000,
                "currency" => "INR",
                "transaction" => [
                    "entity" => "transaction",
                    "account_number" => "2224440041626905",
                    "amount" => 10590,
                    "currency" => "INR",
                    "credit" => 0,
                    "debit" => 10590,
                    "balance" => 9989410,
                ],
                "pending_on_user" => true,
                "workflow_history" => [
                    "config_id" => "FV0aQGxYU4kk4c",
                    "entity_type" => "payouts",
                    "title" => "title",
                    "description" => "[]",
                    "config_version" => "1",
                    "creator_id" => "10000000000000",
                    "creator_type" => "merchant",
                    "diff" => [
                        "old" => [
                            "amount" => null,
                            "merchant_id" => null
                        ],
                        "new" => [
                            "amount" => 10000,
                            "merchant_id" => "10000000000000"
                        ]
                    ],
                    "callback_details" => [
                        "state_callbacks" => [
                            "created" => [
                                "method" => "post",
                                "payload" => [
                                    "queue_if_low_balance" => true,
                                    "type" => "state_callbacks_created"
                                ],
                                "headers" => [
                                    "x-creator-id" => ""
                                ],
                                "service" => "api_live",
                                "type" => "basic",
                                "response_handler" => [
                                    "type" => "success_status_codes",
                                    "success_status_codes" => [
                                        201,
                                        200
                                    ]
                                ]
                            ],
                            "processed" => [
                                "method" => "post",
                                "payload" => [
                                    "queue_if_low_balance" => true,
                                    "type" => "state_callbacks_processed"
                                ],
                                "headers" => [
                                    "x-creator-id" => ""
                                ],
                                "service" => "api_live",
                                "type" => "basic",
                                "response_handler" => [
                                    "type" => "success_status_codes",
                                    "success_status_codes" => [
                                        201,
                                        200
                                    ]
                                ]
                            ]
                        ],
                        "workflow_callbacks" => [
                            "processed" => [
                                "domain_status" => [
                                    "approved" => [
                                        "method" => "post",
                                        "payload" => [
                                            "queue_if_low_balance" => true,
                                            "type" => "workflow_callbacks_approved"
                                        ],
                                        "headers" => [
                                            "x-creator-id" => ""
                                        ],
                                        "service" => "api_live",
                                        "type" => "basic",
                                        "response_handler" => [
                                            "type" => "success_status_codes",
                                            "success_status_codes" => [
                                                201,
                                                200
                                            ]
                                        ]
                                    ],
                                    "rejected" => [
                                        "method" => "post",
                                        "payload" => [
                                            "queue_if_low_balance" => true,
                                            "type" => "state_callbacks_rejected"
                                        ],
                                        "headers" => [
                                            "x-creator-id" => ""
                                        ],
                                        "service" => "api_live",
                                        "type" => "basic",
                                        "response_handler" => [
                                            "type" => "success_status_codes",
                                            "success_status_codes" => [
                                                201,
                                                200
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "status" => "initiated",
                    "domain_status" => "created",
                    "owner_id" => "10000000000000",
                    "owner_type" => "merchant",
                    "org_id" => "100000razorpay",
                    "states" => [
                        "Owner_Approval" => [
                            "status" => "created",
                            "name" => "Owner_Approval",
                            "group_name" => "ABC",
                            "type" => "checker",
                            "rules" => [
                                "actor_property_key" => "role",
                                "actor_property_value" => "owner",
                                "count" => 1
                            ],
                            "pending_on_user" => true,
                        ]
                    ],
                    "type" => "payout-approval",
                    "pending_on_user" => true
                ],
                "notes" => [
                ],
                "fees" => 590,
                "tax" => 90,
                "status" => "processing",
                "purpose" => "refund",
                "utr" => null,
                "user_id" => null,
                "mode" => "NEFT",
                "reference_id" => null,
                "narration" => "Test Merchant Fund Transfer",
                "batch_id" => null,
                "cancelled_at" => null,
                "queued_at" => null,
                "banking_account_id" => "bacc_1000000lcustba",
                "processed_at" => null,
                "reversed_at" => null,
                "failed_at" => null,
                "rejected_at" => null,
                "failure_reason" => null,
                "fee_type" => null,
                "scheduled_at" => null,
                "scheduled_on" => null
            ],
        ],
    ],

    'testRejectPayoutCallbackFromNWFS' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_internal/{id}/reject',
            'content' => [
                'queue_if_low_balance'  => 0,
            ],
        ],
        'response' => [
            'content' => [
                "entity" => "payout",
                "fund_account_id" => "fa_100000000000fa",
                "fund_account" => [
                    "id" => "fa_100000000000fa",
                    "entity" => "fund_account",
                    "contact_id" => "cont_1000001contact",
                    "account_type" => "bank_account",
                    "bank_account" => [
                        "ifsc" => "YESB0CMSNOC",
                        "bank_name" => "Yes Bank",
                        "name" => "random_name",
                        "notes" => [
                        ],
                        "account_number" => "2224440041626905"
                    ],
                    "batch_id" => null,
                    "active" => true,
                ],
                "amount" => 10000,
                "currency" => "INR",
                "transaction_id" => null,
                "pending_on_user" => true,
                "workflow_history" => [
                    "config_id" => "FV0aQGxYU4kk4c",
                    "entity_type" => "payouts",
                    "title" => "title",
                    "description" => "[]",
                    "config_version" => "1",
                    "creator_id" => "10000000000000",
                    "creator_type" => "merchant",
                    "diff" => [
                        "old" => [
                            "amount" => null,
                            "merchant_id" => null
                        ],
                        "new" => [
                            "amount" => 10000,
                            "merchant_id" => "10000000000000"
                        ]
                    ],
                    "callback_details" => [
                        "state_callbacks" => [
                            "created" => [
                                "method" => "post",
                                "payload" => [
                                    "queue_if_low_balance" => true,
                                    "type" => "state_callbacks_created"
                                ],
                                "headers" => [
                                    "x-creator-id" => ""
                                ],
                                "service" => "api_live",
                                "type" => "basic",
                                "response_handler" => [
                                    "type" => "success_status_codes",
                                    "success_status_codes" => [
                                        201,
                                        200
                                    ]
                                ]
                            ],
                            "processed" => [
                                "method" => "post",
                                "payload" => [
                                    "queue_if_low_balance" => true,
                                    "type" => "state_callbacks_processed"
                                ],
                                "headers" => [
                                    "x-creator-id" => ""
                                ],
                                "service" => "api_live",
                                "type" => "basic",
                                "response_handler" => [
                                    "type" => "success_status_codes",
                                    "success_status_codes" => [
                                        201,
                                        200
                                    ]
                                ]
                            ]
                        ],
                        "workflow_callbacks" => [
                            "processed" => [
                                "domain_status" => [
                                    "approved" => [
                                        "method" => "post",
                                        "payload" => [
                                            "queue_if_low_balance" => true,
                                            "type" => "workflow_callbacks_approved"
                                        ],
                                        "headers" => [
                                            "x-creator-id" => ""
                                        ],
                                        "service" => "api_live",
                                        "type" => "basic",
                                        "response_handler" => [
                                            "type" => "success_status_codes",
                                            "success_status_codes" => [
                                                201,
                                                200
                                            ]
                                        ]
                                    ],
                                    "rejected" => [
                                        "method" => "post",
                                        "payload" => [
                                            "queue_if_low_balance" => true,
                                            "type" => "state_callbacks_rejected"
                                        ],
                                        "headers" => [
                                            "x-creator-id" => ""
                                        ],
                                        "service" => "api_live",
                                        "type" => "basic",
                                        "response_handler" => [
                                            "type" => "success_status_codes",
                                            "success_status_codes" => [
                                                201,
                                                200
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "status" => "initiated",
                    "domain_status" => "created",
                    "owner_id" => "10000000000000",
                    "owner_type" => "merchant",
                    "org_id" => "100000razorpay",
                    "states" => [
                        "Owner_Approval" => [
                            "status" => "created",
                            "name" => "Owner_Approval",
                            "group_name" => "ABC",
                            "type" => "checker",
                            "rules" => [
                                "actor_property_key" => "role",
                                "actor_property_value" => "owner",
                                "count" => 1
                            ],
                            "pending_on_user" => true,
                        ]
                    ],
                    "type" => "payout-approval",
                    "pending_on_user" => true
                ],
                "notes" => [
                ],
                "fees" => 0,
                "tax" => 0,
                "status" => "rejected",
                "purpose" => "refund",
                "utr" => null,
                "user_id" => null,
                "mode" => "NEFT",
                "reference_id" => null,
                "narration" => "Test Merchant Fund Transfer",
                "batch_id" => null,
                "cancelled_at" => null,
                "queued_at" => null,
                "banking_account_id" => "bacc_1000000lcustba",
                "initiated_at" => null,
                "processed_at" => null,
                "reversed_at" => null,
                "failed_at" => null,
                "failure_reason" => null,
                "fee_type" => null,
                "scheduled_at" => null,
                "scheduled_on" => null
            ],
        ],
    ],

    'testRejectPayoutCallbackFromNWFSTwice' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_internal/{id}/reject',
            'content' => [
                'queue_if_low_balance'  => 0,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CONFLICT_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 409,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_CONFLICT_ALREADY_EXISTS,
        ],
    ],

    'testGetWorkflowFromNWFS' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/{id}/history',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "config_id" => "FV0aQGxYU4kk4c",
                "entity_type" => "payouts",
                "title" => "title",
                "description" => "[]",
                "config_version" => "1",
                "creator_id" => "10000000000000",
                "creator_type" => "merchant",
                "diff" => [
                    "old" => [
                        "amount" => null,
                        "merchant_id" => null
                    ],
                    "new" => [
                        "amount" => 10000,
                        "merchant_id" => "10000000000000"
                    ]
                ],
                "callback_details" => [
                    "state_callbacks" => [
                        "created" => [
                            "method" => "post",
                            "payload" => [
                                "queue_if_low_balance" => true,
                                "type" => "state_callbacks_created"
                            ],
                            "headers" => [
                                "x-creator-id" => ""
                            ],
                            "service" => "api_live",
                            "type" => "basic",
                            "response_handler" => [
                                "type" => "success_status_codes",
                                "success_status_codes" => [
                                    201,
                                    200
                                ]
                            ]
                        ],
                        "processed" => [
                            "method" => "post",
                            "payload" => [
                                "queue_if_low_balance" => true,
                                "type" => "state_callbacks_processed"
                            ],
                            "headers" => [
                                "x-creator-id" => ""
                            ],
                            "service" => "api_live",
                            "type" => "basic",
                            "response_handler" => [
                                "type" => "success_status_codes",
                                "success_status_codes" => [
                                    201,
                                    200
                                ]
                            ]
                        ]
                    ],
                    "workflow_callbacks" => [
                        "processed" => [
                            "domain_status" => [
                                "approved" => [
                                    "method" => "post",
                                    "payload" => [
                                        "queue_if_low_balance" => true,
                                        "type" => "workflow_callbacks_approved"
                                    ],
                                    "headers" => [
                                        "x-creator-id" => ""
                                    ],
                                    "service" => "api_live",
                                    "type" => "basic",
                                    "response_handler" => [
                                        "type" => "success_status_codes",
                                        "success_status_codes" => [
                                            201,
                                            200
                                        ]
                                    ]
                                ],
                                "rejected" => [
                                    "method" => "post",
                                    "payload" => [
                                        "queue_if_low_balance" => true,
                                        "type" => "state_callbacks_rejected"
                                    ],
                                    "headers" => [
                                        "x-creator-id" => ""
                                    ],
                                    "service" => "api_live",
                                    "type" => "basic",
                                    "response_handler" => [
                                        "type" => "success_status_codes",
                                        "success_status_codes" => [
                                            201,
                                            200
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                "status" => "initiated",
                "domain_status" => "created",
                "owner_id" => "10000000000000",
                "owner_type" => "merchant",
                "org_id" => "100000razorpay",
                "states" => [
                    "Owner_Approval" => [
                        "workflow_id" => "FV58BuqLuCP4Cw",
                        "status" => "created",
                        "name" => "Owner_Approval",
                        "group_name" => "ABC",
                        "type" => "checker",
                        "rules" => [
                            "actor_property_key" => "role",
                            "actor_property_value" => "owner",
                            "count" => 1
                        ],
                        "pending_on_user" => true,
                    ]
                ],
                "type" => "payout-approval",
                "pending_on_user" => true
            ],
        ],
    ],

    'testBulkPayoutWithSameFundAccountNWFS' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ]
            ]
        ],
        'response'                                  =>
            [
                'content'                               => [
                    'entity'                            => 'collection',
                    'count'                             => 1,
                    'items'                             => [
                        [
                            'entity'                    => 'payout',
                            'fund_account'              => [
                                'entity'                => 'fund_account',
                                'account_type'          => 'bank_account',
                                'bank_account'          => [
                                    'ifsc'              => 'HDFC0003780',
                                    'bank_name'         => 'HDFC Bank',
                                    'name'              => 'Vivek Karna',
                                    'account_number'    => '50100244702362',
                                ],
                                'active'                => true,
                            ],
                            'amount'                    => 100,
                            'currency'                  => 'INR',
                            'status'                    => 'pending',
                            'purpose'                   => 'refund',
                            'utr'                       => null,
                            'user_id'                   => 'MerchantUser01',
                            'mode'                      => 'IMPS',
                            'reference_id'              => null,
                            'narration'                 => '123',
                            'idempotency_key'           => 'batch_abc123'
                        ]
                    ]
                ],
            ],
    ],

    'testBulkPayoutApprovalNWFS' => [
        'request' => [
            'url' => '/payouts/bulk_approve',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                [
                    'payout_update_action' => 'A',
                    'razorpayx_account_number' => '2224440041626905',
                    'user_comment' => 'Some user comment',
                    'payout' => [
                        'amount' => '10000',
                        'currency' => 'INR',
                        'mode' => 'IMPS',
                        'purpose' => 'refund',
                        'narration' => '123',
                        'status' => 'pending',
                    ],
                    'fund' => [

                    ],
                    'contact' => [
                        'name' => 'Vivek Karna',
                        'reference_id' => ''
                    ],
                    'idempotency_key' => 'batch_abc123'
                ]
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity' => 'payout',
                        'amount' => 10000,
                        'currency' => 'INR',
                        'status'   => 'pending',
                        'idempotency_key' => 'batch_abc123'
                    ]
                ]
            ],
        ],
    ],

    'testBulkRejectPayoutWithAdminWithNWFS' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/payouts/cancel',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],

    'testBulkRejectPayoutWithAdminWithNWFSToPS' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/payouts/cancel',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'total_count'   => 1,
                'failed_ids'    => []
            ],
        ],
    ],

    'testBulkRetryWorkflowOnPayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/payouts/workflow_retry',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],

    'testPayoutRejectWhenWorkflowEdit' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'        => '2224440041626905',
                'amount'                => 10000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'NEFT',
                'queue_if_low_balance'  => 0,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Workflow edit on the same payout rule is active',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_WORKFLOW_EDIT_IN_PROGRESS,
        ],
    ],

    'testApprovePayoutWithoutComment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'   => 'BUIj3m2Nx2VvVj',
                'otp'     => '0007',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testPendingToOnHoldAndProcessing' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'   => 'BUIj3m2Nx2VvVj',
                'otp'     => '0007',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testApprovePayoutWithInvalidOtp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj',
                'otp'   => '1234',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INCORRECT_OTP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP,
        ],
    ],

    'testBulkApprovePayoutWithComment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/approve/bulk',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'payout_ids'   => [],
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Bulk Approving'
            ],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],

    'testBulkApprovePayoutWithNullComment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/approve/bulk',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'payout_ids'   => [],
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => null
            ],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],

    'testBulkApprovePayoutWithoutComment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/approve/bulk',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'payout_ids' => [],
                'token'      => 'BUIj3m2Nx2VvVj',
                'otp'        => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],

    'testRejectPayoutWithComment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/reject',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '1234',
                'user_comment' => 'Rejecting',
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'rejected',
            ],
        ],
    ],

    'testRejectPayoutWithNullComment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/reject',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '1234',
                'user_comment' =>  null,
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'rejected',
            ],
        ],
    ],

    'testFiringOfWebhookRejectPayoutWithStork' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/reject',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '1234',
                'user_comment' => 'Rejecting',
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'rejected',
            ],
        ],
    ],

    'testRejectPayoutWithoutComment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/reject',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'rejected',
            ],
        ],
    ],

    'testBulkRejectPayoutsWithComment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/reject/bulk',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'payout_ids' => [],
                'user_comment'    => 'Bulk Rejecting',
            ],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],

    'testAutoRejectPayoutWithNewWorkflowServiceViaCron' => [
        'request'  => [
            'method'    => 'POST',
            'url'       => '/payouts/auto_expire'
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAutoExpiryofPayoutsAfterThreeMonths' => [
        'request'  => [
            'method'    => 'POST',
            'url'       => '/payouts/auto_expire'
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAutoExpiryNotApplicableToExcludedMerchant' => [
        'request'  => [
            'method'    => 'POST',
            'url'       => '/payouts/auto_expire',
            'content'   => [
                'excluded_merchant_ids' => ['10000000000000'],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAutoExpiryOfPayoutsAfterThreeMonthsForRejectedPayoutToPS' => [
        'request'  => [
            'method'    => 'POST',
            'url'       => '/payouts/auto_expire'
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testBulkRejectPayoutsWithoutComment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/reject/bulk',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'payout_ids' => [],
            ],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],

    'testCreatePayoutForAmountLessThanMinFee' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [],
            ],
        ],
    ],

    'testCreateQueuedPayout' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 10000001,
                'currency'              => 'INR',
                'mode'                  => 'IMPS',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'queue_if_low_balance'  => true,
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000001,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'utr'             => null,
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [],
            ],
        ],
    ],

    'testCreateQueuedPayoutWithNewCreditsFlow' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 10000001,
                'currency'              => 'INR',
                'mode'                  => 'IMPS',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'queue_if_low_balance'  => true,
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000001,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'utr'             => null,
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [],
            ],
        ],
    ],

    'testCreateAndProcessQueuedPayout' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 10000001,
                'currency'              => 'INR',
                'mode'                  => 'IMPS',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'queue_if_low_balance'  => true,
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000001,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'utr'             => null,
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [],
            ],
        ],
    ],

    'testCreateAndProcessQueuedPayoutWithNewCreditsFlow' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 10000001,
                'currency'              => 'INR',
                'mode'                  => 'IMPS',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'queue_if_low_balance'  => true,
            ]
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testCreatePayoutToCardFundAccount' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 100,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000002fa',
                'mode'                  => 'IMPS'
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'mode'            => 'IMPS',
                'purpose'         => 'refund',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [],
            ],
        ],
    ],

    'testCreatePayoutToInactiveFundAccount' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 1000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000001fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payouts cannot be created on an inactive fund account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'field' => 'fund_account_id',
            'data'  =>  [
                'fund_account_id' => 'fa_100000000001fa',
                'contact_id'      => '1000000contact',
            ],

        ],
    ],

    'testCreatePayoutToFundAccountWithoutContact' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 1000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000004ff',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payouts cannot be created for fund account without contact.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutToInactiveContactFundAccount' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 1000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000001fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payouts cannot be created on an inactive contact fund account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'field' => 'fund_account_id',
            'data'  =>  [
                'fund_account_id' => 'fa_100000000001fa',
                'contact_id'      => '1000000contact',
            ],

        ],
    ],

    'testCreateMerchantPayout' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout',
            'content' => [
                'amount'         => 1000,
                'merchant_id'    => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 1000,
                'currency'    => 'INR',
                'tax'         => 92,
                'fees'        => 602,
                'notes'       => []
            ],
        ],
    ],

    'testCreateMerchantPayoutWithModulo' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout',
            'content' => [
                'amount'         => 1200,
                'merchant_id'    => '10000000000000',
                'modulo'         => 1000
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 1000,
                'currency'    => 'INR',
                'tax'         => 92,
                'fees'        => 602,
                'notes'       => []
            ],
        ],
    ],

    'testCreateMerchantPayoutWithMinAmount' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout',
            'content' => [
                'amount'         => 2200,
                'merchant_id'    => '10000000000000',
                'min_amount'     => 3000,
                'modulo'         => 1000
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amount is less than min amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutFundsOnHold' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'    => '2224440041626905',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'fund_account_id'   => 'fa_100000000000fa',
                'mode'              => 'NEFT',
                'purpose'           => 'refund',
                'notes'             => [
                    'abc' => 'xyz',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
        ],
    ],

    'testCreatePayoutFundsOnHoldForCurrentAccount' => [
        'request' => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
            ],
        ],
        'response' => [
            'content' =>[
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'failure_reason'  => null,
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithVaultTokenForNonRefundsApp' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                "account_number"    => '2224440041626905',
                "amount"            => 1000000,
                "currency"          => "INR",
                "mode"              => "NEFT",
                "purpose"           => "refund",
                "fund_account"  => [
                    "account_type"  => "card",
                    "card" => [
                        "token"  => "NDAwMDQwMDAwMDAwMDAwNA==",
                    ],
                    "contact"  => [
                        "name"          => "Gaurav Kumar",
                        "email"         => "gaurav.kumar@example.com",
                        "contact"       => "9876543210",
                        "type"          => "employee",
                        "reference_id"  => "188181269",
                        "notes"  => [
                            "notes_key_1"  => "Tea, Earl Grey, Hot",
                            "notes_key_2"  => "Tea, Earl Grey... decaf."
                        ]
                    ]
                ],
                "source_details"  =>  [
                    [
                        "source_id"     =>  "HYKmlGHHyEhZuM", // refund id
                        "source_type"   =>  "refund",
                        "priority"      =>  1
                    ]
                ],
                "queue_if_low_balance"  => true,
                "reference_id"          => "can be use to store refund id",
                "narration"             => "Acme Corp Fund Transfer",
                "notes"  => [
                    "notes_key_1"  => "Beam me up Scotty",
                    "notes_key_2"  => "Engage"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'card.token is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testCreatePayoutWithVaultTokenAndCardNumberForRefundsApp' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                "account_number"    => '2224440041626905',
                "amount"            => 1000000,
                "currency"          => "INR",
                "mode"              => "NEFT",
                "purpose"           => "refund",
                "fund_account"  => [
                    "account_type"  => "card",
                    "card" => [
                        "token"        => "NDAwMDQwMDAwMDAwMDAwNA==",
                        "number"       => "340169570990137",
                    ],
                    "contact"  => [
                        "name"      => "Gaurav Kumar",
                        "email"     => "gaurav.kumar@example.com",
                        "contact"   => "9876543210",
                        "type"      => "employee",
                        "reference_id"  => "188181269",
                        "notes"  => [
                            "notes_key_1"  => "Tea, Earl Grey, Hot",
                            "notes_key_2"  => "Tea, Earl Grey... decaf."
                        ]
                    ]
                ],
                "source_details"  =>  [
                    [
                        "source_id"     =>  "HYKmlGHHyEhZuM", // refund id
                        "source_type"   =>  "refund",
                        "priority"      =>  1
                    ]
                ],
                "queue_if_low_balance"  => true,
                "reference_id"          => "can be use to store refund id",
                "narration"             => "Acme Corp Fund Transfer",
                "notes"  => [
                    "notes_key_1"  => "Beam me up Scotty",
                    "notes_key_2"  => "Engage"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'both card.token and card.number should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutWithInvalidNetworkForRefundsApp' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                "account_number"    => '2224440041626905',
                "amount"            => 1000000,
                "currency"          => "INR",
                "mode"              => "NEFT",
                "purpose"           => "refund",
                "fund_account"  => [
                    "account_type"  => "card",
                    "card" => [
                        "token"        => "NDAwMDQwMDAwMDAwMDAwNA==",
                    ],
                    "contact"  => [
                        "name"      => "Gaurav Kumar",
                        "email"     => "gaurav.kumar@example.com",
                        "contact"   => "9876543210",
                        "type"      => "employee",
                        "reference_id"  => "188181269",
                        "notes"  => [
                            "notes_key_1"  => "Tea, Earl Grey, Hot",
                            "notes_key_2"  => "Tea, Earl Grey... decaf."
                        ]
                    ]
                ],
                "source_details"  =>  [
                    [
                        "source_id"     =>  "HYKmlGHHyEhZuM", // refund id
                        "source_type"   =>  "refund",
                        "priority"      =>  1
                    ]
                ],
                "queue_if_low_balance"  => true,
                "reference_id"          => "can be use to store refund id",
                "narration"             => "Acme Corp Fund Transfer",
                "notes"  => [
                    "notes_key_1"  => "Beam me up Scotty",
                    "notes_key_2"  => "Engage"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'xyz is not a valid network',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCardPayoutWithoutTokenForRefundsApp' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                "account_number"    => '2224440041626905',
                "amount"            => 1000000,
                "currency"          => "INR",
                "mode"              => "NEFT",
                "purpose"           => "refund",
                "fund_account"  => [
                    "account_type"  => "card",
                    "card" => [
                        'international' => false,
                        'network'       => 'MC',
                        'trivia'        => null,
                        'input_type'    => 'card',
                    ],
                    "contact"  => [
                        "name"      => "Gaurav Kumar",
                        "email"     => "gaurav.kumar@example.com",
                        "contact"   => "9876543210",
                        "type"      => "employee",
                        "reference_id"  => "188181269",
                        "notes"  => [
                            "notes_key_1"  => "Tea, Earl Grey, Hot",
                            "notes_key_2"  => "Tea, Earl Grey... decaf."
                        ]
                    ]
                ],
                "source_details"  =>  [
                    [
                        "source_id"     =>  "HYKmlGHHyEhZuM", // refund id
                        "source_type"   =>  "refund",
                        "priority"      =>  1
                    ]
                ],
                "queue_if_low_balance"  => true,
                "reference_id"          => "can be use to store refund id",
                "narration"             => "Acme Corp Fund Transfer",
                "notes"  => [
                    "notes_key_1"  => "Beam me up Scotty",
                    "notes_key_2"  => "Engage"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'card.token should be sent if card.network/card.international/card.trivia is passed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutWithCardNumberForRefundsApp' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                "account_number"    => '2224440041626905',
                "amount"            => 1000000,
                "currency"          => "INR",
                "mode"              => "NEFT",
                "purpose"           => "refund",
                "fund_account"  => [
                    "account_type"  => "card",
                    "card" => [
                        "number"       => "340169570990137",
                    ],
                    "contact"  => [
                        "name"          => "Gaurav Kumar",
                        "email"         => "gaurav.kumar@example.com",
                        "contact"       => "9876543210",
                        "type"          => "employee",
                        "reference_id"  => "188181269",
                        "notes"  => [
                            "notes_key_1"  => "Tea, Earl Grey, Hot",
                            "notes_key_2"  => "Tea, Earl Grey... decaf."
                        ]
                    ]
                ],
                "source_details"  =>  [
                    [
                        "source_id"     =>  "HYKmlGHHyEhZuM", // refund id
                        "source_type"   =>  "refund",
                        "priority"      =>  1
                    ]
                ],
                "queue_if_low_balance"  => true,
                "reference_id"          => "can be use to store refund id",
                "narration"             => "Acme Corp Fund Transfer",
                "notes"  => [
                    "notes_key_1"  => "Beam me up Scotty",
                    "notes_key_2"  => "Engage"
                ]
            ],
        ],
        'response' => [
            'content' => [
                "entity"   =>  "payout",
                "fund_account"   =>  [
                    "entity"    =>  "fund_account",
                    "contact"   =>  [
                        "entity"        =>  "contact",
                        "name"          =>  "Gaurav Kumar",
                        "contact"       =>  "9876543210",
                        "email"         =>  "gaurav.kumar@example.com",
                        "type"          =>  "employee",
                        "reference_id"  =>  "188181269",
                        "batch_id"      =>  null,
                        "active"        =>  true,
                        "notes"   =>  [
                            "notes_key_1"   =>  "Tea, Earl Grey, Hot",
                            "notes_key_2"   =>  "Tea, Earl Grey... decaf."
                        ],
                    ],
                    "account_type"   =>  "card",
                    "card"   =>  [
                        "iin"       =>  "999999",
                        "last4"     =>  "0137",
                        "network"   =>  "American Express",
                        "type"      =>  "credit",
                        "issuer"    =>  null,
                        "sub_type"  =>  null
                    ],
                    "batch_id"      =>  null,
                    "active"        =>  true,
                ],
                "amount"   =>  1000000,
                "currency"   =>  "INR",
                "notes"   =>  [
                    "notes_key_1"   =>  "Beam me up Scotty",
                    "notes_key_2"   =>  "Engage"
                ],
                "status"                =>  "processing",
                "purpose"               =>  "refund",
                "mode"                  =>  "NEFT",
                "reference_id"          =>  "can be use to store refund id",
                "narration"             =>  "Acme Corp Fund Transfer",
                "batch_id"              =>  null,
                "banking_account_id"    =>  "bacc_ABCde1234ABCde",
                "failure_reason"        =>  null,
                "fee_type"              =>  null,
                "origin"                =>  "api",
                "source_details"   =>  [[
                    "source_type"       =>  "refund",
                    "priority"          =>  1
                ]],
                "remarks"   =>  null,
                "cancellation_user_id"   =>  null,
                "cancellation_user"      =>  []
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePayoutWithWithCardHavingRazorpayTokenInputTypeWithInvalidProviderDataForRefundsApp' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                "account_number"    => '2224440041626905',
                "amount"            => 1000000,
                "currency"          => "INR",
                "mode"              => "NEFT",
                "purpose"           => "refund",
                "fund_account"  => [
                    "account_type"  => "card",
                    "card" => [],
                    "contact"  => [
                        "name"          => "Gaurav Kumar",
                        "email"         => "gaurav.kumar@example.com",
                        "contact"       => "9876543210",
                        "type"          => "employee",
                        "reference_id"  => "188181269",
                        "notes"  => [
                            "notes_key_1"  => "Tea, Earl Grey, Hot",
                            "notes_key_2"  => "Tea, Earl Grey... decaf."
                        ]
                    ]
                ],
                "source_details"  =>  [
                    [
                        "source_id"     =>  "HYKmlGHHyEhZuM", // refund id
                        "source_type"   =>  "refund",
                        "priority"      =>  1
                    ]
                ],
                "queue_if_low_balance"  => true,
                "reference_id"          => "can be use to store refund id",
                "narration"             => "Acme Corp Fund Transfer",
                "notes"  => [
                    "notes_key_1"  => "Beam me up Scotty",
                    "notes_key_2"  => "Engage"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The token number field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutWithVaultTokenAndDummyNameForRefundsApp' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                "account_number"    => '2224440041626905',
                "amount"            => 1000000,
                "currency"          => "INR",
                "mode"              => "NEFT",
                "purpose"           => "refund",
                "fund_account"  => [
                    "account_type"  => "card",
                    "card" => [
                        "token"  => "MzQwMTY5NTcwOTkwMTM3==",
                    ],
                    "contact"  => [
                        "name"          => "Gaurav Kumar",
                        "email"         => "gaurav.kumar@example.com",
                        "contact"       => "9876543210",
                        "type"          => "employee",
                        "reference_id"  => "188181269",
                        "notes"  => [
                            "notes_key_1"  => "Tea, Earl Grey, Hot",
                            "notes_key_2"  => "Tea, Earl Grey... decaf."
                        ]
                    ]
                ],
                "source_details"  =>  [
                    [
                        "source_id"     =>  "HYKmlGHHyEhZuM", // refund id
                        "source_type"   =>  "refund",
                        "priority"      =>  1
                    ]
                ],
                "queue_if_low_balance"  => true,
                "reference_id"          => "can be use to store refund id",
                "narration"             => "Acme Corp Fund Transfer",
                "notes"  => [
                    "notes_key_1"  => "Beam me up Scotty",
                    "notes_key_2"  => "Engage"
                ]
            ],
        ],
        'response' => [
            'content' => [
                "entity"   =>  "payout",
                "fund_account"   =>  [
                    "entity"     =>  "fund_account",
                    "contact"   =>  [
                        "entity"        =>  "contact",
                        "name"          =>  "Gaurav Kumar",
                        "contact"       =>  "9876543210",
                        "email"         =>  "gaurav.kumar@example.com",
                        "type"          =>  "employee",
                        "reference_id"  =>  "188181269",
                        "batch_id"      =>  null,
                        "active"        =>  true,
                        "notes"   =>  [
                            "notes_key_1"   =>  "Tea, Earl Grey, Hot",
                            "notes_key_2"   =>  "Tea, Earl Grey... decaf."
                        ],
                    ],
                    "account_type"   =>  "card",
                    "card"   =>  [
                        "iin"           =>  "999999",
                        "last4"         =>  "0137",
                        "network"       =>  "American Express",
                        "type"          =>  "credit",
                        "issuer"        =>  null,
                        "sub_type"      =>  null
                    ],
                    "batch_id"      =>  null,
                    "active"        =>  true,
                ],
                "amount"   =>  1000000,
                "currency"   =>  "INR",
                "notes"   =>  [
                    "notes_key_1"   =>  "Beam me up Scotty",
                    "notes_key_2"   =>  "Engage"
                ],
                "status"                =>  "processing",
                "purpose"               =>  "refund",
                "mode"                  =>  "NEFT",
                "reference_id"          =>  "can be use to store refund id",
                "narration"             =>  "Acme Corp Fund Transfer",
                "batch_id"              =>  null,
                "banking_account_id"    =>  "bacc_ABCde1234ABCde",
                "failure_reason"        =>  null,
                "fee_type"              =>  null,
                "origin"                =>  "api",
                "source_details"   =>  [[
                    "source_type"   =>  "refund",
                    "priority"   =>  1
                ]],
                "remarks"   =>  null,
                "cancellation_user_id"  =>  null,
                "cancellation_user"     =>  []
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateNeftPayoutWithPgMerchantIdForRefundsApp' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                "account_number"    => '2224440041626905',
                "amount"            => 1000000,
                "currency"          => "INR",
                "mode"              => "NEFT",
                "purpose"           => "refund",
                "fund_account"  => [
                    "account_type"  => "card",
                    "card" => [
                        'international' => false,
                        'network'       => 'MC',
                        'trivia'        => null,
                        'token'         => 'JDzXk6S3CAjUn8',
                        'input_type'    => 'razorpay_token',
                    ],
                    "contact"  => [
                        "name"          => "Gaurav Kumar",
                        "email"         => "gaurav.kumar@example.com",
                        "contact"       => "9876543210",
                        "type"          => "employee",
                        "reference_id"  => "188181269",
                        "notes"  => [
                            "notes_key_1"  => "Tea, Earl Grey, Hot",
                            "notes_key_2"  => "Tea, Earl Grey... decaf."
                        ]
                    ]
                ],
                "source_details"  =>  [
                    [
                        "source_id"     =>  "HYKmlGHHyEhZuM", // refund id
                        "source_type"   =>  "refund",
                        "priority"      =>  1
                    ]
                ],
                "queue_if_low_balance"  => true,
                "reference_id"          => "can be use to store refund id",
                "narration"             => "Acme Corp Fund Transfer",
                "notes"  => [
                    "notes_key_1"  => "Beam me up Scotty",
                    "notes_key_2"  => "Engage"
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'pg_merchant_id is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutWithVaultTokenAndNonDummyNameForRefundsApp' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                "account_number"    => '2224440041626905',
                "amount"            => 1000000,
                "currency"          => "INR",
                "mode"              => "NEFT",
                "purpose"           => "refund",
                "fund_account"  => [
                    "account_type"  => "card",
                    "card" => [
                        "token"  => "MzQwMTY5NTcwOTkwMTM3==",
                    ],
                    "contact"  => [
                        "name"          => "Gaurav Kumar",
                        "email"         => "gaurav.kumar@example.com",
                        "contact"       => "9876543210",
                        "type"          => "employee",
                        "reference_id"  => "188181269",
                        "notes"  => [
                            "notes_key_1"  => "Tea, Earl Grey, Hot",
                            "notes_key_2"  => "Tea, Earl Grey... decaf."
                        ]
                    ]
                ],
                "source_details"  =>  [
                    [
                        "source_id"     =>  "HYKmlGHHyEhZuM", // refund id
                        "source_type"   =>  "refund",
                        "priority"      =>  1
                    ]
                ],
                "queue_if_low_balance"  => true,
                "reference_id"          => "can be use to store refund id",
                "narration"             => "Acme Corp Fund Transfer",
                "notes"  => [
                    "notes_key_1"  => "Beam me up Scotty",
                    "notes_key_2"  => "Engage"
                ]
            ],
        ],
        'response' => [
            'content' => [
                "entity"   =>  "payout",
                "fund_account"   =>  [
                    "entity"     =>  "fund_account",
                    "contact"   =>  [
                        "entity"        =>  "contact",
                        "name"          =>  "Gaurav Kumar",
                        "contact"       =>  "9876543210",
                        "email"         =>  "gaurav.kumar@example.com",
                        "type"          =>  "employee",
                        "reference_id"  =>  "188181269",
                        "batch_id"      =>  null,
                        "active"        =>  true,
                        "notes"   =>  [
                            "notes_key_1"   =>  "Tea, Earl Grey, Hot",
                            "notes_key_2"   =>  "Tea, Earl Grey... decaf."
                        ],
                    ],
                    "account_type"   =>  "card",
                    "card"   =>  [
                        "iin"           =>  "999999",
                        "last4"         =>  "0137",
                        "network"       =>  "American Express",
                        "type"          =>  "credit",
                        "issuer"        =>  null,
                        "sub_type"      =>  null
                    ],
                    "batch_id"      =>  null,
                    "active"        =>  true,
                ],
                "amount"   =>  1000000,
                "currency"   =>  "INR",
                "notes"   =>  [
                    "notes_key_1"   =>  "Beam me up Scotty",
                    "notes_key_2"   =>  "Engage"
                ],
                "status"                =>  "processing",
                "purpose"               =>  "refund",
                "mode"                  =>  "NEFT",
                "reference_id"          =>  "can be use to store refund id",
                "narration"             =>  "Acme Corp Fund Transfer",
                "batch_id"              =>  null,
                "banking_account_id"    =>  "bacc_ABCde1234ABCde",
                "failure_reason"        =>  null,
                "fee_type"              =>  null,
                "origin"                =>  "api",
                "source_details"   =>  [[
                    "source_type"   =>  "refund",
                    "priority"   =>  1
                ]],
                "remarks"   =>  null,
                "cancellation_user_id"  =>  null,
                "cancellation_user"     =>  []
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePayoutFundsOnHoldOnTestMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'    => '2224440041626905',
                'amount'            => 2000000,
                'currency'          => 'INR',
                'fund_account_id'   => 'fa_100000000000fa',
                'mode'              => 'IMPS',
                'purpose'           => 'refund',
                'notes'             => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'failure_reason'  => null,
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutInsufficientBalance' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'    => '2224440041626905',
                'amount'            => 300000000,
                'currency'          => 'INR',
                'fund_account_id'   => 'fa_100000000000fa',
                'mode'              => 'NEFT',
                'purpose'           => 'refund',
                'notes'             => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
        ],
    ],

    'testGetPayouts' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts?account_number=2224440041626905',
            'content' => [],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testPayoutsListApi' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts?account_number=2224440041626905',
            'content' => [],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetPayoutsWithRemovingPayoutFeatureForMerchant' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts?account_number=2224440041626905',
            'content' => [],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetPayoutsWithRemovingPayoutFeatureForMerchantWithProxyAuth' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts?account_number=2224440041626905',
            'content' => [],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetPayoutsForReferenceId' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts?account_number=2224440041626905',
            'content' => [],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetPayoutsForReversalId' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts?account_number=2224440041626905&reversal_id=',
            'content' => [
                'expand' => [
                    'reversal',
                    'user'
                ]
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetPayoutWithReversalForProxyAuth' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts/{id}',
            'content' => [
                'expand' => [
                    'reversal',
                ]
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetPayoutWithReversalForPrivateAuth' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts/{id}',
            'content' => [
                'expand' => [
                    'reversal',
                ]
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetPayoutWithReversalForPrivilegeAuthNonAccountingApp' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts_internal/{id}',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'expand' => [
                    'reversal',
                ]
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetPayoutWithReversalForPrivilegeAuthAccountingApp' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts_internal/{id}',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'expand' => [
                    'reversal',
                ]
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetPayoutsWithoutAccountNumber' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account number field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPayoutStatusReasonMapping' => [
        'request' => [
            'method' => 'get',
            'url'    => '/payouts_status_reason_map',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetPayout' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts/{id}',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testStatusSummaryObjectInGetPayout' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts/{id}',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testStatusSummaryObjectNullCaseInGetPayout' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts/{id}',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testCreatePaymentPayout' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payments/{id}/payout',
            'content' => [
                'amount'          => 1000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'IMPS',
                'purpose'         => 'refund',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'            => 'payout',
                'amount'            => 1000,
                'currency'          => 'INR',
                'fund_account_id'   => 'fa_100000000000fa',
                'mode'              => 'IMPS',
                'tax'               => 92,
                'fees'              => 602,
                'notes'             => [
                    'abc' => 'xyz',
                ],
            ],
        ]
    ],

    'testPaymentPayoutAmountGreaterThanCapture' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payments/{id}/payout',
            'content' => [
                'amount'      => 3000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'destination' => 'ba_1000000lcustba',
                'notes'       => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_CAPTURED,
        ],
    ],

    'testPaymentPayoutPartial' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payments/{id}/payout',
            'content' => [
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'IMPS',
                'purpose'         => 'refund',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'IMPS',
                'tax'             => 94,
                'fees'            => 614,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ]
    ],

    'testCreatePaymentPayoutNotSettledLiveMode' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payments/{id}/payout',
            'content' => [
                'amount'          => 1000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'IMPS',
                'purpose'         => 'refund',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_PAYOUT_BEFORE_SETTLEMENT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_BEFORE_SETTLEMENT,
        ],
    ],

    'testPayoutAttemptSuccess' => [
        'channel'        => 'yesbank',
        'version'        => 'V3',
        'status'         => FundTransferAttemptStatus::PROCESSED,
        'remarks'        => '',
        'failure_reason' => null,
    ],

    'testPayoutEntitySuccess' => [
        'channel'        => 'yesbank',
        'status'         => PayoutStatus::PROCESSED,
        'remarks'        => '',
        'failure_reason' => null,
        'settled_on'     => null,
    ],

    'testPayoutAttemptReconSuccess' => [
        'channel'          => 'yesbank',
        'version'          => 'V3',
        'bank_status_code' => 'P',
        'status'           => FundTransferAttemptStatus::INITIATED,
    ],

    'testCreateMerchantPayoutOnDemand' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 398,
                'currency'    => 'INR',
                'tax'         => 92,
                'fees'        => 602,
                'notes'       => []
            ],
        ],
    ],
    'testCreateMerchantPayoutOnDemandOnLowBalance' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the payout operation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
        ],
    ],
    'testCreateMerchantPayoutOnDemandAmountInCrores' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 2000000000,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 1976399410,
                'currency'    => 'INR',
                'tax'         => 3600090,
                'fees'        => 23600590,
                'notes'       => []
            ],
        ],
    ],
    'testCreateMerchantPayoutOnDemandNonBankingHoursWithLessThan2Lakhs' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 20000000,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 19763410,
                'currency'    => 'INR',
                'tax'         => 36090,
                'fees'        => 236590,
                'notes'       => []
            ],
        ],
    ],
    'testCreateMerchantPayoutOnDemandHoliday' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 20000000,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 19763410,
                'currency'    => 'INR',
                'tax'         => 36090,
                'fees'        => 236590,
                'notes'       => []
            ],
        ],
    ],
    'testCreateMerchantPayoutOnDemandExceedAmountLimit' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 2000000100,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount may not be greater than 2000000000.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testCreateMerchantPayoutOnDemandExceedAmountLimitNonBankingHours'=> [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 50000100,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [

                    'description' => PublicErrorDescription::BAD_REQUEST_ES_ON_DEMAND_IMPS_AMOUNT_LIMIT_EXCEEDED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ES_ON_DEMAND_IMPS_AMOUNT_LIMIT_EXCEEDED,
        ],
    ],
    'testCreateMerchantPayoutExceedAmountLimit' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout',
            'content' => [
                'amount'   => 1000000000,
                'merchant_id'   => '10000000000000'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount may not be greater than 800000000.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testCreateMerchantPayoutOnHoldFunds' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This operation is not allowed. Please contact Razorpay support for details.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
        ],
    ],

    'testCreateMerchantPayoutOnHoldFundsOnTestMode' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 398,
                'currency'    => 'INR',
                'tax'         => 92,
                'fees'        => 602,
                'notes'       => []
            ],
        ],
    ],

    'testCreateMerchantPayoutOnMinAmount' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 105,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payout amount including fees should be greater than Re 1',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LESS_THAN_MIN_AMOUNT,
        ],
    ],
    'testOnDemandPayoutFetchFees' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/merchant/payout/demand/fees',
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                    'entity'=> 'collection',
                    'count'=> 2,
                    'items'=> [
                         [
                            'name'=> 'payout',
                            'amount'=> 510,
                            'percentage'=> null,
                            'pricing_rule'=> [
                                'percent_rate'=> 100,
                                'fixed_rate'=> 500,
                            ]
                        ],
                        [
                            'name'=> 'tax',
                            'amount'=> 92,
                            'percentage'=> 1800,
                        ]
                    ]
            ],
        ],
    ],

    'testConditionInVpaTypeFundAccount' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ]
    ],

    'testSearchPayoutByTransactionId' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByUtr' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByContactId' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByContactName' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByContactPhone' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByContactEmail' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByContactEmailWithExperimentOn' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayrollPayoutByContactEmailWithExperimentOn' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutWithMultipleSourceByContactEmailWithExperimentOn' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByContactEmailExactMatch' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByFundAccountId' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByPayoutId' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByPayoutStatus' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByPayoutStatusReason' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByPayoutContactType' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testBulkPayout' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'UPI',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'vpa',
                        'account_name'          => 'Debojyoti Chak',
                        'account_IFSC'          => '',
                        'account_number'        => '',
                        'account_vpa'           => '8861655100@ybl'
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Debojyoti Chak',
                        'email'                 => 'sampletwo@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc124'
                ]
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9999310
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'vpa',
                            'vpa'                   => [
                                'address'           => '8861655100@ybl'
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9998620
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'UPI',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc124'
                    ]
                ]
            ],
        ],
    ],

    'testBulkPayoutWithOldIfsc' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'CORP0000100',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'UPI',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'vpa',
                        'account_name'          => 'Debojyoti Chak',
                        'account_IFSC'          => '',
                        'account_number'        => '',
                        'account_vpa'           => '8861655100@ybl'
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Debojyoti Chak',
                        'email'                 => 'sampletwo@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc124'
                ]
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'UBIN0901008',
                                'bank_name'         => 'Union Bank of India',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9999310
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'vpa',
                            'vpa'                   => [
                                'address'           => '8861655100@ybl'
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9998620
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'UPI',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc124'
                    ]
                ]
            ],
        ],
    ],

    'testBulkPayoutAmazonPay' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'amazonpay',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'wallet',
                        'account_name'          => 'Vivek Karna',
                        'account_phone_number'  => '+919832478134',
                        'account_email'         => 'sample@example.com'
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
            ],
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 1,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'wallet',
                            'wallet'                => [
                                'phone'             => '+919832478134',
                                'email'             => 'sample@example.com',
                                'provider'          => 'amazonpay'
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9999310
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'amazonpay',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ]
                ]
            ],
        ],
    ],

    'testBulkPayoutAmazonPayWithoutEmail' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'amazonpay',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'wallet',
                        'account_name'          => 'Vivek Karna',
                        'account_phone_number'  => '+919832478134',
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
            ],
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 1,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'wallet',
                            'wallet'                => [
                                'phone'             => '+919832478134',
                                'provider'          => 'amazonpay'
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9999310
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'amazonpay',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ]
                ]
            ],
        ],
    ],

    'testBulkPayoutWithSameContact' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'UPI',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'vpa',
                        'account_name'          => 'Debojyoti Chak',
                        'account_IFSC'          => '',
                        'account_number'        => '',
                        'account_vpa'           => '8861655100@ybl'
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc124'
                ]
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9999310
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'vpa',
                            'vpa'                   => [
                                'address'           => '8861655100@ybl'
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9998620
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'UPI',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc124'
                    ]
                ]
            ],
        ],
    ],

    'testBulkPayoutWithSameFundAccount' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc124'
                ]
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9999310
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9998620
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc124'
                    ]
                ]
            ],
        ],
    ],

    'testBulkPayoutApproval' => [
        'request' => [
            'url' => '/payouts/bulk_approve',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                [
                    'payout_update_action' => 'A',
                    'razorpayx_account_number' => '2224440041626905',
                    'user_comment' => 'Some user comment',
                    'payout' => [
                        'amount' => '10000',
                        'currency' => 'INR',
                        'mode' => 'IMPS',
                        'purpose' => 'refund',
                        'narration' => '123',
                        'status' => 'pending',
                    ],
                    'fund' => [

                    ],
                    'contact' => [
                        'name' => 'Vivek Karna',
                        'reference_id' => ''
                    ],
                    'idempotency_key' => 'batch_abc123'
                ]
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity' => 'payout',
                        'amount' => 10000,
                        'currency' => 'INR',
                        'status'   => 'processing',
                        'idempotency_key' => 'batch_abc123'
                    ]
                ]
            ],
        ],
    ],

    'testBulkPayoutRejection' => [
        'request' => [
            'url' => '/payouts/bulk_approve',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                [
                    'payout_update_action' => 'R',
                    'razorpayx_account_number' => '2224440041626905',
                    'user_comment' => 'Some user comment',
                    'payout' => [
                        'amount' => '10000',
                        'currency' => 'INR',
                        'mode' => 'IMPS',
                        'purpose' => 'refund',
                        'narration' => '123',
                        'status' => 'pending',
                    ],
                    'fund' => [

                    ],
                    'contact' => [
                        'name' => 'Vivek Karna',
                        'reference_id' => ''
                    ],
                    'idempotency_key' => 'batch_abc123'
                ]
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity' => 'payout',
                        'amount' => 10000,
                        'currency' => 'INR',
                        'status'   => 'rejected',
                        'idempotency_key' => 'batch_abc123'
                    ]
                ]
            ],
        ],
    ],


    'testCreatePayoutForRblDirectAccount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'IMPS',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutForRblDirectWithSharedRules' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'status'          => 'processing',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutToCardFundAccountUsingUpi' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 100,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000002fa',
                'mode'                  => 'UPI',
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000002fa',
                'mode'            => 'UPI',
                'purpose'         => 'refund',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [],
            ],
        ],
    ],

    'testFetchMultiplePayoutsWithBankingProductParameter' => [
        'request' => [
            'url'    => '/payouts',
            'method' => 'get',
            'content' => [
                'product' => 'banking',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity'          => 'payout',
                        'amount'          => 2000000,
                        'currency'        => 'INR',
                        'fund_account_id' => 'fa_100000000000fa',
                        'narration'       => 'Batman',
                        'purpose'         => 'refund',
                        'status'          => 'processing',
                        'tax'             => 162,
                        'fees'            => 1062,
                        'notes'           => [
                            'abc' => 'xyz',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultiplePayoutsWithQueuedReasonParameter' => [
        'request' => [
            'url'    => '/payouts',
            'method' => 'get',
            'content' => [
                'queued_reason' => 'low_balance',
                'product'       => 'banking',
                'status'        => 'queued',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity'          => 'payout',
                        'amount'          => 10000001,
                        'currency'        => 'INR',
                        'fund_account_id' => 'fa_100000000000fa',
                        'narration'       => 'Test Merchant Fund Transfer',
                        'purpose'         => 'refund',
                        'status'          => 'queued',
                        'tax'             => 0,
                        'fees'            => 0,
                        'notes'           => [

                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultiplePayoutsWithBankingProductParameterWithViewOnlyRole' => [
        'request' => [
            'url'    => '/payouts',
            'method' => 'get',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'product' => 'banking',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity'          => 'payout',
                        'amount'          => 2000000,
                        'currency'        => 'INR',
                        'fund_account_id' => 'fa_100000000000fa',
                        'narration'       => 'Batman',
                        'purpose'         => 'refund',
                        'status'          => 'processing',
                        'tax'             => 162,
                        'fees'            => 1062,
                        'notes'           => [
                            'abc' => 'xyz',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultiplePayoutsWithPrimaryProductParameter' => [
        'request' => [
            'url'    => '/payouts',
            'method' => 'get',
            'content' => [
                'product' => 'primary',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The selected product is invalid.',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'                 => Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testFetchMultipleWithHasMoreOnPrivate' => [
        'request' => [
            'url'    => '/payouts',
            'method' => 'get',
            'content' => [
                'account_number'    => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity'          => 'payout',
                        'amount'          => 2000000,
                        'currency'        => 'INR',
                        'fund_account_id' => 'fa_100000000000fa',
                        'narration'       => 'Batman',
                        'purpose'         => 'refund',
                        'status'          => 'processing',
                        'tax'             => 162,
                        'fees'            => 1062,
                        'notes'           => [
                            'abc' => 'xyz',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleWithHasMoreFalseWithNoResults' => [
        'request' => [
            'url'    => '/payouts',
            'method' => 'get',
            'content' => [
                'product' => 'banking',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'has_more'  => false,
                'count' => 0,
                'items' => [],
            ],
        ],
    ],

    'testFetchMultipleWithHasMoreWithSkipAndCount' => [
        'request' => [
            'url'    => '/payouts',
            'method' => 'get',
            'content' => [
                'product' => 'banking',
                'skip' => 1,
                'count' => 2,
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'has_more' => true,
                'count' => 2,
                'items' => [
                    [
                        'entity'          => 'payout',
                        'amount'          => 2000000,
                        'currency'        => 'INR',
                        'fund_account_id' => 'fa_100000000000fa',
                        'narration'       => 'Batman',
                        'purpose'         => 'refund',
                        'status'          => 'processing',
                        'tax'             => 162,
                        'fees'            => 1062,
                        'notes'           => [
                            'abc' => 'xyz',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleWithHasMoreWithOnlyCount' => [
        'request' => [
            'url'    => '/payouts',
            'method' => 'get',
            'content' => [
                'product' => 'banking',
                'count' => 2,
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'has_more' => true,
                'count' => 2,
                'items' => [
                    [
                        'entity'          => 'payout',
                        'amount'          => 2000000,
                        'currency'        => 'INR',
                        'fund_account_id' => 'fa_100000000000fa',
                        'narration'       => 'Batman',
                        'purpose'         => 'refund',
                        'status'          => 'processing',
                        'tax'             => 162,
                        'fees'            => 1062,
                        'notes'           => [
                            'abc' => 'xyz',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleWithHasMoreWithOnlyMaxCount' => [
        'request' => [
            'url'    => '/payouts',
            'method' => 'get',
            'content' => [
                'product' => 'banking',
                'count' => 3,
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'has_more' => false,
                'count' => 3,
                'items' => [
                    [
                        'entity'          => 'payout',
                        'amount'          => 2000000,
                        'currency'        => 'INR',
                        'fund_account_id' => 'fa_100000000000fa',
                        'narration'       => 'Batman',
                        'purpose'         => 'refund',
                        'status'          => 'processing',
                        'tax'             => 162,
                        'fees'            => 1062,
                        'notes'           => [
                            'abc' => 'xyz',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleWithHasMoreWithExactSkipAndCount' => [
        'request' => [
            'url'    => '/payouts',
            'method' => 'get',
            'content' => [
                'product' => 'banking',
                'skip' => 2,
                'count' => 2,
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'has_more' => false,
                'count' => 2,
                'items' => [
                    [
                        'entity'          => 'payout',
                        'amount'          => 2000000,
                        'currency'        => 'INR',
                        'fund_account_id' => 'fa_100000000000fa',
                        'narration'       => 'Batman',
                        'purpose'         => 'refund',
                        'status'          => 'processing',
                        'tax'             => 162,
                        'fees'            => 1062,
                        'notes'           => [
                            'abc' => 'xyz',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleWithHasMoreWithNoCountAndSkip' => [
        'request' => [
            'url'    => '/payouts',
            'method' => 'get',
            'content' => [
                'product' => 'banking',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'has_more' => false,
                'count' => 3,
                'items' => [
                    [
                        'entity'          => 'payout',
                        'amount'          => 2000000,
                        'currency'        => 'INR',
                        'fund_account_id' => 'fa_100000000000fa',
                        'narration'       => 'Batman',
                        'purpose'         => 'refund',
                        'status'          => 'processing',
                        'tax'             => 162,
                        'fees'            => 1062,
                        'notes'           => [
                            'abc' => 'xyz',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testBulkPayoutWithSameIdempotencyandBatchId' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'UPI',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'vpa',
                        'account_name'          => 'Debojyoti Chak',
                        'account_IFSC'          => '',
                        'account_number'        => '',
                        'account_vpa'           => '8861655100@ybl'
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Debojyoti Chak',
                        'email'                 => 'sampletwo@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ]
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9999310
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ]
                ]
            ],
        ],
    ],

    'testRxPayoutForSlaExpiry' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 200000,
                'currency'        => 'INR',
                'purpose'         => 'payout',
                'narration'       => 'King',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 200000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'King',
                'purpose'         => 'payout',
                'mode'            => 'IMPS',
                'status'          => 'processing',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testEmailNotificationForPayoutPendingOnApproval' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/pending-payouts-approval-email',
        ],
        'response' => [
            'content' => [
                    'Queued email count' => 2
            ],
        ],
    ],

    'testEmailNotificationForPendingPayoutLinksForApproval' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/pending-payouts-approval-email',
        ],
        'response' => [
            'content' => [
                'Queued email count' => 7
            ],
        ],
    ],

    'testReminderNotificationForPayoutPendingOnApproval' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/pending-payouts-approval-reminder',
        ],
        'response' => [
            'content' => [
                'reminderEventCount' => 2
            ],
        ],
    ],

    'testReminderNotificationForPayoutPendingOnApprovalWithClevertapMigrationExpEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/pending-payouts-approval-reminder',
        ],
        'response' => [
            'content' => [
                'reminderEventCount' => 2
            ],
        ],
    ],

    'testDashboardSummary' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/_meta/summary',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testDashboardSummaryForPayoutsOnNonBankingBalance' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/_meta/summary',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'bacc_1000000lcustba' => [
                    'queued' =>  [
                        'balance'       => 10000000,
                        'count'         => 0,
                        'total_amount'  => 0,
                        'total_fees'    => 0,
                    ],
                    'pending' => [
                        'count'         => 1,
                        'total_amount'  => 10000,
                    ]
                ],
            ],
        ],
    ],

    'createCustomerWalletPayout' => [
        'request'  => [
            'url'     => '/customers/cust_100000customer/payouts',
            'method'  => 'post',
            'content' => [
                'amount'          => 800,
                'purpose'         => 'refund',
                'fund_account_id' => 'fa_100000000000fa',
                'currency'        => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'customer_id'     => 'cust_100000customer',
                'fund_account_id' => 'fa_100000000000fa',
                'currency'        => 'INR',
                'amount'          => 800,
                'status'          => 'processing',
            ]
        ],

    ],

    'testCreatePayoutForVpaFundAccountId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000003fa',
                'mode'            => 'UPI',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'UPI is not supported',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testCreatePayoutForIciciToBankAccountViaNEFT' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'NEFT is not supported',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testCreatePayoutToBankAccountViaIMPS' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateQueuedPayoutWithModeSet' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 10000001,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'IMPS',
                'queue_if_low_balance'  => true,
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000001,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'utr'             => null,
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [],
            ],
        ],
    ],

    'testCreateQueuedPayoutWithModeSetWithCredits' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 10000001,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'IMPS',
                'queue_if_low_balance'  => true,
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000001,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'utr'             => null,
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [],
            ],
        ],
    ],

    'testCreateQueuedPayoutWithModeSetWithCreditsWithNewCreditsFlow'  => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 10000001,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'IMPS',
                'queue_if_low_balance'  => true,
            ]
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testCreatePayoutForCitiToCardViaNEFTWithMultipleCredits' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 1000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000002fa',
                'mode'                  => 'NEFT'
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 1000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000002fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'utr'             => null,
                'mode'            => 'NEFT',
                'tax'             => 0,
                'fees'            => 500,
                'notes'           => [],
            ],
        ],
    ],

    'testCreatePayoutForCitiToCardViaNEFTWithMultipleCreditsWithNewCreditsFlow'  => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 1000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000002fa',
                'mode'                  => 'NEFT'
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 1000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000002fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'utr'             => null,
                'mode'            => 'NEFT',
                'tax'             => 0,
                'fees'            => 500,
                'notes'           => [],
            ],
        ],
    ],

    'testCreatePayoutForCitiToCardViaNEFTRewardFeeCredits' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 1000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000002fa',
                'mode'                  => 'NEFT'
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 1000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000002fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'utr'             => null,
                'mode'            => 'NEFT',
                'notes'           => [],
            ],
        ],
    ],

    'testCreatePayoutForCitiToCardViaNEFTRewardFeeCreditsWithNewCreditsFlow'  => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 1000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000002fa',
                'mode'                  => 'NEFT'
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 1000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000002fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'utr'             => null,
                'mode'            => 'NEFT',
                'notes'           => [],
            ],
        ],
    ],

    'testCreateQueuedPayoutUnsupportedModeForCitiIcici' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 3000000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000003fa',
                'mode'                  => 'UPI',
                'queue_if_low_balance'  => 1,
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'UPI is not supported',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testCreatePayoutForIciciToCardViaNEFT' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 1000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000002fa',
                'mode'                  => 'NEFT'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'NEFT is not supported',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testCreatePayoutWithModeNotSet' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The mode field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateRblPayoutWithModeNotSet' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626906',
                'amount'          => 2000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The mode field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutForVpaFundAccountWithUnsupportedMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 200000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000003fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'NEFT is not a valid mode for account type vpa',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutWithInvalidMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 20000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'MehulIsA10xDeveloper',
                'fund_account_id' => 'fa_100000000003fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYOUT_INVALID_MODE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_INVALID_MODE,
        ],
    ],

    'testCreateRblPayoutToCard' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'        => '2224440041626906',
                'amount'                => 2000000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'narration'             => 'Batman',
                'mode'                  => 'IMPS',
                'fund_account'   => [
                    'account_type' => 'card',
                    'card' => [
                        'name'      => 'Prashanth YV',
                        'number'    => '04111111111111111',
                        'ifsc'      => 'KKBK0000430',
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'notes'                 => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card' => [
                        'last4'     =>  '1111',
                        'network'   =>  'Visa',
                        'type'      =>  'credit',
                        'issuer'    =>  'HDFC',
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'contact'      => '9999999999',
                        'email'        => 'prashanth@razorpay.com',
                        'type'         => 'employee',
                        'reference_id' => null,
                        'batch_id'     => null,
                        'active'       => true,
                        'notes'        => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'mode'            => 'IMPS',
                'purpose'         => 'refund',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateRblPayoutToUpiWithoutFeatureEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'        => '2224440041626905',
                'amount'                => 200,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'narration'             => 'Batman',
                'mode'                  => 'UPI',
                'fund_account_id'       => 'fa_100000000002fa',
                'notes'                 => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'RBL does not support UPI payouts to VPA',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testCreateRblPayoutToUpiWithFeatureEnabledAndFreePayoutsAvailable' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'        => '2224440041626905',
                'amount'                => 200,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'narration'             => 'Batman',
                'mode'                  => 'UPI',
                'fund_account_id'       => 'fa_100000000002fa',
                'notes'                 => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 200,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000002fa',
                'mode'            => 'UPI',
                'purpose'         => 'refund',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateRblPayoutToUpiWithFeatureEnabledAndFreePayoutsUnavailable' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'        => '2224440041626905',
                'amount'                => 200,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'narration'             => 'Batman',
                'mode'                  => 'UPI',
                'fund_account_id'       => 'fa_100000000002fa',
                'notes'                 => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 200,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000002fa',
                'mode'            => 'UPI',
                'purpose'         => 'refund',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateRblPayoutToCardViaUpi' => [
        'request'  => [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' => [
                'fund_account' => [
                    "account_type" => "card",
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                    "card"         => [
                        'number' => '378282246310005',
                        'name'   => 'Tester Test',
                    ]
                ],
                'mode'            => 'UPI',
                'purpose'         => 'refund',
                'account_number'  => '2224440041626905',
                'amount'          => 10000,
                'currency'        => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'payout',
                'amount'   => 10000,
                'currency' => 'INR',
                'purpose'  => 'refund',
                'mode'     => 'UPI',
            ],
        ],
    ],

    'testCreateMerchantPayoutOnDemandWithFtsRampFailure' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 398,
                'currency'    => 'INR',
                'tax'         => 92,
                'fees'        => 602,
                'notes'       => []
            ],
        ],
    ],

    'testCreateMerchantPayoutOnDemandWithFtsRampSuccess' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testFiringOfWebhookOnUpdationOfUtrEventData' => [
        'entity'   => 'event',
        'event'    => 'payout.updated',
        'contains' => [
            'payout',
        ],
        'payload' => [
            'payout' => [
                'entity' => [
                    'entity'     => 'payout',
                    'utr'        => '933815233814',
                ],
            ],
        ],
    ],

    'testFiringOfWebhookOnUpdateOfPayoutEventData' => [
        'entity'   => 'event',
        'event'    => 'payout.updated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'processing',
                ],
            ],
        ],
    ],

    'testFiringOfWebhookOnProcessPayoutEventData' => [
        'entity'   => 'event',
        'event'    => 'payout.processed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'processed',
                ],
            ],
        ],
    ],

    'testFiringOfWebhookOnQueuedPayoutEventData' => [
        'entity'   => 'event',
        'event'    => 'payout.queued',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'queued',
                ],
            ],
        ],
    ],

    'testFiringOfWebhookOnInitiatedPayoutEventData' => [
        'entity'   => 'event',
        'event'    => 'payout.initiated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'processing',
                ],
            ],
        ],
    ],

    'testFiringOfWebhookOnCreatedTransactionPayoutEventData' => [
        'entity'   => 'event',
        'event'    => 'transaction.created',
        'contains' => [
            'transaction',
        ],
        'payload'  => [
            'transaction' => [
                'entity' => [
                    'entity' => 'transaction',
                    'source' => [
                        'entity' => 'payout',
                        'status' => 'processing',
                    ]
                ],
            ],
        ],
    ],

    'testGetPayoutMetaWorkflowProxyAuth' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method'  => 'GET',
            'url'     => '/payouts/_meta/workflows',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testGetPayoutMetaWorkflowProxyAuthByType' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                "config_type" => "payout-approval"
            ],
            'method'  => 'GET',
            'url'     => '/payouts/_meta/wf_config',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testGetPayoutMetaWorkflowAdminAuthByType' => [
        'request'  => [
            'content' => [
                "config_type" => "payout-approval"
            ],
            'method'  => 'GET',
            'url'     => '/admin-workflows/payouts/wf_config',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testGetPayoutMetaWorkflowPrivateAuth' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/_meta/workflows',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testCreatePayoutWithWrongFundAccountId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_101200340560fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testCreatePayoutIMPSMoreThanMaxAmount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 60000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Given method / mode cannot be used for the payout amount specified',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_AMOUNT_MODE_MISMATCH,
        ],
    ],

    'testCreatePayoutAmazonPayMoreThanMaxAmount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 30000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'amazonpay',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Given method / mode cannot be used for the payout amount specified',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_AMOUNT_MODE_MISMATCH,
        ],
    ],

    'testCreatePayoutUPIMoreThanMaxAmount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 30000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'UPI',
                'fund_account_id' => 'fa_100000000003fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Given method / mode cannot be used for the payout amount specified',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_AMOUNT_MODE_MISMATCH,
        ],
    ],

    'testCreatePayoutRTGSLessThanMinAmount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 3000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'RTGS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Given method / mode cannot be used for the payout amount specified',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_AMOUNT_MODE_MISMATCH,
        ],
    ],

    'testSearchPayoutByMode' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByReferenceId' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testCreatePayoutInvalidCurrency' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 30000,
                'currency'        => 'USD',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected currency is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetAllPayoutPurposes' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/payouts/purposes',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 6,
                'items'     =>  [
                    [
                        'purpose'       =>  'refund',
                        'purpose_type'  =>  'refund',
                    ],
                    [
                        'purpose'       => 'cashback',
                        'purpose_type'  => 'refund',
                    ],
                    [
                        'purpose'       => 'payout',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'salary',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'utility bill',
                        'purpose_type'  =>  'settlement',
                    ],
                    [
                        'purpose'       => 'vendor bill',
                        'purpose_type'  =>  'settlement',
                    ]
                ],
            ],
        ],
    ],

    'testGetAllCustomPayoutPurposesInternalRoute' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/payouts/purposes/{merchant_id}',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 4,
                'items'     => [
                    [
                        'purpose'       => 'Give Mehul A Bonus',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'   => 'Bulk Custom Purpose',
                        'purpose_type'  => 'settlement'
                    ],
                    [
                        'purpose'   => 'Is this a purpose',
                        'purpose_type'  => 'settlement'
                    ],
                    [
                        'purpose'   => 'Zomato',
                        'purpose_type'  => 'settlement'
                    ]
                ],
            ],
        ],
    ],

    'testAddCustomPayoutPurpose' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts/purposes',
            'content' => [
                'purpose'       => 'Give Mehul A Bonus',
                'purpose_type'  => 'settlement'
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 7,
                'items'     =>  [
                    [
                        'purpose'       => 'Give Mehul A Bonus',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'refund',
                        'purpose_type'  => 'refund',
                    ],
                    [
                        'purpose'       => 'cashback',
                        'purpose_type'  => 'refund',
                    ],
                    [
                        'purpose'       => 'payout',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'salary',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'utility bill',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'vendor bill',
                        'purpose_type'  => 'settlement',
                    ],
                ],
            ],
        ],
    ],

    'testAddBulkCustomPayoutPurpose' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts/purposes/{merchant_id}',
            'content' => [
                [
                    'purpose'   => 'Give Mehul A Bonus',
                    'purpose_type'  => 'settlement'
                ],
                [
                    'purpose'   => 'Bulk Custom Purpose',
                    'purpose_type'  => 'settlement'
                ],
                [
                    'purpose'   => 'Is this a purpose',
                    'purpose_type'  => 'settlement'
                ],
                [
                    'purpose'   => ' Zomato ',
                    'purpose_type'  => 'settlement'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 10,
                'items'     =>  [
                    [
                        'purpose'   => 'Give Mehul A Bonus',
                        'purpose_type'  => 'settlement'
                    ],
                    [
                        'purpose'   => 'Bulk Custom Purpose',
                        'purpose_type'  => 'settlement'
                    ],
                    [
                        'purpose'   => 'Is this a purpose',
                        'purpose_type'  => 'settlement'
                    ],
                    [
                        'purpose'   => 'Zomato',
                        'purpose_type'  => 'settlement'
                    ],
                    [
                        'purpose'       => 'refund',
                        'purpose_type'  => 'refund',
                    ],
                    [
                        'purpose'       => 'cashback',
                        'purpose_type'  => 'refund',
                    ],
                    [
                        'purpose'       => 'payout',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'salary',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'utility bill',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'vendor bill',
                        'purpose_type'  => 'settlement',
                    ],
                ],
            ],
        ],
    ],

    'testAddBulkCustomPayoutPurposeVendorPaymentsInternalAuth' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts/purposes/{merchant_id}',
            'content' => [
                [
                    'purpose'   => 'Give Sumit A Bonus',
                    'purpose_type'  => 'settlement'
                ],
                [
                    'purpose'   => 'Advertising And Marketing',
                    'purpose_type'  => 'settlement'
                ],
                [
                    'purpose'   => 'Education and Training Expense',
                    'purpose_type'  => 'settlement'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 9,
                'items'     =>  [
                    [
                        'purpose'   => 'Give Sumit A Bonus',
                        'purpose_type'  => 'settlement'
                    ],
                    [
                        'purpose'   => 'Advertising And Marketing',
                        'purpose_type'  => 'settlement'
                    ],
                    [
                        'purpose'   => 'Education and Training Expense',
                        'purpose_type'  => 'settlement'
                    ],
                    [
                        'purpose'       => 'refund',
                        'purpose_type'  => 'refund',
                    ],
                    [
                        'purpose'       => 'cashback',
                        'purpose_type'  => 'refund',
                    ],
                    [
                        'purpose'       => 'payout',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'salary',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'utility bill',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'vendor bill',
                        'purpose_type'  => 'settlement',
                    ],
                ],
            ],
        ],
    ],

    'testAddCustomPayoutPurposeOfNumericType' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts/purposes',
            'content' => [
                'purpose'       => 'Give Mehul A Bonus',
                'purpose_type'  => 'settlement'
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'items'     =>  [

                ],
            ],
        ],
    ],

    'testAddCustomPayoutPurposeWithWrongPurposeType' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts/purposes',
            'content' => [
                'purpose'       => 'Give Mehul A Bonus',
                'purpose_type'  => 'penny_tesing'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected purpose type is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddCustomPayoutPurposeThatAlreadyExists' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts/purposes',
            'content' => [
                'purpose'       => 'Give Mehul A Bonus',
                'purpose_type'  => 'settlement'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Purpose \'Give Mehul A Bonus\' is already defined and cannot be added.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddBulkCustomPayoutPurposeThatAlreadyExists' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts/purposes/{merchant_id}',
            'content' => [
                [
                    'purpose'       => 'Give Mehul A Bonus',
                    'purpose_type'  => 'settlement'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 10,
                'items'     =>  [
                    [
                        'purpose'   => 'Give Mehul A Bonus',
                        'purpose_type'  => 'settlement'
                    ],
                    [
                        'purpose'   => 'Bulk Custom Purpose',
                        'purpose_type'  => 'settlement'
                    ],
                    [
                        'purpose'   => 'Is this a purpose',
                        'purpose_type'  => 'settlement'
                    ],
                    [
                        'purpose'   => 'Zomato',
                        'purpose_type'  => 'settlement'
                    ],
                    [
                        'purpose'       => 'refund',
                        'purpose_type'  => 'refund',
                    ],
                    [
                        'purpose'       => 'cashback',
                        'purpose_type'  => 'refund',
                    ],
                    [
                        'purpose'       => 'payout',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'salary',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'utility bill',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'vendor bill',
                        'purpose_type'  => 'settlement',
                    ],
                ],
            ],

        ],
    ],

    'testAdd201CustomPayoutPurposes' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts/purposes',
            'content' => [
                'purpose'       => 'Give Mehul A Bonus',
                'purpose_type'  => 'settlement'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You have reached the maximum limit (200) of custom payout purposes that can be created.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAdd301BulkCustomPayoutPurposes' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts/purposes/{merchant_id}',
            'content' => [
                [
                    'purpose'       => 'Give Mehul A Bonus',
                    'purpose_type'  => 'settlement'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You have reached the maximum limit (300) of custom payout purposes that can be created.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testAddCustomPurposeRZPFees' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/purposes',
            'content' => [
                'purpose'       => 'rzp_fees',
                'purpose_type'  => 'settlement',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Purpose \'rzp_fees\' is an internal purpose used by Razorpay and cannot be added.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCancelRZPFeesPayout' => [
        'request'  => [
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FEE_RECOVERY_PAYOUT_CANCEL_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FEE_RECOVERY_PAYOUT_CANCEL_NOT_PERMITTED,
        ],
    ],

    'testCreatePayoutToRzpFeesContact' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Creating a payout to an internal Razorpay Fund Account is not permitted',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_TO_INTERNAL_FUND_ACCOUNT_NOT_PERMITTED,
        ],
    ],

    'testCreatePayoutToRzpTaxContactShouldFail' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Creating a payout to an internal Razorpay Fund Account is not permitted',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_TO_INTERNAL_FUND_ACCOUNT_NOT_PERMITTED,
        ],
    ],

    'testRZPFeesQueuedPayoutPriority' => [
        'request'  => [
            'method'    => 'POST',
            'url'       => '/payouts/queued/process/new',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testRZPFeesQueuedPayoutNotEnoughBalance' => [
        'request'  => [
            'method'    => 'POST',
            'url'       => '/payouts/queued/process/new',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testOnHoldPayoutCreateAndAutoCancel' => [
        'request'  => [
            'method'    => 'POST',
            'url'       => '/payouts/onhold/process',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testOnHoldPayoutCreateAndProcess' => [
        'request'  => [
            'method'    => 'POST',
            'url'       => '/payouts/onhold/process',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testOnHoldPayoutsProcessing' => [
        'request'  => [
            'method'    => 'POST',
            'url'       => '/payouts/onhold/process',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreatingPendingPayoutsForRblWithUnsupportedModeChannelDestinationTypeCombo' => [
        'request' => [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' => [
                'fund_account_id' => 'fa_D6XkDQaM3whg5v',
                'amount'          => '100',
                'mode'            => 'UPI',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'RBL does not support UPI payouts to VPA',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testCreatingPendingPayoutsForRblWithSupportedModeChannelDestinationTypeCombo' => [
        'request' => [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' => [
                'fund_account_id' => 'fa_100000000000fa',
                'amount'          => '1000000',
                'mode'            => 'IMPS',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account_id' => 'fa_100000000000fa',
                'amount'          => 1000000,
                'currency'        => 'INR',
                'status'          => 'pending',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
            ],
        ],
    ],

    'testWorkflowTriggerForBankingRequest' => [
        'request' => [
            'url'    => '/payouts_with_otp',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 500000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'token'           => 'BUIj3m2Nx2VvVj',
                'otp'             => '0007',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 500000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'pending',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testDefaultWorkflowBehaviourForAPIRequest' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 500000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 500000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'pending',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testSkipWorkflowForAPIRequest' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 500000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 500000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testCreatePayoutWithFetchAndUpdateBalanceFromGatewayAndBalanceLessThanPayoutAmount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true,
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account_id' => 'fa_100000000000fa',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'notes'           => [
                    'abc' => 'xyz'
                ],
                'status'          => 'queued',
                'purpose'         => 'refund',
                'utr'             => null,
                'mode'            => 'IMPS',
                'reference_id'    => null,
                'narration'       => 'Batman',
                'batch_id'        => null,
                'failure_reason'  => NULL,
            ],
        ],
    ],
    'testCreatePayoutWithFetchAndUpdateBalanceFromGatewayAndBalanceMoreThanPayoutAmount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true,
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account_id' => 'fa_100000000000fa',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'notes'           => [
                    'abc' => 'xyz'
                ],
                'status'          => 'processing',
                'purpose'         => 'refund',
                'utr'             => null,
                'mode'            => 'IMPS',
                'reference_id'    => null,
                'narration'       => 'Batman',
                'batch_id'        => null,
                'failure_reason'  => NULL,
            ],
        ],
    ],

    'testDispatchGatewayBalanceUpdateJobForInvalidDirectChannel' => [
        'request' => [
            'url'     => '/banking_accounts/gateway/hdfc/balance',
            'method'  => 'put',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid channel: hdfc',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFiringOfWebhookOnRejectionOfPayoutEventData' => [
        'entity'   => 'event',
        'event'    => 'payout.rejected',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'rejected',
                ],
            ],
        ],
    ],

    'testFiringOfWebhookOnRejectionOfPayoutWithCommentEventData' => [
        'entity'   => 'event',
        'event'    => 'payout.rejected',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'            => 'payout',
                    'status'            => 'rejected',
                    'failure_reason'    => 'Rejecting',
                ],
            ],
        ],
    ],

    'testFiringOfWebhookOnRejectionOfPayoutWithoutCommentInWebhookEventData' => [
        'entity'   => 'event',
        'event'    => 'payout.rejected',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'            => 'payout',
                    'status'            => 'rejected',
                    'failure_reason'    =>  null,
                ],
            ],
        ],
    ],

    'testPayoutStatusUpdate' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts/{id}/status',
            'content' => [
                'status' => 'processed'
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testPayoutInvalidStatusUpdate' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts/{id}/status',
            'content' => [
                'status' => 'processed'
            ],
        ],
        'response'  => [
            'content'     => [
                'error'         => [
                    'code'              => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => 'Status change not permitted',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                     => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'       => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'message'                   => 'Status change not permitted',
        ],
    ],

    'testPayoutStatusUpdateOnPrivateAuth' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts/{id}/status',
            'content' => [
                'status' => 'processed'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testPayoutStatusUpdateOnLiveMode' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts/{id}/status',
            'content' => [
                'status' => 'processed'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYOUT_STATUS_UPDATE_ALLOWED_ONLY_IN_TEST_MODE,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                     => RZP\Exception\BadRequestException::class,
            'internal_error_code'       => ErrorCode::BAD_REQUEST_PAYOUT_STATUS_UPDATE_ALLOWED_ONLY_IN_TEST_MODE,
            'message'                   => PublicErrorDescription::BAD_REQUEST_PAYOUT_STATUS_UPDATE_ALLOWED_ONLY_IN_TEST_MODE,
        ],
    ],

    'testFiringOfWebhookOnCreationOfPendingPayoutEventData' => [
        'entity'   => 'event',
        'event'    => 'payout.pending',
        'contains' => [
            'payout',
        ],
        'payload' => [
            'payout' => [
                'entity' => [
                    'entity'     => 'payout',
                    'status'     => 'pending',
                ],
            ],
        ],
    ],

    'testApprovePayoutWithNonBankingRoleInWorkflow' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_DOES_NOT_BELONG_TO_MERCHANT,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                     => RZP\Exception\BadRequestException::class,
            'internal_error_code'       => ErrorCode::BAD_REQUEST_USER_DOES_NOT_BELONG_TO_MERCHANT,
            'message'                   => PublicErrorDescription::BAD_REQUEST_USER_DOES_NOT_BELONG_TO_MERCHANT,
        ],
    ],

    'testPayoutToAmexCardWithNullIssuerSupportedMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account' => [
                    "account_type" => "card",
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                    "card"         => [
                        "name"         => "Prashanth YV",
                        "number"       => "340169570990137",
                        "cvv"          => "2126",
                        "expiry_month" => 10,
                        "expiry_year"  => 29,
                    ]
                ],
                'amount'          => 100,
                'mode'            => 'NEFT',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card' => [
                        'last4'     =>  '0137',
                        'network'   =>  'American Express',
                        'type'      =>  'credit',
                        'issuer'    =>  null,
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'contact'      => '9999999999',
                        'email'        => 'prashanth@razorpay.com',
                        'type'         => 'employee',
                        'reference_id' => null,
                        'batch_id'     => null,
                        'active'       => true,
                        'notes'        => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'amount'          => 100,
                'currency'        => 'INR',
                'status'          => 'processing',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
            ],
        ],
    ],

    'testPayoutToAmexCardWithNullIssuerWithUPIMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account' => [
                    "account_type" => "card",
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                    "card"         => [
                        "name"         => "Prashanth YV",
                        "number"       => "340169570990137",
                        "cvv"          => "2126",
                        "expiry_month" => 10,
                        "expiry_year"  => 29,
                    ]
                ],
                'amount'          => 100,
                'mode'            => 'UPI',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card' => [
                        'last4'     =>  '0137',
                        'network'   =>  'American Express',
                        'type'      =>  'credit',
                        'issuer'    =>  null,
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'contact'      => '9999999999',
                        'email'        => 'prashanth@razorpay.com',
                        'type'         => 'employee',
                        'reference_id' => null,
                        'batch_id'     => null,
                        'active'       => true,
                        'notes'        => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'amount'          => 100,
                'currency'        => 'INR',
                'status'          => 'processing',
                'purpose'         => 'refund',
                'mode'            => 'UPI',
            ],
        ],
    ],

    'testPayoutToAmexCardWithSupportedIssuerSupportedMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account' => [
                    "account_type" => "card",
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                    "card"         => [
                        "name"         => "Prashanth YV",
                        "number"       => "340169570990137",
                        "cvv"          => "2126",
                        "expiry_month" => 10,
                        "expiry_year"  => 29,
                    ]
                ],
                'amount'          => 100,
                'mode'            => 'NEFT',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card' => [
                        'last4'     =>  '0137',
                        'network'   =>  'American Express',
                        'type'      =>  'credit',
                        'issuer'    =>  'SCBL',
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'contact'      => '9999999999',
                        'email'        => 'prashanth@razorpay.com',
                        'type'         => 'employee',
                        'reference_id' => null,
                        'batch_id'     => null,
                        'active'       => true,
                        'notes'        => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'amount'          => 100,
                'currency'        => 'INR',
                'status'          => 'processing',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
            ],
        ],
    ],

    'testPayoutToAmexCardWithSupportedIssuerWithUPIMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account' => [
                    "account_type" => "card",
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                    "card"         => [
                        "name"         => "Prashanth YV",
                        "number"       => "340169570990137",
                        "cvv"          => "2126",
                        "expiry_month" => 10,
                        "expiry_year"  => 29,
                    ]
                ],
                'amount'          => 100,
                'mode'            => 'UPI',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card' => [
                        'last4'     =>  '0137',
                        'network'   =>  'American Express',
                        'type'      =>  'credit',
                        'issuer'    =>  'SCBL',
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'contact'      => '9999999999',
                        'email'        => 'prashanth@razorpay.com',
                        'type'         => 'employee',
                        'reference_id' => null,
                        'batch_id'     => null,
                        'active'       => true,
                        'notes'        => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'amount'          => 100,
                'currency'        => 'INR',
                'status'          => 'processing',
                'purpose'         => 'refund',
                'mode'            => 'UPI',
            ],
        ],
    ],


    'testPayoutToAmexCardWithNotSupportedIssuer' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                "account_type" => "card",
                "contact_id"   => "cont_1000001contact",
                "card"         => [
                    "name"         => "Prashanth YV",
                    "number"       => "340169570990137",
                    "cvv"          => "2126",
                    "expiry_month" => 10,
                    "expiry_year"  => 29,
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Card not supported for fund account creation',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
        ],
    ],

    'testBulkRejectPayoutWithAdmin' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/payouts/cancel',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],

    'testBulkRejectPayoutWithAdminWithFailure' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/payouts/cancel',
            'content' => [
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payout is not in pending state',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_INVALID_STATE,
        ],
    ],

    'testDashboardSummaryForNonWorkflowRoles' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/_meta/summary',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateMerchantPayoutOnDemandDoesNotTriggerWorkflow' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 398,
                'currency'    => 'INR',
                'tax'         => 92,
                'fees'        => 602,
                'notes'       => [],
                'workflow_history' => [],
                'status'      => 'processing'
            ],
        ],
    ],

    'testCreateMerchantPayoutDoesntTriggerWorkflow' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout',
            'content' => [
                'amount'         => 1000,
                'merchant_id'    => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 1000,
                'currency'    => 'INR',
                'tax'         => 92,
                'fees'        => 602,
                'notes'       => [],
                'workflow_history' => [],
                'status'      => 'processing'
            ],
        ],
    ],

    'testFiringOfWebhooksAndEmailOnPayoutReversal' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => 'EgmjebcvYkSg3v',
                'source_type'         => 'payout',
                'status'              => 'FAILED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testFiringOfWebhooksAndEmailOnPayoutReversalTransactionCreatedEventData' => [
        'entity'   => 'event',
        'event'    => 'transaction.created',
        'contains' => [
            'transaction',
        ],
        'payload' => [
            'transaction' => [
                'entity' => [
                    'entity' => 'transaction',
                    'source' => [
                        'entity' => 'reversal',
                    ],
                ],
            ],
        ],
    ],

    'testFiringOfWebhooksAndEmailOnPayoutReversalPayoutReversedEventData' => [
        'entity'   => 'event',
        'event'    => 'payout.reversed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'reversed',
                ],
            ],
        ],
    ],

    'testTransactionCreatedWebhookAndPayoutReversedEmailNotFiringForCurrentAccountPayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email not firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => 'EgmjebcvYkSg3v',
                'source_type'         => 'payout',
                'status'              => 'REVERSED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testTransactionCreatedWebhookAndPayoutReversedEmailNotFiringForCurrentAccountPayoutEventData' => [
        'entity'   => 'event',
        'event'    => 'payout.reversed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'reversed',
                ],
            ],
        ],
    ],

    'testWorkflowActionNotesTransformationForNumericAndEmptyKeys' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/w-actions/%s/diff',
            'content' => []
        ],
        'response' => [
            'content' => [
                'old' => [],
                'new' => [
                    'merchant_id' => '10000000000000',
                    'fund_account_id' => '100000000000fa',
                    'method' => 'fund_transfer',
                    'attempts' => 1,
                    'type' => 'default',
                    'fees' => 0,
                    'tax' => 0,
                    'amount' => 10000,
                    'currency' => 'INR',
                    'purpose' => 'refund',
                    'mode' => 'NEFT',
                    'notes' => [
                        'notes_key_0' => 'Test',
                        'notes_key_1' => 'Test1',
                        'notes_key_2' => 'Test2',
                        'a' => 'Test2',
                    ],
                    'narration' => 'Test Merchant Fund Transfer',
                    'channel' => 'yesbank',
                    'purpose_type' => 'refund'
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testWorkflowActionNotesTransformationForNonAssociativeArrays' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/w-actions/%s/diff',
            'content' => []
        ],
        'response' => [
            'content' => [
                'old' => [],
                'new' => [
                    'merchant_id' => '10000000000000',
                    'fund_account_id' => '100000000000fa',
                    'method' => 'fund_transfer',
                    'attempts' => 1,
                    'type' => 'default',
                    'fees' => 0,
                    'tax' => 0,
                    'amount' => 10000,
                    'currency' => 'INR',
                    'purpose' => 'refund',
                    'mode' => 'NEFT',
                    'notes' => [
                        'notes_key_0' => 'Test',
                        'notes_key_1' => 'Test1',
                        'notes_key_2' => 'Test2',
                        'notes_key_3' => 'Test3',
                    ],
                    'narration' => 'Test Merchant Fund Transfer',
                    'channel' => 'yesbank',
                    'purpose_type' => 'refund'
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testScheduledPayoutCreationPostPayoutApproval' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreateScheduledPayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'scheduled',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateScheduledPayoutInvalidTimeStamp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SCHEDULED_PAYOUT_INVALID_TIMESTAMP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_SCHEDULED_PAYOUT_INVALID_TIMESTAMP,
        ],
    ],

    'testCreateScheduledPayoutWhereTimeStampOutOfTimeSlot' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SCHEDULED_PAYOUT_INVALID_TIME_SLOT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_SCHEDULED_PAYOUT_INVALID_TIME_SLOT,
        ],
    ],

    'testCreateScheduledPayoutPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SCHEDULED_PAYOUT_AUTH_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_SCHEDULED_PAYOUT_AUTH_NOT_SUPPORTED,
        ],
    ],

    'testFiringOfWebhooksOnPayoutScheduled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'scheduled',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testFiringOfWebhooksOnPayoutScheduledEventData' => [
        'entity'   => 'event',
        'event'    => 'payout.scheduled',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'scheduled',
                ],
            ],
        ],
    ],

    'testFiringOfWebhooksOnPayoutScheduledFromPending' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFiringOfWebhooksOnPayoutScheduledFromPendingEventData' => [
        'entity'   => 'event',
        'event'    => 'payout.pending',
        'contains' => [
            'payout',
        ],
        'payload' => [
            'payout' => [
                'entity' => [
                    'entity'     => 'payout',
                    'status'     => 'pending',
                ],
            ],
        ],
    ],

    'testCancelScheduledPayout' => [
        'request'  => [
            'method'  => 'POST',
        ],
        'response'  => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'cancelled',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCancelScheduledPayoutPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SCHEDULED_PAYOUT_CANCEL_AUTH_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_SCHEDULED_PAYOUT_CANCEL_AUTH_NOT_SUPPORTED,
        ],
    ],

    'testCancelScheduledPayoutWithComments' => [
        'request'  => [
            'method'  => 'POST',
        ],
        'response'  => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'cancelled',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCancelQueuedPayoutProxyAuth' => [
        'request'  => [
            'method'  => 'POST',
        ],
        'response'  => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000001,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'cancelled',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testCancelQueuedPayoutPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
        ],
        'response'  => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000001,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'cancelled',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testCancelQueuedPayoutWithComments' => [
        'request'  => [
            'method'  => 'POST',
        ],
        'response'  => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000001,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'cancelled',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testApproveScheduledPayoutAfterScheduledAtTime' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SCHEDULED_PAYOUT_CANCEL_REJECT_APPROVE_INVALID_TIMESTAMP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_SCHEDULED_PAYOUT_CANCEL_REJECT_APPROVE_INVALID_TIMESTAMP,
        ],
    ],

    'testCancelScheduledPayoutAfterScheduledAtTime' => [
        'request'  => [
            'method'  => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SCHEDULED_PAYOUT_CANCEL_REJECT_APPROVE_INVALID_TIMESTAMP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_SCHEDULED_PAYOUT_CANCEL_REJECT_APPROVE_INVALID_TIMESTAMP,
        ],
    ],

    'testRejectScheduledPayoutAfterScheduledAtTime' => [
        'request'  => [
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SCHEDULED_PAYOUT_CANCEL_REJECT_APPROVE_INVALID_TIMESTAMP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_SCHEDULED_PAYOUT_CANCEL_REJECT_APPROVE_INVALID_TIMESTAMP,
        ],
    ],

    'testBulkPayoutWithNotes' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'notes'                     => [
                        'abc'                   => 'xyz',
                    ],
                    'idempotency_key'           => 'batch_abc123',

                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'notes'                     => [
                        'abc2'                  => 'xyz',
                    ],
                    'idempotency_key'           => 'batch_abc124'
                ]
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9999310
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123',
                        'notes'                     => [
                            'abc'                   => 'xyz',
                        ],
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9998620
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc124',
                        'notes'                     => [
                            'abc2'                  => 'xyz',
                        ],
                    ]
                ]
            ],
        ],
    ],

    'testCreatePayoutWithIfQueueLowBalanceFalse' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc'         => 'xyz',
                ],
                'queue_if_low_balance'  => 0
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the payout operation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
        ],
    ],

    'testCreatePayoutWithIfQueueLowBalanceFalseWithCredits' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc'         => 'xyz',
                ],
                'queue_if_low_balance'  => 0
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the payout operation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
        ],
    ],

    'testPayoutFetchById' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/payouts/id',
            'content' => []
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testPayoutsFetchMultiple' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/payouts',
            'content' => []
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testRblPayoutWithInvalidMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => '10xKarna',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true,
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYOUT_INVALID_MODE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_INVALID_MODE,
        ],
    ],

    'testOnHoldPayoutCreateForFeatureNotEnabledAndDirectAccount' => [
    'request'  => [
        'method'  => 'POST',
        'url'     => '/payouts',
        'content' => [
            'account_number'  => '2224440041626905',
            'amount'          => 2000000,
            'currency'        => 'INR',
            'narration'       => 'Batman',
            'mode'            => 'IMPS',
            'fund_account_id' => 'fa_100000000000fa',
            'purpose'         => 'refund',
            'notes'           => [
                'abc'         => 'xyz',
            ],
        ],
    ],
    'response' => [
        'content' => [
            'entity'          => 'payout',
            'amount'          => 2000000,
            'currency'        => 'INR',
            'fund_account_id' => 'fa_100000000000fa',
            'narration'       => 'Batman',
            'status'          => 'queued',
            'mode'            => 'IMPS',
            'tax'             => 0,
            'fees'            => 0,
            'notes'           => [
                'abc'         => 'xyz',
            ],
        ],
    ],
],


    'testCreatePayoutWithCustomPurpose' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'custom',
                'notes'           => [
                    'abc'         => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'purpose'         => 'custom',
                'notes'           => [
                    'abc'         => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithInvalidPurpose' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'invalid',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc'         => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid purpose: invalid'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutWithoutPurpose' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The purpose field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutPurpose' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/payouts/purposes',
            'content' => [
                'purpose'   => 'test purpose',
                'purpose_type'  => 'refund'
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection'
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateMasterCardSendPayoutPurpose' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/payouts/purposes',
            'content' => [
                'purpose'   => 'business disbursal',
                'purpose_type'  => 'refund'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Purpose 'business disbursal' is an internal purpose used " .
                                     "for payout to cards and cannot be added.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutPurposeWithInvalidPurpose' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/payouts/purposes',
            'content' => [
                'purpose'       => '334kfs *',
                'purpose_type'  => 'refund'
            ],
        ],
        'response'  => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The purpose may only contain alphabets, digits, hyphens, underscores, and spaces.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutPurposeWithInvalidPurposeType' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/payouts/purposes',
            'content' => [
                'purpose'       => 'test purpose',
                'purpose_type'  => 'invalid'
            ],
        ],
        'response'  => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected purpose type is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetPayoutReversals' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts/{id}/reversals',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'reversal',
            ],
            'status_code' => 200,
        ],
    ],

    'testGetPayoutReversalsForProcessedPayout' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts/{id}/reversals',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 0,
                'items' => []
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateRblPayoutSuccessfully' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626906',
                'amount'          => 2000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'mode'            => 'NEFT',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'NEFT',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testUpdateFTAAndPayoutWithInvalidStateTransition' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email not firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => 'EgmjebcvYkSg3v',
                'source_type'         => 'payout',
                'status'              => 'REVERSED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'webhook update skipped due to invalid state transition'
            ],
        ],
    ],

    'testUpdateFTAAndPayoutWithInvalidStatus' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email not firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => 'EgmjebcvYkSg3v',
                'source_type'         => 'payout',
                'status'              => 'XYZ',
                'utr'                 => 928337183,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid status',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCancelPayoutNotInQueuedState' => [
        'request'  => [
            'method'  => 'POST',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYOUT_NOT_QUEUED_OR_SCHEDULED_STATUS,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_QUEUED_OR_SCHEDULED_STATUS,
        ],
    ],

    'testRejectPayoutNotInPendingState' => [
        'request'  => [
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYOUT_INVALID_STATE,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_INVALID_STATE,
        ],
    ],

    'testBulkPayoutWithThrottling' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'NEFT',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'notes'                     => [
                        'abc'                   => 'xyz',
                    ],
                    'idempotency_key'           => 'batch_abc123',

                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100000000',
                        'currency'              => 'INR',
                        'mode'                  => 'RTGS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'notes'                     => [
                        'abc2'                  => 'xyz',
                    ],
                    'idempotency_key'           => 'batch_abc124'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'UPI',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'vpa',
                        'account_name'          => 'Mehul Kaushik',
                        'account_IFSC'          => '',
                        'account_number'        => '',
                        'account_vpa'           => 'mehul@hdfc'
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'notes'                     => [
                        'abc2'                  => 'xyz',
                    ],
                    'idempotency_key'           => 'batch_abc125'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'notes'                     => [
                        'abc2'                  => 'xyz',
                    ],
                    'idempotency_key'           => 'batch_abc126'
                ]
            ],
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 4,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'NEFT',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123',
                        'notes'                     => [
                            'abc'                   => 'xyz',
                        ],
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100000000,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'RTGS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc124',
                        'notes'                     => [
                            'abc2'                  => 'xyz',
                        ],
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'vpa',
                            'vpa'                   => [
                                'address'           => 'mehul@hdfc',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9999310
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'UPI',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc125'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9998620
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc126',
                    ],
                ],
            ],
        ],
    ],

    'testProcessBulkPayoutDelayedInitiation' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/batch/process',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testProcessBulkPayoutDelayedInitiationWithNewCreditsFlow'  => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/batch/process',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testScheduledPayoutProcessing' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/scheduled/process'
        ],
        'response'  => [
            'content' => [
            ],
        ],
    ],

    'testScheduledPayoutProcessingWithNewCreditsFlow' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/scheduled/process'
        ],
        'response'  => [
            'content' => [
            ],
        ],
    ],

    'testScheduledPayoutProcessingLowBalance' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/scheduled/process'
        ],
        'response'  => [
            'content' => [
            ],
        ],
    ],

    'testScheduledPayoutProcessingAutoReject' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/scheduled/process',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response'  => [
            'content' => [
            ],
        ],
    ],

    'testScheduledPayoutProcessingAutoRejectWithWfs' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/scheduled/process',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response'  => [
            'content' => [
            ],
        ],
    ],

    'testScheduledPayoutProcessingAutoRejectWithWfsToPS' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/scheduled/process',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response'  => [
            'content' => [
            ],
        ],
    ],

    'testGetScheduleTimeSlotsForDashboard' =>  [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/schedule/timeslots'
        ],
        'response'  => [
            'content' => [
                '09',
                '13',
                '17',
                '21',
            ],
        ],
    ],

    'testScheduledPayoutSummary' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/_meta/summary',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testBulkScheduledPayouts' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => '',
                        'scheduled_at'          => '1593835770'
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'UPI',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => '',
                        'scheduled_at'          => '1593835770'
                    ],
                    'fund'                      => [
                        'account_type'          => 'vpa',
                        'account_name'          => 'Debojyoti Chak',
                        'account_IFSC'          => '',
                        'account_number'        => '',
                        'account_vpa'           => '8861655100@ybl'
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Debojyoti Chak',
                        'email'                 => 'sampletwo@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc124'
                ]
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'scheduled',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'vpa',
                            'vpa'                   => [
                                'address'           => '8861655100@ybl'
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'scheduled',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'UPI',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc124'
                    ]
                ]
            ],
        ],
    ],

    'testProcessBulkScheduledPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/scheduled/process',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreatePayoutWithUnnecessarySpacesTrimmedInPurpose' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => '  refund  ',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithOtpAndUnnecessarySpacesTrimmedInPurpose' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund ',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'token'           => 'BUIj3m2Nx2VvVj',
                'otp'             => '0007',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutPurposeWithSpacesTrimmed' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/payouts/purposes',
            'content' => [
                'purpose'   => 'test purpose',
                'purpose_type'  => 'refund'
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection'
            ],
            'status_code' => 200,
        ],
    ],

    'testBulkPayoutWithNotesAndSpacesTrimmed' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund  ',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna ',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer  ',
                        'name'                  => 'Vivek Karna  ',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'notes'                     => [
                        'abc'                   => 'xyz',
                    ],
                    'idempotency_key'           => 'batch_abc123',

                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund ',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna ',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer  ',
                        'name'                  => 'Vivek Karna ',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'notes'                     => [
                        'abc2'                  => 'xyz',
                    ],
                    'idempotency_key'           => 'batch_abc124'
                ]
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9999310
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123',
                        'notes'                     => [
                            'abc'                   => 'xyz',
                        ],
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9998620
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc124',
                        'notes'                     => [
                            'abc2'                  => 'xyz',
                        ],
                    ]
                ]
            ],
        ],
    ],

    'testBulkPayoutCreationWithWorkflowActive' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'NEFT',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'notes'                     => [
                        'abc'                   => 'xyz',
                    ],
                    'idempotency_key'           => 'batch_abc123',

                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'UPI',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'vpa',
                        'account_name'          => 'Mehul Kaushik',
                        'account_IFSC'          => '',
                        'account_number'        => '',
                        'account_vpa'           => 'mehul@hdfc'
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'notes'                     => [
                        'abc2'                  => 'xyz',
                    ],
                    'idempotency_key'           => 'batch_abc125'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'notes'                     => [
                        'abc2'                  => 'xyz',
                    ],
                    'idempotency_key'           => 'batch_abc126'
                ]
            ],
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 3,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'pending',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => null,
                        'mode'                      => 'NEFT',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123',
                        'notes'                     => [
                            'abc'                   => 'xyz',
                        ],
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'vpa',
                            'vpa'                   => [
                                'address'           => 'mehul@hdfc',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'pending',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => null,
                        'mode'                      => 'UPI',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc125'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'fees'                      => 0,
                        'tax'                       => 0,
                        'status'                    => 'pending',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => null,
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc126',
                    ],
                ],
            ],
        ],
    ],

    'testApproveBulkPayoutsDelayedInitiation' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/approve/bulk',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'   => 'BUIj3m2Nx2VvVj',
                'otp'     => '0007',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testPricingRuleAuthTypeForPrivateAuthPayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testPricingRuleAuthTypeForProxyAuthPayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateFreePayoutForNEFTModeSharedAccountPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateFreePayoutForNEFTModeSharedAccountPrivateAuthSkipFreePayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateFreePayoutForNEFTModeSharedAccountPrivateAuthWithNewCreditsFlow' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateFreePayoutForNEFTModeSharedAccountPrivateAuthWithOldCreditsFlow' =>  [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateFreePayoutForUPIModeSharedAccountPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'UPI',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'UPI',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateFreePayoutForIMPSModeSharedAccountProxyAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateFreePayoutForNEFTModeDirectAccountProxyAuth' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626906',
                'amount'          => 2000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateFreePayoutForUPIModeDirectAccountPrivateAuth' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626906',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'UPI',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'RBL does not support UPI payouts to VPA',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testCreateFreePayoutForIMPSModeDirectAccountProxyAuth' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626906',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testReverseFreePayoutForNEFTModeSharedAccount' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'NEFT',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => 'EgmjebcvYkSg3v',
                'source_type'         => 'payout',
                'status'              => 'REVERSED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testFailFreePayoutForNEFTModeDirectAccount' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'NEFT',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => 'EgmjebcvYkSg3v',
                'source_type'         => 'payout',
                'status'              => 'FAILED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testReverseFreePayoutForIMPSModeSharedAccount' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => 'EgmjebcvYkSg3v',
                'source_type'         => 'payout',
                'status'              => 'REVERSED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testReverseFreePayoutForIMPSModeDirectAccount' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => 'EgmjebcvYkSg3v',
                'source_type'         => 'payout',
                'status'              => 'REVERSED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testFailFreePayoutForIMPSModeDirectAccount' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for free payouts reversal',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => 'EgmjebcvYkSg3v',
                'source_type'         => 'payout',
                'status'              => 'FAILED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testSharedAccountPayoutCreationFailedDueToInsufficientBalanceAndCheckCounterAttributes' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'mode'                 => 'NEFT',
                'fund_account_id'      => 'fa_100000000000fa',
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => 0,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the payout operation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
        ],
    ],

    'testSharedAccountPayoutCreationFailedDueToInsufficientBalance' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'mode'                 => 'NEFT',
                'fund_account_id'      => 'fa_100000000000fa',
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => 0,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the payout operation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
        ],
    ],

    'testQueuedSharedAccountPayoutCreationAndCheckCounterAttributes' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'status'          => 'queued',
            ],
        ],
    ],

    'testFreePayoutFromPendingToCreatedState' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testScheduledPayoutCreationAndNoIncrementOfCounter' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetFreePayoutsAttributesOnProxyAuthOwnerUser' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/{balance_id}/free_payout',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetFreePayoutsAttributesForNewSlabMerchants' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/{balance_id}/free_payout',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetFreePayoutsAttributesOnProxyAuthViewOnlyUser' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payouts/{balance_id}/free_payout',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testGetFreePayoutsAttributesOnAdminAuthWithIncorrectBalanceType' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/{balance_id}/free_payout',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only Banking type balance is allowed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FREE_PAYOUTS_ATTRIBUTES_INCORRECT_BALANCE_TYPE,
        ],
    ],

    'testGetFreePayoutsAttributesOnAdminAuthWithBalanceIdNotPresentInDb' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/{balance_id}/free_payout',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid balance id, no db records found.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FREE_PAYOUTS_ATTRIBUTES_INVALID_BALANCE_ID,
        ],
    ],

    'testBackFillDataForExistingBulkUsers' =>  [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/bulk/amount_type',
            'content' => [
                'merchant_ids' => ['10000000000000']
            ]
        ],
        'response'  => [
            'content'     => [
                'success' => true
            ],
        ],
    ],

    'testUpdateBulkPayoutAmountTypeByOwner' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts/bulk/amount_type',
            'content' => [
                'merchant_ids' => ['10000000000000'],
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'success' => true,
            ],
        ],
    ],

    'testUpdateBulkPayoutAmountTypeByNonOwnerRole' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts/bulk/amount_type',
            'content' => [
                'merchant_ids' => ['10000000000000'],
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testSkipWorkflowForPayoutEnabledRequestValueTrue' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 500000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'skip_workflow'   => true,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 500000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testSkipWorkflowForPayoutEnabledRequestValueFalse' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 500000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'skip_workflow'   => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only true is valid for skip_workflow key.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSkipWorkflowForPayoutEnabledRequestValueTrueAndSkipWorkflowForApiEnabled' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 500000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'skip_workflow'   => true,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 500000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testSkipWorkflowForPayoutDisablesRequestWithSkipWorkflowKey' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 500000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'skip_workflow'   => true,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Skip workflow is not  enabled.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testWorkflowFeatureWithoutSkip' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 500000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'skip_workflow'   => true,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 500000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testSkipWorkflowKeyWithoutBoolean' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 500000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'skip_workflow'   => "sfgsg",
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only true is valid for skip_workflow key.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutWithoutOriginFieldPrivateAuth' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 500000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 500000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testCreatePayoutWithOriginFieldPrivateAuth' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 500000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'origin'          => "api",
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'origin is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateVendorPaymentPayoutWithOrigin' => [
        'request'  => [
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
            ],
        ],
    ],

    'testCreateVendorPaymentPayoutWithOriginWithSourceDetails' => [
        'request'  => [
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
    ],

    'testGetVendorPaymentPayoutWithOrigin' => [
        'request'  => [
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'url'     => '/payouts_internal/{payout_id}',
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'pending',
                'mode'            => 'NEFT',
                'tax'             => 0,
                'fees'            => 0,
                'origin'          => 'api',
            ],
        ],
    ],

    'testCreatePayoutForRequestSubmitted' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ]
        ],
                'response' => [
                    'content' => [
                            'entity'          => 'payout',
                            'amount'          => 2000000,
                            'currency'        => 'INR',
                            'fund_account_id' => 'fa_100000000000fa',
                            'narration'       => 'Batman',
                            'purpose'         => 'refund',
                            'status'          => 'processing',
                            'mode'            => 'IMPS',
                            'notes'           => [
                                'abc' => 'xyz',
                            ],
                    ]
                ]
     ],

    'testFailPayoutWithErrorCodeAsPbankValidationError' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'PBANK_VALIDATION_ERROR',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => 'EgmjebcvYkSg3v',
                'source_type'         => 'payout',
                'status'              => 'FAILED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testPayoutValidatePurposeForValidPurpose' => [
        'request'  => [
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts/purpose/validate',
            'content' => [
                'purpose'  => 'refund',
            ],
        ],
        'response' => [
            'content' => [
                'is_valid_purpose' => true,
            ],
        ],
    ],

    'testPayoutValidatePurposeForInvalidPurpose' => [
        'request'  => [
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts/purpose/validate',
            'content' => [
                'purpose'  => 'testing-purpose',
            ],
        ],
        'response' => [
            'content' => [
                'is_valid_purpose' => false,
            ],
        ],
    ],

    'testCreatePayoutLinkPayoutWithoutSourceDetails' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
            ],
        ],
    ],

    'testCreatePayoutWithMultipleSourceDetails' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'xpayroll',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000000pa',
                        'source_type' => 'source_type2',
                        'priority'    => 2,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'xpayroll',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000000pa',
                        'source_type' => 'source_type2',
                        'priority'    => 2,
                    ]
                ],
            ],
        ],
    ],

    'testCreateXpayrollPayoutWithSourceDetails' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'xpayroll',
                        'priority'    => 1,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                //zero pricing for xpayroll
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'xpayroll',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
    ],

    'testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
            ],
        ],
    ],

    'testCreatePayoutLinkPayoutWithSourceDetails' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
    ],

    'testCreatePayoutLinkPayoutWithExtraFieldsInSourceDetails' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                        'abcd'        => 'efhg',
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'abcd is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testCreatePayoutLinkPayoutWithIncorrectPriorityInSourceDetails' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 'abc',
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The source_details.1.priority must be an integer.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutLinkPayoutWithIncorrectPrioritySequenceInSourceDetails' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'source_details has sources with duplicate priorities',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutOnPrivateAuthWithSourceDetails' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'source_details is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutLinkPayoutWithSourceDetailsAsNotAnArray' => [
        'request'   => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'source_details'  => 'aenlc',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The source details must be an array.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateVendorPaymentPayoutWithDuplicateSourceDetailsButDifferentPriorities' => [
        'request'   => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'A source with same source_id and source_type already exists.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_SOURCE_ALREADY_EXISTS,
        ],
    ],

    'testFetchPayoutWithSourceIdAndSourceTypeOnInternalAuth' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/payouts',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchPayoutsOnProxyAuth' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/payouts',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetXpayrollPayoutWithExperimentOn' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/payouts',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testGetXpayrollPayoutWithExperimentOff' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/payouts',
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testFetchPayoutsOnXDemo' => [
        'request' => [
            'url'    => '/payouts?product=banking',
            'method' => 'get',
            'content' => [
                'product' => 'banking',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity'          => 'payout',
                        'amount'          => 100,
                        'currency'        => 'INR',
                        'fund_account_id' => 'fa_D6Z9Jfir2egAUT',
                        'purpose'         => 'refund',
                        'status'          => 'processing',
                        'tax'             => 90,
                        'fees'            => 590,
                    ],
                ],
            ],
        ],
    ],

    'testPayoutToSCBLCardWithNetworkOtherThanAmexMasterVisa' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                "account_type" => "card",
                "contact_id"   => "cont_1000001contact",
                "card"         => [
                    "name"         => "Prashanth YV",
                    "number"       => "6521618738419536",
                    "cvv"          => "212",
                    "expiry_month" => 10,
                    "expiry_year"  => 29,
                ]
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'RuPay cards are not supported for issuer SCBL',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
        ],
    ],

    'testPayoutToSCBLCardWithNetworkOtherThanAmexMasterVisaIfFundAccountAlreadyCreated' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account_id' => 'fa_100000000001fa',
                'amount'          => 100,
                'mode'            => 'IMPS',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'RuPay cards are not supported for issuer SCBL',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPayoutToSCBLCardWithMastercardIfFundAccountAlreadyCreated' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account_id' => 'fa_100000000001fa',
                'amount'          => 100,
                'mode'            => 'NEFT',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000001fa',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'NEFT'
            ]
        ],
    ],

    'testPayoutToSCBLCardWithVisaIfFundAccountAlreadyCreated' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account_id' => 'fa_100000000001fa',
                'amount'          => 100,
                'mode'            => 'NEFT',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000001fa',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'NEFT'
            ]
        ],
    ],

    'testSourceCreationInCaseOfPendingPayoutCreatedByVendorPayments' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'pending',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
    ],

    'testTrueSkipWorkflowForXPayrollPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                // zero pricing for xpayroll
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
    ],

    'testEnableWorkflowForInternalContactPayoutCreatedByVendorPayments' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'                       => '2224440041626905',
                'amount'                               => 2000000,
                'currency'                             => 'INR',
                'purpose'                              => 'refund',
                'narration'                            => 'Batman',
                'mode'                                 => 'IMPS',
                'enable_workflow_for_internal_contact' => true,
                'fund_account_id'                      => 'fa_100000000000fa',
                'notes'                                => [
                    'abc' => 'xyz',
                ],
                'origin'                               => 'dashboard',
                'source_details'                       => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'pending',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
    ],

    'testDisablePayoutServicePayoutCreationForInternalPayoutFeatureEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'                       => '2224440041626905',
                'amount'                               => 2000000,
                'currency'                             => 'INR',
                'purpose'                              => 'refund',
                'narration'                            => 'Batman',
                'mode'                                 => 'IMPS',
                'enable_workflow_for_internal_contact' => false,
                'fund_account_id'                      => 'fa_100000000000fa',
                'notes'                                => [
                    'abc' => 'xyz',
                ],
                'origin'                               => 'dashboard',
                'source_details'                       => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
    ],

    'testDisableWorkflowForInternalContactPayoutCreatedByVendorPayments' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'                       => '2224440041626905',
                'amount'                               => 2000000,
                'currency'                             => 'INR',
                'purpose'                              => 'refund',
                'narration'                            => 'Batman',
                'mode'                                 => 'IMPS',
                'enable_workflow_for_internal_contact' => false,
                'fund_account_id'                      => 'fa_100000000000fa',
                'notes'                                => [
                    'abc' => 'xyz',
                ],
                'origin'                               => 'dashboard',
                'source_details'                       => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
    ],

    'testSourceCreationInCaseOfQueuedPayoutCreatedByVendorPayments' => [
        'request' => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => 1,
                'origin'               => 'dashboard',
                'source_details'       => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
    ],

    'testSourceCreationInCaseOfCompositePayoutCreatedBySettlements' => [
        'request' => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account'   => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Mehul Kaushik',
                        'ifsc'           => 'ICIC0000104',
                        'account_number' => '1111000011110000'
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => 1,
                'origin'               => 'dashboard',
                'source_details'       => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'settlements',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'settlements',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
    ],

    'testIdempotencyInCaseOfCompositePayoutCreatedBySettlementsWithDifferentRequestContents' => [
        'request' => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account'   => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Mehul Kaushik',
                        'ifsc'           => 'ICIC0000104',
                        'account_number' => '1111000011110000'
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => 1,
                'origin'               => 'dashboard',
                'source_details'       => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'settlements',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SAME_IDEM_KEY_DIFFERENT_REQUEST,
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_SAME_IDEM_KEY_DIFFERENT_REQUEST,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testIdempotencyInCaseOfCompositePayoutCreatedBySettlementsWithSameRequestContents' => [
        'request' => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account'   => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Mehul Kaushik',
                        'ifsc'           => 'ICIC0000104',
                        'account_number' => '1111000011110000'
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => 1,
                'origin'               => 'dashboard',
                'source_details'       => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'settlements',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'settlements',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
    ],

    'testSourceCreationInCaseOfInternalContactPayoutCreatedByXpayroll' => [
        'request' => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => '',
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => 1,
                'origin'               => 'dashboard',
                'source_details'       => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'xpayroll',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'xpayroll',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
    ],

    'testIdempotencyInCaseOfInternalContactPayoutCreatedByXpayrollWithDifferentRequestContents' => [
        'request' => [],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SAME_IDEM_KEY_DIFFERENT_REQUEST,
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_SAME_IDEM_KEY_DIFFERENT_REQUEST,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testDuplicateFundAccountInCaseOfCompositePayoutCreatedBySettlements' => [
        'request' => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account'   => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Mehul Kaushik',
                        'ifsc'           => 'ICIC0000104',
                        'account_number' => '1111000011110000'
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => 1,
                'origin'               => 'dashboard',
                'source_details'       => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'settlements',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'settlements',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
    ],

    'testCreatePayoutViaUpi' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'UPI',
                'fund_account_id' => 'fa_100000000003fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000003fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'UPI',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutToBlacklistedVpasForMerchants' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'UPI',
                'fund_account_id' => 'fa_100000000003fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payouts to this VPA ID are not allowed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testCreatePayoutToBlacklistedVpasForMerchantsBlockedViaCommonRegex' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'UPI',
                'fund_account_id' => 'fa_100000000003fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payouts to this VPA ID are not allowed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testCreatePayoutToVpaCheckPayoutNotBlocked' => [
        'vpa' => [
            'username' => 'abcd',
            'handle'   => 'defg',
        ],
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'UPI',
                'fund_account_id' => 'fa_100000000003fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000003fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'UPI',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutViaAmazonPay' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 5000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'amazonpay',
                'fund_account_id' => 'fa_100000000003fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 5000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000003fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'amazonpay',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutViaAmazonPayMerchantDisabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 5000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'amazonpay',
                'fund_account_id' => 'fa_100000000003fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_AMAZONPAY_PAYOUTS_NOT_PERMITTED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_AMAZONPAY_PAYOUTS_NOT_PERMITTED,
        ],
    ],

    'testFiringOfWebhookPayoutResponseForReversedPayout' => [
        'entity'   => 'event',
        'event'    => 'payout.reversed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'reversed',
                    'failure_reason' => 'Technical issue at beneficiary bank. Please retry after 30 mins.',
                    'error'  => [
                        'source' => 'beneficiary_bank',
                        'reason' =>  'beneficiary_bank_rejected',
                        'description' => 'Technical issue at beneficiary bank. Please retry after 30 mins.'
                    ]
                ],
            ],
        ],
    ],

    'testFiringOfWebhookPayoutResponseForReversedPayoutOnLiveMode' => [
        'entity'   => 'event',
        'event'    => 'payout.reversed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'reversed',
                    'failure_reason' => 'Payout failed as the card number is not available. Please retry.',
                    'error'  => [
                        'source' => 'internal',
                        'reason' =>  'card_number_unavailable',
                        'description' => 'Payout failed as the card number is not available. Please retry'
                    ]
                ],
            ],
        ],
    ],

    'testNewErrorObjectInPayoutResponse' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'error'           => [
                    'description' => null,
                    'reason'      => null,
                    'source'      => null
                ]
            ],
        ],
    ],

    'testNewErrorObjectInPayoutResponseOnLiveMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'error'           => [
                    'description' => null,
                    'reason'      => null,
                    'source'      => null
                ]
            ],
        ],
    ],

    'testFailedWebhookPayoutResponseForNewBankingError' => [
        'entity'   => 'event',
        'event'    => 'payout.failed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'failed',
                    'failure_reason' => 'Insufficient balance to process payout',
                    'error'          => [
                        'source' => 'business',
                        'reason' =>  'insufficient_funds',
                        'description' => 'Your account does not have enough balance to carry out the payout operation.'
                    ]
                ],
            ],
        ],
    ],

    'testCreatePayoutWithWrongFundAccountIdNewApiError' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_101200340560fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                    'reason'      => 'input_validation_failed',
                    'source'      => 'business',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testCreatePayoutWithWrongFundAccountIdNewApiErrorOnLiveMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_101200340560fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                    'reason'      => 'input_validation_failed',
                    'source'      => 'business',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testCreatePayoutWithIfQueueLowBalanceFalseNewApiError' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc'         => 'xyz',
                ],
                'queue_if_low_balance'  => 0
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the payout operation.',
                    'reason'      => 'insufficient_funds',
                    'source'      => 'business',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
        ],
    ],

    'testCreatePayoutWithIfQueueLowBalanceFalseNewApiErrorOnLiveMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc'         => 'xyz',
                ],
                'queue_if_low_balance'  => 0
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the payout operation.',
                    'reason'      => 'insufficient_funds',
                    'source'      => 'business',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
        ],
    ],

    'testPayoutRejectWhenWorkflowEditNewApiError' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'        => '2224440041626905',
                'amount'                => 10000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'NEFT',
                'queue_if_low_balance'  => 0,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Workflow edit on the same payout rule is active',
                    'reason'      => 'server_error',
                    'source'      => 'internal',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_WORKFLOW_EDIT_IN_PROGRESS,
        ],
    ],

    'testFiringOfWebhookPayoutResponseForReversedPayoutDefaultErrorObject' => [
        'entity'   => 'event',
        'event'    => 'payout.reversed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'reversed',
                    'failure_reason' => 'Payout failed. Contact support for help.',
                    'error'  => [
                        'source' => 'internal',
                        'reason' =>  'server_error',
                        'description' => 'Payout failed. Contact support for help.'
                    ]
                ],
            ],
        ],
    ],

    'testFiringOfWebhookPayoutResponseForReversedPayoutDefaultErrorObjectOnLiveMode' => [
        'entity'   => 'event',
        'event'    => 'payout.reversed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'reversed',
                    'failure_reason' => 'Payout failed. Contact support for help.',
                    'error'  => [
                        'source' => 'internal',
                        'reason' =>  'server_error',
                        'description' => 'Payout failed. Contact support for help.'
                    ]
                ],
            ],
        ],
    ],

    'testFiringOfWebhookPayoutResponseForProcessedPayout' => [
        'entity'   => 'event',
        'event'    => 'payout.processed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'processed',
                    'failure_reason' => null,
                    'error'  => [
                        'source'      => null,
                        'reason'      => null,
                        'description' => null
                    ]
                ],
            ],
        ],
    ],

    'testFiringOfWebhookPayoutResponseForProcessedPayoutOnLiveMode' => [
        'entity'   => 'event',
        'event'    => 'payout.processed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'processed',
                    'failure_reason' => null,
                    'error'  => [
                        'source'      => null,
                        'reason'      => null,
                        'description' => null
                    ]
                ],
            ],
        ],
    ],

    'testUsersApiForExistingNonBulkUser' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/users',
        ],
        'response' => [
            'content' => [
                'id' => "MerchantUser01",
            ],
        ],
    ],

    'testUsersApiForNewBulkUser' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/users',
        ],
        'response' => [
            'content' => [
                'id' => "MerchantUser01",
            ],
        ],
    ],

    'testUsersApiForExistingBulkUserAmountTypePaise' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/users',
        ],
        'response' => [
            'content' => [
                'id' => "MerchantUser01",
            ],
        ],
    ],

    'testUsersApiForExistingBulkUserAmountTypeRupees' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/users',
        ],
        'response' => [
            'content' => [
                'id' => "MerchantUser01",
            ],
        ],
    ],

    'testCsvSampleFileForBulkPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/bulk/sample_file',
            'content' => [
                'file_type'         => 'sample_file',
                'file_extension'    => 'csv'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCsvSampleFileForBulkPayoutsAmazonPayEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/bulk/sample_file',
            'content' => [
                'file_type'         => 'sample_file',
                'file_extension'    => 'csv'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCsvTemplateFileForBulkPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/bulk/sample_file',
            'content' => [
                'file_type'         => 'template_file',
                'file_extension'    => 'csv'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCsvTemplateFileForBulkPayoutsAmazonPayEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/bulk/sample_file',
            'content' => [
                'file_type'         => 'template_file',
                'file_extension'    => 'csv'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testXlsxTemplateFileForBulkPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/bulk/sample_file',
            'content' => [
                'file_type'         => 'template_file',
                'file_extension'    => 'xlsx'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testXlsxTemplateFileForBulkPayoutsAmazonPayEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/bulk/sample_file',
            'content' => [
                'file_type'         => 'template_file',
                'file_extension'    => 'xlsx'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testXlsxSampleFileForBulkPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/bulk/sample_file',
            'content' => [
                'file_type'         => 'sample_file',
                'file_extension'    => 'xlsx'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testXlsxSampleFileForBulkPayoutsAmazonPayEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/bulk/sample_file',
            'content' => [
                'file_type'         => 'sample_file',
                'file_extension'    => 'xlsx'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateBulkPayoutWithAmountTypeRupees' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount_in_rupees'      => '10.23',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount_in_rupees'      => '1',
                        'currency'              => 'INR',
                        'mode'                  => 'UPI',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'vpa',
                        'account_name'          => 'Debojyoti Chak',
                        'account_IFSC'          => '',
                        'account_number'        => '',
                        'account_vpa'           => '8861655100@ybl'
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Debojyoti Chak',
                        'email'                 => 'sampletwo@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc124'
                ]
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 1023,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 1613,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 1613,
                            'balance'               => 9998387
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'vpa',
                            'vpa'                   => [
                                'address'           => '8861655100@ybl'
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9997697
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'UPI',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc124'
                    ]
                ]
            ],
        ],
    ],

    'testFiringOfWebhookPayoutResponseForUpdatedPayout' => [
        'entity'   => 'event',
        'event'    => 'payout.updated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'processing',
                    'failure_reason' => null,
                    'error'  => [
                        'source'      => null,
                        'reason'      => null,
                        'description' => null
                    ]
                ],
            ],
        ],
    ],

    'testCancelQueuedPayoutWithCommentsGreaterThanMaxRange' => [
        'request'  => [
            'method'  => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The remarks may not be greater than 255 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCancelQueuedPayoutPrivateAuthAndCheckDataAfterFetchingItAgain' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payouts/{id}',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreatePayoutViaDashboardAndAssertMetricsSent' => [
        'request' => [
            'url'    => '/payouts_with_otp',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 500000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'token'           => 'BUIj3m2Nx2VvVj',
                'otp'             => '0007',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 500000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testPayoutFetchByIdCreatedByPartner' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/payouts/id',
            'content' => []
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreatePayoutWithExistingIKeyButNoSourceEntityFoundOnApiAndPSForPSIkeyFeatureEnabledMerchant' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_PAYOUT_SERVICE_IDEM_KEY_SOURCE_NOT_FOUND,
            'message'             => 'Payout service idempotency key source not found in payouts db',
        ],
    ],

    'testCreatePayoutWithExistingIKeyButNoSourceEntityFoundOnApiAndNoMappingOnPSForPSIkeyFeatureEnabledMerchant' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_PAYOUT_SERVICE_IDEM_KEY_SOURCE_UNMAPPED,
            'message'             => 'Payout service idempotency key has no source mapped',
        ],
    ],

    'testCreatePayoutWithExistingIKeyButNoSourceEntityFoundOnApiAndNullMappingOnPSFForPSIkeyFeatureEnabledMerchant' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_PAYOUT_SERVICE_IDEM_KEY_SOURCE_UNMAPPED,
            'message'             => 'Payout service idempotency key has no source mapped',
        ],
    ],

    'testCreatePayoutOnPrivateAuthAndMetricsSent' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePartnerPayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateBulkPayoutWithErrorInPayoutData' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'COD',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'vpa',
                        'account_name'          => 'Debojyoti Chak',
                        'account_IFSC'          => '',
                        'account_number'        => '',
                        'account_vpa'           => '8861655100@ybl'
                    ],
                    'contact'                   => [
                        'type'                  => 'employee',
                        'name'                  => 'Mehul Kaushik',
                        'email'                 => 'testemail@example.com',
                        'mobile'                => '9988776655',
                        'reference_id'          => 'abcd1234'
                    ],
                    'idempotency_key'           => 'batch_abc124'
                ]
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9999310
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'batch_id' => 'C0zv9I46W4wiOq',
                        'idempotency_key' => 'batch_abc124',
                        'error' => [
                            'description' => 'Payout mode is invalid',
                            'code' => 'BAD_REQUEST_ERROR',
                        ],
                        'http_status_code' => 400
                    ]
                ]
            ],
        ],
    ],

    'testCreateBulkPayoutWithErrorInFundAccountData'  => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Vivek Karna',
                        'account_IFSC'          => 'HDFC0003780',
                        'account_number'        => '50100244702362',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    'fund'                      => [
                        'account_type'          => 'abcd',
                        'account_name'          => 'Debojyoti Chak',
                        'account_IFSC'          => '',
                        'account_number'        => '',
                        'account_vpa'           => '8861655100@ybl'
                    ],
                    'contact'                   => [
                        'type'                  => 'employee',
                        'name'                  => 'Mehul Kaushik',
                        'email'                 => 'testemail@example.com',
                        'mobile'                => '9988776655',
                        'reference_id'          => 'abcd1234'
                    ],
                    'idempotency_key'           => 'batch_abc124'
                ]
            ]
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 2,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9999310
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ],
                    [
                        'batch_id' => 'C0zv9I46W4wiOq',
                        'idempotency_key' => 'batch_abc124',
                        'error' => [
                            'description' => 'Invalid value for fund account type - abcd',
                            'code' => 'BAD_REQUEST_ERROR',
                        ],
                        'http_status_code' => 400
                    ]
                ]
            ],
        ],
    ],

    'testBlockBankingVAToNonBankingVAPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '7878780111222',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_VA_TO_VA_PAYOUTS_NOT_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_NOT_ALLOWED,
        ],
    ],

    'testBlockVAtoVAPayoutsBetweenCurrentAndNodalVirtualAccounts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '7878780111222',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_VA_TO_VA_PAYOUTS_NOT_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_NOT_ALLOWED,
        ],
    ],

    'testAllowVAtoVAPayoutsWhenDestinationMerchantIsWhitelisted' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '7878780111222',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processed',
                'mode'            => 'IFT',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testAllowVAtoVAPayoutsWhenSourceMerchantIsEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '7878780111222',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processed',
                'mode'            => 'IFT',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testBlockVAtoVAPayoutsWhenBothSourceAndDestinationMerchantNotEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '7878780111222',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
        ],
    ],

    'testBlockVAtoVAPayoutsWhenDestinationVaIsInActive' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '7878780111222',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account associated with provided fund account is either not active or does not exist. please check',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_NO_ACTIVE_BENEFICIARY_VA_FOUND,
        ],
    ],

    'testBlockVAtoVAPayoutsWhenBothSourceAndDestinationAreSameBankingAccount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '7878780111222',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payout to same banking account is blocked',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUT_ON_SAME_ACCOUNT,
        ],
    ],

    'testAllowLowBalanceQueuedVAtoVAPayoutsWhenSourceMerchantIsEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '7878780111222',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'queue_if_low_balance'  => 1,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'mode'            => 'IFT',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testBlockVAtoVAPayoutsWithICICIDestination' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
        ],
    ],

    'testBlockVAtoVAPayoutsWithYesbankDestination' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
        ],
    ],

    'testBlockVAtoVAPayoutsWithRBLDestination' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
        ],
    ],

    'testAllowVAtoVAPayoutsWhenSourceDestinationIsWhitelisted' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testAllowVAtoVAPayoutsWithRazorXExperimentWithICICIDestination' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testAllowVAtoVAPayoutsWithRazorXExperimentWithYesbankDestination' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCAtoVAPayout' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626906',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testVAtoCAPayout' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testBlockVAtoVirtualAccountVpaPayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'UPI',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
        ],
    ],

    'testBlockVAtoVACompositePayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Mehul Kaushik',
                        'ifsc'           => 'ICIC0000104',
                        'account_number' => '3434000111000'
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
        ],
    ],

    'testAllowVAtoVACompositePayoutsWithRazorXExperiment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Mehul Kaushik',
                        'ifsc'           => 'ICIC0000104',
                        'account_number' => '3434000111000'
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'payout',
                'amount'       => 2000000,
                'currency'     => 'INR',
                'narration'    => 'Batman',
                'purpose'      => 'refund',
                'status'       => 'processing',
                'mode'         => 'IMPS',
                'tax'          => 162,
                'fees'         => 1062,
                'notes'        => [
                    'abc' => 'xyz',
                ],
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'ifsc'           => 'ICIC0000104',
                        'bank_name'      => 'ICICI Bank',
                        'name'           => 'Mehul Kaushik',
                        'notes'          => [],
                        'account_number' => '3434000111000'
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'contact'      => '9999999999',
                        'email'        => 'prashanth@razorpay.com',
                        'type'         => 'employee',
                        'reference_id' => null,
                        'batch_id'     => null,
                        'active'       => true,
                        'notes'        => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testBlockBulkVAToVAPayouts' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    // The fund Account data has been kept this way so that the destination account
                    // matches a RazorpayX VA account
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Mehul Kaushik',
                        'account_IFSC'          => 'ICIC0000104',
                        'account_number'        => '3434000111000',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
            ],
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 1,
                'items'                             => [
                    [
                        'batch_id' => 'C0zv9I46W4wiOq',
                        'idempotency_key' => 'batch_abc123',
                        'error' => [
                            'description' => PublicErrorDescription::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
                            'code' => 'BAD_REQUEST_ERROR',
                        ],
                        'http_status_code' => 400
                    ],
                ],
            ],
        ],
    ],

    'testAllowBulkVAToVAPayoutsWithRazorXExperiment' => [
        'request'   => [
            'url'     => '/payouts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'razorpayx_account_number'  => '2224440041626905',
                    'payout'                    => [
                        'amount'                => '100',
                        'currency'              => 'INR',
                        'mode'                  => 'IMPS',
                        'purpose'               => 'refund',
                        'narration'             => '123',
                        'reference_id'          => ''
                    ],
                    // The fund Account data has been kept this way so that the destination account
                    // matches a RazorpayX VA account
                    'fund'                      => [
                        'account_type'          => 'bank_account',
                        'account_name'          => 'Mehul Kaushik',
                        'account_IFSC'          => 'ICIC0000104',
                        'account_number'        => '3434000111000',
                        'account_vpa'           => ''
                    ],
                    'contact'                   => [
                        'type'                  => 'customer',
                        'name'                  => 'Vivek Karna',
                        'email'                 => 'sampleone@example.com',
                        'mobile'                => '9988998899',
                        'reference_id'          => ''
                    ],
                    'idempotency_key'           => 'batch_abc123'
                ],
            ],
        ],
        'response'                                  => [
            'content'                               => [
                'entity'                            => 'collection',
                'count'                             => 1,
                'items'                             => [
                    [
                        'entity'                    => 'payout',
                        'fund_account'              => [
                            'entity'                => 'fund_account',
                            'account_type'          => 'bank_account',
                            'bank_account'          => [
                                'ifsc'              => 'ICIC0000104',
                                'bank_name'         => 'ICICI Bank',
                                'name'              => 'Mehul Kaushik',
                                'account_number'    => '3434000111000',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
                        'transaction'               => [
                            'entity'                => 'transaction',
                            'account_number'        => '2224440041626905',
                            'amount'                => 690,
                            'currency'              => 'INR',
                            'credit'                => 0,
                            'debit'                 => 690,
                            'balance'               => 9999310
                        ],
                        'fees'                      => 590,
                        'tax'                       => 90,
                        'status'                    => 'processing',
                        'purpose'                   => 'refund',
                        'utr'                       => null,
                        'user_id'                   => 'MerchantUser01',
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc123'
                    ]
                ]
            ],
        ],
    ],

    'testGetPrimaryBalance' => [
        'request'  => [
            'url'    => '/primary_balance',
            'method' => 'GET',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'balance'   => 1000000,
                'type'      => 'primary',
            ],
        ],
    ],

    'testSourceCreationInCaseOfCreateRequestSubmittedPayoutCreatedByVendorPayments' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],

            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                    [
                        'source_id'   => '100000000001sa',
                        'source_type' => 'vendor_payments',
                        'priority'    => 2,
                    ],
                ],
            ],
        ],
    ],

    'testFetchPayoutWithSourceIdAndSourceTypeOnProxyAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payouts',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchPayoutWithSourceIdAndSourceTypeOnPrivateAuth' => [
        'request'   => [
            'method' => 'GET',
            'url'    => '/payouts',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'source_id, source_type is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testCreatePayoutToCardsNotAllowed' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account_id' => 'fa_100000000002fa',
                'amount'          => 100,
                'mode'            => 'IMPS',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'payout'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Standalone payouts API is not supported for payouts to card numbers, please use the composite API',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_STANDALONE_PAYOUT_TO_CARDS_NOT_ALLOWED,
        ],
    ],

    'testCreatePayoutToRzpTokenisedCardWithInvalidTokenIin' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account_id' => 'fa_100000000002fa',
                'amount'          => 100,
                'mode'            => 'card',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'payout'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Fund account not supported for payout creation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDeleteCardMetaDataForPayoutService' => [
        'request'  => [
            'method'  => 'DELETE',
            'url'     => '/payouts_service/delete_card_metadata',
            'content' => [
                'vault_token' => 'pay_44f3d176b38b4cd2a588f243e3ff7b20',
                'card_id'     => 'card_1000000000card'
            ],
        ],
        'response' => [
            'content' => [
                "success" => true,
                'error'   => "",
            ],
        ],
    ],

    'testCreatePayoutToRzpTokenisedCardThroughBankRails' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account_id' => 'fa_100000000002fa',
                'amount'          => 100,
                'mode'            => 'IMPS',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'payout'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Payout mode is not supported for tokenised cards',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MODE_NOT_SUPPORTED_FOR_PAYOUT_TO_TOKENISED_CARDS,
        ],
    ],

    'testCreateM2PPayoutForDebitCardWithUpperCaseCardMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account' => [
                    "account_type" => "card",
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                    "card"         => [
                        "name"         => "Prashanth YV",
                        "number"       => "340169570990137",
                        "cvv"          => "212",
                        "expiry_month" => 10,
                        "expiry_year"  => 29,
                    ]
                ],
                'amount'          => 100,
                'mode'            => 'CARD',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card' => [
                        'last4'     =>  '0137',
                        'network'   =>  'MasterCard',
                        'type'      =>  'debit',
                        'issuer'    =>  'YESB',
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'contact'      => '9999999999',
                        'email'        => 'prashanth@razorpay.com',
                        'type'         => 'employee',
                        'reference_id' => null,
                        'batch_id'     => null,
                        'active'       => true,
                        'notes'        => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'amount'          => 100,
                'currency'        => 'INR',
                'status'          => 'processing',
                'purpose'         => 'payout',
                'mode'            => 'card',
            ],
        ],
    ],

    'testCreateM2PPayoutForDebitCardWithLowerCaseCardMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account' => [
                    "account_type" => "card",
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                    "card"         => [
                        "name"         => "Prashanth YV",
                        "number"       => "340169570990137",
                        "cvv"          => "212",
                        "expiry_month" => 10,
                        "expiry_year"  => 29,
                    ]
                ],
                'amount'          => 100,
                'mode'            => 'card',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card' => [
                        'last4'     =>  '0137',
                        'network'   =>  'MasterCard',
                        'type'      =>  'debit',
                        'issuer'    =>  'YESB',
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'contact'      => '9999999999',
                        'email'        => 'prashanth@razorpay.com',
                        'type'         => 'employee',
                        'reference_id' => null,
                        'batch_id'     => null,
                        'active'       => true,
                        'notes'        => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'amount'          => 100,
                'currency'        => 'INR',
                'status'          => 'processing',
                'purpose'         => 'payout',
                'mode'            => 'card',
            ],
        ],
    ],

    'testCreateM2PPayoutForDebitCardWithRandomCaseCardMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account' => [
                    "account_type" => "card",
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                    "card"         => [
                        "name"         => "Prashanth YV",
                        "number"       => "340169570990137",
                        "cvv"          => "212",
                        "expiry_month" => 10,
                        "expiry_year"  => 29,
                    ]
                ],
                'amount'          => 100,
                'mode'            => 'cARd',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card' => [
                        'last4'     =>  '0137',
                        'network'   =>  'MasterCard',
                        'type'      =>  'debit',
                        'issuer'    =>  'YESB',
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'contact'      => '9999999999',
                        'email'        => 'prashanth@razorpay.com',
                        'type'         => 'employee',
                        'reference_id' => null,
                        'batch_id'     => null,
                        'active'       => true,
                        'notes'        => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'amount'          => 100,
                'currency'        => 'INR',
                'status'          => 'processing',
                'purpose'         => 'payout',
                'mode'            => 'card',
            ],
        ],
    ],

    'testCreateM2PPayoutWithoutSupportedModes' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account_id' => 'fa_EIXgVWknyiroq6',
                'amount'          => 100,
                'mode'            => 'card',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'payout'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payout mode CARD is not supported for the fund account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateM2PPayoutForMerchantBlacklistedByProduct' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account' => [
                    "account_type" => "card",
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                    "card"         => [
                        "name"         => "Prashanth YV",
                        "number"       => "340169570990137",
                        "cvv"          => "212",
                        "expiry_month" => 10,
                        "expiry_year"  => 29,
                    ]
                ],
                'amount'          => 100,
                'mode'            => 'card',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'payout'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_FOR_PRODUCT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_FOR_PRODUCT,
        ],
    ],

    'testCreateM2PPayoutForMerchantBlacklistedByNetwork' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account' => [
                    "account_type" => "card",
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                    "card"         => [
                        "name"         => "Prashanth YV",
                        "number"       => "340169570990137",
                        "cvv"          => "212",
                        "expiry_month" => 10,
                        "expiry_year"  => 29,
                    ]
                ],
                'amount'          => 100,
                'mode'            => 'card',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'payout'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_BY_NETWORK,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_BY_NETWORK,
        ],
    ],

    'testCreatePayoutInternalWhenPayoutFeatureNotMapped' => [
        'request'  => [
            'url'     => '/payouts_internal',
            'method'  => 'post',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testCreatePayoutInternalWhenPayoutFeatureMapped' => [
        'request'  => [
            'url'     => '/payouts_internal',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'merchant_id',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'method'  => 'POST',
            'content' => [
                'account_number'  => '987654321000',
                'amount'          => 100000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                ],
            ],
        ],
        'response'  => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ],
                ],
            ],
        ]
    ],

    'testFailedWebhookPayoutResponseForNewBankingErrorOnLiveMode' => [
        'entity'   => 'event',
        'event'    => 'payout.failed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'failed',
                    'failure_reason' => 'Insufficient balance to process payout',
                    'error'          => [
                        'source' => 'business',
                        'reason' =>  'insufficient_funds',
                        'description' => 'Your account does not have enough balance to carry out the payout operation.'
                    ]
                ],
            ],
        ],
    ],

    'testBlockVAtoVAPayoutsWithYesbankDestinationAndFeatureEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testOnHoldPayoutForFeatureEnabledMerchantAndBeneDown' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testOnHoldPayoutForFeatureEnabledMerchantWithNoTestTransactionsAndBeneDown' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testFiringOfWebhooksAndEmailOnPayoutReversalWithoutUtr' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => 'Test for webhook and email firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => 'EgmjebcvYkSg3v',
                'source_type'         => 'payout',
                'status'              => 'FAILED',
                'utr'                 => null,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testCreateM2PPayoutForMerchantDirectAccountCardMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account' => [
                    "account_type" => "card",
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                    "card"         => [
                        "name"         => "Prashanth YV",
                        "number"       => "340169570990137",
                        "cvv"          => "212",
                        "expiry_month" => 10,
                        "expiry_year"  => 29,
                    ]
                ],
                'amount'          => 100,
                'mode'            => 'card',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'payout'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'RBL does not support card payouts to CARD',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testCreatePayoutViaAmazonPayFromDirectAccount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626906',
                'amount'          => 1000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'amazonpay',
                'fund_account_id' => 'fa_100000000003fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_AMAZONPAY_PAYOUT_NOT_ALLOWED_ON_DIRECT_ACCOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_AMAZONPAY_PAYOUT_NOT_ALLOWED_ON_DIRECT_ACCOUNT,
        ],
    ],
    'testCreatePayoutGreaterThanMaxAmount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 10000000001,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'RTGS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount may not be greater than ' . PayoutEntity::MAX_PAYOUT_LIMIT . '.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutGreaterThanMaxAmountForSettlementService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 300000000001,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'RTGS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' =>
                        'The amount may not be greater than ' . PayoutEntity::MAX_SETTLEMENT_PAYOUT_LIMIT . '.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutGreaterThanGlobalMaxAmountForSettlementService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 10000000001,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'RTGS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000000001,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'RTGS',
                'tax'             => 270,
                'fees'            => 1770,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testBeneBankDowntimeConfigSetup' => [
        'request' => [
            'url'    => '/fts/channel/notify',
            'method' => 'post',
            'content' => [
                "type" => "bene_health",
                "payload" => [
                    "begin"=> 1621201921,
                    "created_at" => 1621201921,
                    "end"=>1621202497,
                    "entity"=>"bene_health",
                    "id"=>"HBcQBczRgAD0I6",
                    "instrument"=>[
                        "bank"=> "HDFC"
                    ],
                    "method"=> ["IMPS"],
                    "scheduled"=> false,
                    "source"=> "BENEFICIARY",
                    "status"=> "started",
                    "updated_at"=> 1621202497
                ]
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTS channel notification processed successfully',
            ],
        ],
    ],

    'testBeneBankUptimeConfigSetup' => [
        'request' => [
            'url'    => '/fts/channel/notify',
            'method' => 'post',
            'content' => [
                "type" => "bene_health",
                "payload" => [
                    "begin"=> 1621201921,
                    "created_at" => 1621201921,
                    "end"=>1621202497,
                    "entity"=>"bene_health",
                    "id"=>"HBcQBczRgAD0I6",
                    "instrument"=>[
                        "bank"=> "HDFC"
                    ],
                    "method"=> ["IMPS"],
                    "scheduled"=> false,
                    "source"=> "BENEFICIARY",
                    "status"=> "resolved",
                    "updated_at"=> 1621202497
                ]
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTS channel notification processed successfully',
            ],
        ],
     ],

    'testAlternateFailureReasonForNewError' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'error'           => [
                    'description' => null,
                    'reason'      => null,
                    'source'      => null
                ]
            ],
        ],
    ],

    'testPayoutPricingForXpayroll'=>  [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 10000,
                'currency'        => 'INR',
                'purpose'         => 'payout',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'xpayroll',
                        'priority'    => 1,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'payout',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testQueuedPayoutPricingForXpayroll'=>  [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 10000,
                'currency'        => 'INR',
                'purpose'         => 'payout',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'queue_if_low_balance'  => 1,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'xpayroll',
                        'priority'    => 1,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'payout',
                'status'          => 'queued',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testPayoutPricingForNonXpayrollApp'=>  [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 10000,
                'currency'        => 'INR',
                'purpose'         => 'payout',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'payout',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testPayoutPricingForPrivateAuth'=>  [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 10000,
                'currency'        => 'INR',
                'purpose'         => 'payout',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'payout',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testPayoutCreateOnInternalContactByXpayroll'=> [
        'request'  => [
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method'  => 'POST',
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'payout',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => '',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'xpayroll',
                        'priority'    => 1,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'payout',
                'amount'    => 2000000,
                'currency'  => 'INR',
                'narration' => 'Batman',
                'purpose'   => 'payout',
                'status'    => 'processing',
                'mode'      => 'IMPS',
                'tax'       => 0,
                'fees'      => 0,
                'notes'     => [
                    'abc' => 'xyz',
                ],
            ]
        ],
    ],

    'testPayoutCreateOnXpayrollInternalContactByOtherAppFailure'=> [
        'request'  => [
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method'  => 'POST',
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'payout',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => '',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Creating a payout to an internal Razorpay Fund Account is not permitted',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_TO_INTERNAL_FUND_ACCOUNT_NOT_PERMITTED,
        ],
    ],

    'testCompositePayoutCreationViaNewCompositeFlow' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => '2000000',
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'UPI',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'vpa',
                    'vpa' => [
                        'address'  => 'mehulisa10xdev@razorpay',
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'payout',
                'amount'       => 2000000,
                'currency'     => 'INR',
                'narration'    => 'Batman',
                'purpose'      => 'refund',
                'status'       => 'processing',
                'mode'         => 'UPI',
                'tax'          => 0,
                'fees'         => 0,
                'notes'        => [
                    'abc' => 'xyz',
                ],
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'vpa',
                    'vpa' => [
                        'address'  => 'mehulisa10xdev@razorpay',
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'contact'      => '9999999999',
                        'email'        => 'prashanth@razorpay.com',
                        'type'         => 'employee',
                        'reference_id' => null,
                        'batch_id'     => null,
                        'active'       => true,
                        'notes'        => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateCompositePayoutWithOtpAndWithoutQueueIfLowBalanceInput' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/composite_payout_with_otp',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => '2000',
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'UPI',
                'otp'            => '0007',
                'token'          => 'BUIj3m2Nx2VvVj',
                'fund_account'   => [
                    'account_type' => 'vpa',
                    'vpa' => [
                        'address'  => 'test@ybl',
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'type'    => 'employee',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'payout',
                'amount'       => 2000,
                'currency'     => 'INR',
                'narration'    => 'Batman',
                'purpose'      => 'refund',
                'status'       => 'processing',
                'mode'         => 'UPI',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'vpa',
                    'vpa' => [
                        'address'  => 'test@ybl',
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'type'         => 'employee',
                        'reference_id' => 'test@ybl',
                        'batch_id'     => null,
                        'active'       => true,
                    ],
                ],
            ],
        ],
    ],


    'testCreateCompositePayoutWithOtpAndWithoutOtpInput' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/composite_payout_with_otp',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => '2000000',
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'UPI',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'vpa',
                    'vpa' => [
                        'address'  => 'mehulisa10xdev@razorpay',
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The otp field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testCompositePayoutCreationViaNewCompositeFlowV1ForPayoutsToCard' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => '2000000',
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'UPI',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'card',
                    "card"         => [
                        "name"         => "Prashanth YV",
                        "number"       => "340169570990137",
                        "cvv"          => "212",
                        "expiry_month" => 10,
                        "expiry_year"  => 29,
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'payout',
                'amount'       => 2000000,
                'currency'     => 'INR',
                'narration'    => 'Batman',
                'purpose'      => 'refund',
                'status'       => 'processing',
                'mode'         => 'UPI',
                'tax'          => 0,
                'fees'         => 0,
                'notes'        => [
                    'abc' => 'xyz',
                ],
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'card',
                    'card' => [
                    ],
                    'batch_id'     => null,
                    'active'       => true,
                    'contact'      => [
                        'entity'       => 'contact',
                        'name'         => 'Prashanth YV',
                        'contact'      => '9999999999',
                        'email'        => 'prashanth@razorpay.com',
                        'type'         => 'employee',
                        'reference_id' => null,
                        'batch_id'     => null,
                        'active'       => true,
                        'notes'        => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCompositePayoutCreationViaNewCompositeFlowFailsForInternalContact' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => '2000000',
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'UPI',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'vpa',
                    'vpa' => [
                        'address'  => 'mehulisa10xdev@razorpay',
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'rzp_fees',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
        ],
    ],

    'testProcessingOfCreateRequestSubmittedPayoutForHighTpsForDirectAccounts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'UPI',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'vpa',
                    'vpa' => [
                        'address'  => 'mehulisa10xdev@razorpay',
                    ],
                    'contact'      => [
                        'name'    => 'Prashanth YV',
                        'email'   => 'prashanth@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'High tps not supported for direct accounts',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
                'class'               => Exception\BadRequestException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testStatusDetailsInPayoutUpdatedWebhookForBeneficiaryBankConfirmationPendingRTGSMode' => [
        'entity' => 'event',
        'event'  => 'payout.updated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'processing',
                    'status_details'  => [
                        'reason' => 'beneficiary_bank_confirmation_pending',
                        'description' => 'Confirmation of credit to the beneficiary is pending from ICICI Bank. Please check the status after 09th November 2021, 11:45 PM'
                    ],
                ],
            ],
        ],
    ],

    'testStatusDetailsInPayoutUpdatedWebhookForBeneficiaryBankConfirmationPendingNEFTMode' => [
        'entity' => 'event',
        'event'  => 'payout.updated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'processing',
                    'status_details'  => [
                        'reason' => 'beneficiary_bank_confirmation_pending',
                        'description' => 'Confirmation of credit to the beneficiary is pending from HDFC Bank. Please check the status after 09th November 2021, 11:45 PM'
                    ],
                ],
            ],
        ],
    ],

    'testStatusDetailsInPayoutUpdatedWebhookForBeneficiaryBankConfirmationPendingIMPSMode' => [
        'entity' => 'event',
        'event'  => 'payout.updated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'processing',
                    'status_details'  => [
                        'reason' => 'beneficiary_bank_confirmation_pending',
                        'description' => 'Confirmation of credit to the beneficiary is pending from ICICI Bank. Please check the status after 09th November 2021, 09:13 PM'
                    ],
                ],
            ],
        ],
    ],

    'testStatusDetailsInPayoutUpdatedWebhookForBeneficiaryBankConfirmationPendingUPIMode' => [
        'entity' => 'event',
        'event'  => 'payout.updated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'processing',
                    'status_details'  => [
                        'reason' => 'beneficiary_bank_confirmation_pending',
                        'description' => 'Confirmation of credit to the beneficiary is pending from beneficiary bank. Please check the status after 09th November 2021, 09:13 PM'
                    ],
                ],
            ],
        ],
    ],

    'testStatusDetailsInPayoutUpdatedWebhookForBankWindowClosedNEFTMode' => [
        'entity' => 'event',
        'event'  => 'payout.updated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'processing',
                    'status_details'  => [
                        'reason' => 'bank_window_closed',
                        'description' => "The NEFT window for the day is closed. Please "
                                         ."check the status after 09th November 2021, 09:13 PM",
                    ],
                ],
            ],
        ],
    ],

    'testStatusDetailsInPayoutUpdatedWebhookForBankWindowClosedRTGSMode' => [
        'entity' => 'event',
        'event'  => 'payout.updated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'processing',
                    'status_details'  => [
                        'reason' => 'bank_window_closed',
                        'description' => "The RTGS window for the day is closed. Please "
                                          ."check the status after 10th November 2021, 12:33 AM",
                    ],
                ],
            ],
        ],
    ],

    'testStatusDetailsInPayoutUpdatedWebhookForPartnerBankPendingNEFTMode' => [
        'entity' => 'event',
        'event'  => 'payout.updated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'processing',
                    'status_details'  => [
                        'reason' => 'partner_bank_pending',
                        'description' => "Payout is being processed by our partner bank. Please "
                            ."check the final status after 10th November 2021, 12:33 AM",
                    ],
                ],
            ],
        ],
    ],

    'testStatusDetailsInPayoutUpdatedWebhookForPayoutProcessing' => [
        'entity' => 'event',
        'event'  => 'payout.updated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'processing',
                    'status_details'  => [
                        'reason' => 'payout_bank_processing',
                        'description' => 'Payout is being processed by our partner bank. Please check '
                                            ."the final status after some time"
                    ],
                ],
            ],
        ],
    ],

    'testStatusDetailsInPayoutUpdatedWebhookForNullCase' => [
        'entity' => 'event',
        'event'  => 'payout.updated',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity'         => 'payout',
                    'status'         => 'processing',
                    'status_details'  => [
                        'reason' => null,
                        'description' => null,
                    ],
                ],
            ],
        ],
    ],

    'testUnauthorisedAccessToRazorpayXResourcesWithOauth' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method'  => 'GET',
            'url'     => '/payouts',
            'content' => [
                'product' => 'banking',
                'count'   => 10,
                'expand'  => [
                    'fund_account.contact',
                    'user'
                ]
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_ACCESS_TO_RAZORPAYX_RESOURCE
                ]
            ],
            'status_code' => 401
        ]
    ],

    'testGetPayoutsWithBearerAuth' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method'  => 'GET',
            'url'     => '/payouts',
            'content' => [
                'product' => 'banking',
                'count'   => 10,
                'expand'  => [
                    'fund_account.contact',
                    'user'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' =>[
                    0 => [
                        'entity' => 'payout',
                        'fund_account_id' => 'fa_100000000000fa',
                        'fund_account' => [],
                        'amount' => 10000,
                        'currency' => 'INR',
                        'notes' =>
                            array (
                            ),
                        'fees' => 0,
                        'tax' => 0,
                        'status' => 'pending',
                        'purpose' => 'refund',
                        'utr' => NULL,
                        'user' => [],
                        'mode' => 'NEFT',
                        'workflow_history' => [],
                        'reference_id' => NULL,
                        'narration' => 'Test Merchant Fund Transfer',
                        'batch_id' => NULL,
                        'failure_reason' => NULL,
                        'fee_type' => NULL,
                        'scheduled_at' => NULL,
                        'merchant_id' => '10000000000000',
                    ],
                ],
            ],
        ],
        'expected_passport' => [
            'mode'          => 'live',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'merchant',
                'id'   => '10000000000000',
            ],
            'oauth' => [
                'owner_type' => 'merchant',
                'owner_id'   => '10000000000000',
                // 'client_id'  => '<CLIENT_ID>',
                // 'app_id'     => '<APP_ID>',
                'env'        => 'prod',
            ],
            'credential' => [
                'username'   => 'rzp_live_oauth_TheTestAuthKey',
                'public_key' => 'rzp_live_oauth_TheTestAuthKey',
            ],
            'roles' => [
                'oauth::scope::rx_read_write',
            ],
        ],
    ],

    'testPayoutCreateOnInternalContactByCapitalCollections'=> [
        'request'  => [
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method'  => 'POST',
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'payout',
                'mode'            => 'IMPS',
                'fund_account_id' => ''
            ],
            'source_details' => [
                [
                    'source_type' => 'capital_collections',
                    'source_id'   => 'Sk77UkNsB8ywa5',
                    'priority'    => 1
                ]
            ]
        ],
        'response' => [
            'content' => [
                'entity'    => 'payout',
                'amount'    => 2000000,
                'currency'  => 'INR',
                'narration' => 'Test Merchant Fund Transfer',
                'purpose'   => 'payout',
                'status'    => 'processing',
                'mode'      => 'IMPS',
                'tax'       => 162,
                'fees'      => 1062,
                'notes'     => []
            ]
        ],
    ],

    'testPayoutCreateOnCollectionsInternalContactByOtherAppFailure'=> [
        'request'  => [
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method'  => 'POST',
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'payout',
                'mode'            => 'IMPS',
                'fund_account_id' => '',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => ErrorCode::BAD_REQUEST_APP_NOT_PERMITTED_TO_CREATE_PAYOUT_ON_THIS_CONTACT_TYPE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutWithOtpBearerAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz'
                ],
            ],
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'merchant',
                'id'   => '10000000000000',
            ],
             'credential' => [
                'username'   => 'rzp_test_oauth_TheTestAuthKey',
                'public_key' => 'rzp_test_oauth_TheTestAuthKey',
            ],
            'mode'          => 'test',
            'oauth' => [
                'owner_type' => 'merchant',
                'owner_id'   => '10000000000000',
                // 'client_id'  => '<CLIENT_ID>',
                // 'app_id'     => '<APP_ID>',
                'env'        => 'dev',
            ],
            'roles' => [
                    'oauth::scope::read_write',
            ],
        ]
    ],

    'testUpdatePayout' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts_service/payout/Gg7sgBZgvYjlSB/update',
            'content' => [
                "status"               => "pending",
                "merchant_id"          => "10000000000000"
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'SUCCESS',
                'error'  => null
            ],
        ],
    ],

    'testCreateVaToVaPayoutInLedgerReverseShadowMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '7878780111222',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processed',
                'mode'            => 'IFT',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateAndProcessQueuedPayoutWithLowMerchantBalanceInLedgerReverseShadowMode' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 10000001,
                'currency'              => 'INR',
                'mode'                  => 'IMPS',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'queue_if_low_balance'  => true,
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000001,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'utr'             => null,
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [],
            ],
        ],
    ],

    'testPayoutWithJournalLedgerCronInLedgerReverseShadow' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/ledger_service/create_journal_cron',
            'content'   => [
                'entity'        => 'payout',
            ]
        ],
        'response' => [
            'content' => [
                'success',
            ],
        ],
    ],

    'testPayoutWithJournalLedgerCronInLedgerReverseShadowWithWhitelistIds' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/ledger_service/create_journal_cron',
            'content'   => [
                'whitelist_ids' => ['IwHCToefEWVgpi'],
                'entity'        => 'payout',
            ]
        ],
        'response' => [
            'content' => [
                'success',
            ],
        ],
    ],

    'testPayoutProcessedWithJournalLedgerCronInLedgerReverseShadowWithWhitelistIds' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/ledger_service/create_journal_cron',
            'content'   => [
                'whitelist_ids' => ['IwHCToefEWVgpi'],
                'entity'        => 'payout',
            ]
        ],
        'response' => [
            'content' => [
                'success',
            ],
        ],
    ],

    'testPayoutReversalWithJournalLedgerCronInLedgerReverseShadow' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/ledger_service/create_journal_cron',
            'content'   => [
                'entity'        => 'reversal',
            ]
        ],
        'response' => [
            'content' => [
                'success',
            ],
        ],
    ],

    'testPayoutReversalWithJournalLedgerCronInLedgerReverseShadowWithPseudoReversals' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/ledger_service/create_journal_cron',
            'content'   => [
                'entity'        => 'reversal',
            ]
        ],
        'response' => [
            'content' => [
                'success',
            ],
        ],
    ],

    'testUpdateMerchantSlaForOnHoldPayoutsInsufficientPermission' => [
        'request' => [
            'method'    => 'PUT',
            'url'       => '/payouts/merchant_on_hold_slas',
            'content'   => [
                '10'        => ["90000merchant1", "90000merchant3"],
                '20'        => ["90000merchant2", "90000merchant4"],
            ]
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access Denied',
                ],
            ]
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testUpdateMerchantSlaForOnHoldPayoutsSuccess' => [
        'request' => [
            'method'    => 'PUT',
            'url'       => '/payouts/merchant_on_hold_slas',
            'content'   => [
                '10'        => ["90000merchant1", "90000merchant3"],
                '20'        => ["90000merchant2", "90000merchant4"],
            ]
        ],
        'response' => [
            'content' => [
                'success'  =>  true,
            ],
        ],
    ],

    'testUpdateMerchantSlaForOnHoldPayoutsMissingPayload' => [
        'request' => [
            'method'    => 'PUT',
            'url'       => '/payouts/merchant_on_hold_slas',
            'content'   => []
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'There should be atleast one SLA => merchantIds key value pair in request body',
                ],
            ]
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateMerchantSlaForOnHoldPayoutsInvalidSla' => [
        'request' => [
            'method'    => 'PUT',
            'url'       => '/payouts/merchant_on_hold_slas',
            'content'   => [
                'abc-invalid-sla' => ['90000merchant1']
            ]
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'sla value should be an integer greater than 0',
                ],
            ]
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateMerchantSlaForOnHoldPayoutsEmptyMerchantIdList' => [
        'request' => [
            'method'    => 'PUT',
            'url'       => '/payouts/merchant_on_hold_slas',
            'content'   => [
                '10' => []
            ]
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The value for a SLA should be a non-empty list of merchantIds',
                ],
            ]
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateMerchantSlaForOnHoldPayoutsInvalidMerchantId' => [
        'request' => [
            'method'    => 'PUT',
            'url'       => '/payouts/merchant_on_hold_slas',
            'content'   => [
                '10' => ['']
            ]
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'merchantId should be a non-empty string',
                ],
            ]
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateMerchantSlaForOnHoldPayoutsNonUniqueMerchantIds' => [
        'request' => [
            'method'    => 'PUT',
            'url'       => '/payouts/merchant_on_hold_slas',
            'content'   => [
                '10' => ['m1'],
                '20' => ['m1']
            ]
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'all merchantIds should be a unique',
                ],
            ]
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateMerchantSlaForOnHoldPayoutsUnknownMerchantIds' => [
        'request' => [
            'method'    => 'PUT',
            'url'       => '/payouts/merchant_on_hold_slas',
            'content'   => [
                '10' => ['m1'],
            ]
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'merchantId: m1 is not found in database',
                ],
            ]
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testCohesiveCreatePayoutWithTdsFailsForPrivateAuth' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'tds'             => [
                    'category_id' => 1,
                    'amount'      => 1000
                ],
                'subtotal_amount' => 500,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Payout with TDS not supported via private auth',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_AUTH_NOT_SUPPORTED_FOR_PAYOUT_WITH_TDS,
        ],
    ],

    'testCohesiveCreatePayoutWithTdsPayoutToBeQueuedFailsForPrivateAuth' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => true,
                'tds'                  => [
                    'category_id' => 1,
                    'amount'      => 1000
                ],
                'subtotal_amount'      => 500,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Payout with TDS not supported via private auth',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_AUTH_NOT_SUPPORTED_FOR_PAYOUT_WITH_TDS,
        ],
    ],

    'testCohesiveCreatePayoutWithAttachmentsFailsForPrivateAuth' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'attachments'     => [
                    [
                        'file_id'   => 'file_testing',
                        'file_name' => 'not-your-attachment.pdf'
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Payout with attachments not supported via private auth',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_AUTH_NOT_SUPPORTED_FOR_PAYOUT_WITH_ATTACHMENTS,
        ],
    ],

    'testCohesiveCreatePayoutWithoutTdsSuccessForPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ]
        ],
    ],

    'testCohesiveCreatePayoutWithoutTdsPayoutToBeQueuedSuccessForPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => true,
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ]
        ],
    ],

    'testCohesiveCreatePayoutWithoutAttachmentSuccessForPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ]
        ],
    ],

    'testCohesiveCreateCompositePayoutWithTdsForPrivateAuth' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'test',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'fund_account'    => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Name of account holder',
                        'ifsc'           => 'ICIC0000104',
                        'account_number' => '3434000111000'
                    ],
                    'contact'      => [
                        'name'    => 'contact name',
                        'email'   => 'contact@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'tds'             => [
                    'category_id' => 1,
                    'amount'      => 100,
                ],
                'subtotal_amount' => 150,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Payout with TDS not supported via private auth',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_AUTH_NOT_SUPPORTED_FOR_PAYOUT_WITH_TDS,
        ],
    ],

    'testCohesiveCreateCompositePayoutWithAttachmentForPrivateAuth' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'test',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Name of account holder',
                        'ifsc'           => 'ICIC0000104',
                        'account_number' => '3434000111000'
                    ],
                    'contact'      => [
                        'name'    => 'contact name',
                        'email'   => 'contact@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'attachments'    => [
                    [
                        'file_id'   => 'file_testing',
                        'file_name' => 'not-your-attachment.pdf'
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Payout with attachments not supported via private auth',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_AUTH_NOT_SUPPORTED_FOR_PAYOUT_WITH_ATTACHMENTS,
        ],
    ],

    'testCohesiveCreateCompositePayoutWithoutTdsForPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'test',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
                'fund_account'   => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Name of account holder',
                        'ifsc'           => 'ICIC0000104',
                        'account_number' => '3434000111000'
                    ],
                    'contact'      => [
                        'name'    => 'contact name',
                        'email'   => 'contact@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'payout',
                'amount'   => 2000000,
                'currency' => 'INR',
                'purpose'  => 'refund',
                'mode'     => 'IMPS',
                'notes'    => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCohesiveCreateCompositePayoutWithTdsPayoutToBeQueuedForPrivateAuth' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'test',
                'mode'                 => 'IMPS',
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'fund_account'         => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Name of account holder',
                        'ifsc'           => 'ICIC0000104',
                        'account_number' => '3434000111000'
                    ],
                    'contact'      => [
                        'name'    => 'contact name',
                        'email'   => 'contact@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'tds'                  => [
                    'category_id' => 1,
                    'amount'      => 100,
                ],
                'subtotal_amount'      => 150,
                'queue_if_low_balance' => true,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Payout with TDS not supported via private auth',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_AUTH_NOT_SUPPORTED_FOR_PAYOUT_WITH_TDS,
        ],
    ],

    'testCohesiveCreateCompositePayoutWithoutTdsPayoutToBeQueuedForPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'test',
                'mode'                 => 'IMPS',
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'fund_account'         => [
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => 'Name of account holder',
                        'ifsc'           => 'ICIC0000104',
                        'account_number' => '3434000111000'
                    ],
                    'contact'      => [
                        'name'    => 'contact name',
                        'email'   => 'contact@razorpay.com',
                        'contact' => '9999999999',
                        'type'    => 'employee',
                        'notes'   => [
                            'note_key' => 'note_value'
                        ],
                    ],
                ],
                'queue_if_low_balance' => true,
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'payout',
                'amount'   => 2000000,
                'currency' => 'INR',
                'purpose'  => 'refund',
                'mode'     => 'IMPS',
                'notes'    => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCohesiveCreatePayoutWithTdsSuccessForProxyAuthTdsCategoriesInCache' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp'             => '0007',
                'token'           => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'tds'             => [
                    'category_id' => 1,
                    'amount'      => 1000
                ],
                'subtotal_amount' => 10000,
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 270,
                'fees'            => 1770,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'meta'            => [
                    'tds'             => [
                        'category_id' => 1,
                        'amount'      => 1000
                    ],
                    'subtotal_amount' => 10000,
                ]
            ],
        ],
    ],

    'testCohesiveCreatePayoutWithAttachmentSuccessForProxyAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp'             => '0007',
                'token'           => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'attachments'     => [
                    [
                        'file_id'   => 'file_testing',
                        'file_name' => 'not-your-attachment.pdf'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 270,
                'fees'            => 1770,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'meta'            => [
                    'attachments' => [
                        [
                            'file_id'   => 'file_testing',
                            'file_name' => 'not-your-attachment.pdf'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCohesiveCreatePayoutWithoutTdsSuccessForProxyAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp'             => '0007',
                'token'           => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 270,
                'fees'            => 1770,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCohesiveCreatePayoutWithTdsMissingTdsAmountForProxyAuth' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp'             => '0007',
                'token'           => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'tds'             => [
                    'category_id' => 1
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCohesiveCreatePayoutWithTdsMissingTdsCategoryIdForProxyAuth' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp'             => '0007',
                'token'           => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'tds'             => [
                    'amount' => 100,
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The category id field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCohesiveCreatePayoutWithTdsTdsAmountMoreThanPayoutAmountForProxyAuth' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 200,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp'             => '0007',
                'token'           => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'tds'             => [
                    'category_id' => 1,
                    'amount'      => 1000
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_TDS_AMOUNT_GREATER_THAN_PAYOUT_AMOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_TDS_AMOUNT_GREATER_THAN_PAYOUT_AMOUNT,
        ],
    ],

    'testCohesiveCreatePayoutWithTdsIncorrectTdsCategoryIdForProxyAuth' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 20000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp'             => '0007',
                'token'           => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'tds'             => [
                    'category_id' => 5,
                    'amount'      => 1000
                ],
                'subtotal_amount' => 500,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_TDS_CATEGORY_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_TDS_CATEGORY_ID,
        ],
    ],

    'testCohesiveCreatePayoutWithTdsPayoutToBeQueuedForProxyAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 50000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'otp'                  => '0007',
                'token'                => 'BUIj3m2Nx2VvVj',
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => true,
                'tds'                  => [
                    'category_id' => 1,
                    'amount'      => 1000
                ],
                'subtotal_amount'      => 10000,
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 270,
                'fees'            => 1770,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'meta'            => [
                    'tds'             => [
                        'category_id' => 1,
                        'amount'      => 1000
                    ],
                    'subtotal_amount' => 10000,
                ]
            ],
        ],
    ],

    'testCohesiveCreatePayoutWithoutTdsPayoutToBeQueuedForProxyAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 50000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'otp'                  => '0007',
                'token'                => 'BUIj3m2Nx2VvVj',
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => true,
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'tax'             => 270,
                'fees'            => 1770,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCohesiveCreatePayoutWithTdsIncorrectTdsCategoryIdForInternalAuth' => [
        'request'   => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
                'tds'             => [
                    'category_id' => 5,
                    'amount'      => 1000
                ],
                'subtotal_amount' => 10000,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_TDS_CATEGORY_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_TDS_CATEGORY_ID,
        ],
    ],

    'testCohesiveCreatePayoutWithTdsMissingTdsCategoryIdForInternalAuth' => [
        'request'   => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp'             => '0007',
                'token'           => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
                'tds'             => [
                    'amount' => 1000
                ],
                'subtotal_amount' => 10000,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The category id field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCohesiveCreatePayoutWithTdsMissingTdsAmountForInternalAuth' => [
        'request'   => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp'             => '0007',
                'token'           => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
                'tds'             => [
                    'category_id' => 1
                ],
                'subtotal_amount' => 10000,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCohesiveCreatePayoutWithTdsTdsAmountMoreThanPayoutAmountForInternalAuth' => [
        'request'   => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 25000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp'             => '0007',
                'token'           => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
                'tds'             => [
                    'category_id' => 1,
                    'amount'      => 50000,
                ],
                'subtotal_amount' => 10000,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_TDS_AMOUNT_GREATER_THAN_PAYOUT_AMOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_TDS_AMOUNT_GREATER_THAN_PAYOUT_AMOUNT,
        ],
    ],

    'testCohesiveCreatePayoutWithTdsSuccessForInternalAuth' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
                'tds'             => [
                    'category_id' => 1,
                    'amount'      => 1000
                ],
                'subtotal_amount' => 10000,
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
                'meta'            => [
                    'tds'             => [
                        'category_id' => 1,
                        'amount'      => 1000
                    ],
                    'subtotal_amount' => 10000,
                ]
            ],
        ],
    ],

    'testCohesiveCreatePayoutWithAttachmentSuccessForInternalAuth' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
                'attachments'     => [
                    [
                        'file_id'   => 'file_testing',
                        'file_name' => 'not-your-attachment.pdf'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
            ],
        ],
    ],

    'testCohesiveCreatePayoutWithoutTdsSuccessForInternalAuth' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
            ],
        ],
    ],

    'testCohesiveCreatePayoutWithTdsPayoutToBeQueuedForInternalAuth' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'origin'               => 'dashboard',
                'source_details'       => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
                'queue_if_low_balance' => true,
                'tds'                  => [
                    'category_id' => 1,
                    'amount'      => 1000
                ],
                'subtotal_amount'      => 10000,
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
            ],
        ],
    ],

    'testCohesiveCreatePayoutWithoutTdsPayoutToBeQueuedForInternalAuth' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'origin'               => 'dashboard',
                'source_details'       => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
                'queue_if_low_balance' => true,
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'source_details'  => [
                    [
                        'source_id'   => '100000000000sa',
                        'source_type' => 'payout_links',
                        'priority'    => 1,
                    ]
                ],
            ],
        ],
    ],

    'testCohesiveFetchPayoutByIdForPayoutWithoutTdsForProxyAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payouts/pout_ID',
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 270,
                'fees'            => 1770,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'origin'          => 'dashboard',
                'tds'             => null,
                'total_amount'    => null,
            ],
        ],
    ],

    'testCohesiveFetchPayoutByIdForPayoutWithoutTdsForPrivateAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payouts/pout_ID',
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 270,
                'fees'            => 1770,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCohesiveFetchMultiplePayoutsWithTdsCategoryIdFilterForPrivateAuth' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/payouts?tds_category_id=1&account_number=2224440041626905',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'tds_category_id is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testCohesiveFetchMultiplePayoutsWithIncorrectTaxPaymentPublicIdFilterForProxyAuth' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/payouts?tax_payment_id=tx_1234&account_number=2224440041626905',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Invalid tax_payment_id',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_TAX_PAYMENT_ID,
        ],
    ],

    'testCreationOfTestPayoutsForDetectingFundLoadingDowntimeICICI' => [
        'request' => [
            'method' => 'post',
            'url' => '/payouts/test/downtime_detection_icici',
            'content' => [
                'account_number' => '2244240041626905',
                'amount' => 100,
                'currency' => 'INR',
                'purpose' => 'payout',
                'narration' => 'ICICI Test Payout',
                'modes' => ['NEFT'],
                'fund_account_id' => 'fa_D6Z9Jfir2egAUT',
                'notes' => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                [
                  'mode' => 'NEFT',
                  'status' => 'created',
                    'fund_account_id' => 'D6Z9Jfir2egAUT',
                    'narration' => 'ICICI Test Payout',
                ],
            ],
        ],
    ],

    'testCreationOfTestPayoutsForDetectingFundLoadingDowntimeYESB' => [
        'request' => [
            'method' => 'post',
            'url' => '/payouts/test/downtime_detection_yesb',
            'content' => [
                'account_number' => '2223330041626905',
                'amount' => 100,
                'currency' => 'INR',
                'purpose' => 'payout',
                'narration' => 'YESB Test Payout',
                'modes' => ['IMPS'],
                'fund_account_id' => 'fa_D6Z9Jfir2egAUT',
                'notes' => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                [
                    'mode' => 'IMPS',
                    'status' => 'created',
                    'fund_account_id' => 'D6Z9Jfir2egAUT',
                    'narration' => 'YESB Test Payout',
                ],
            ],
        ],
    ],

    'testFundLoadingDowntimeDetectionICICI' => [
        'request' => [
            'method' => 'post',
            'url' => '/payouts/test/check_status',
            'content' => [
                'modes' => ['NEFT'],
            ],
        ],
        'response' => [
            'content' => [
                [
                    [
                        'bank' => 'ICICI',
                        'status' => [
                            'mode' => 'IFT',
                            'is_downtime_detected' => true,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFundLoadingDowntimeDetectionYESB' => [
        'request' => [
            'method' => 'post',
            'url' => '/payouts/test/check_status',
            'content' => [
                'modes' => ['IMPS'],
            ],
        ],
        'response' => [
            'content' => [
                [
                    [
                        'bank' => 'ICICI',
                        'mode' => 'IFT',
                        'status' => [
                          'message' => 'No test payout found',
                        ],

                    ],
                ],
            ],
        ],
    ],

    'testAddingBalanceToSourceForTestMerchant' => [
        'request' => [
          'method' => 'post',
          'url' => '/payouts/test/add_balance_to_source',
          'content' => [
              'account_number' => '7878780111000',
              'amount' => 1728000,
              'currency' => 'INR',
              'purpose' => 'payout',
              'narration' => ' Adding balance',
              'mode' => 'IMPS',
              'fund_account_id' => 'fa_D6Z9Jfir2egAUQ',
              'notes' => [
                  'abc' => 'xyz',
              ],
          ],
        ],
        'response' => [
            'content' => [
                'message' => 'Balance added to source account successfully',
                ]
            ],
    ],

    'testPayoutProcessFailureInFtsStatusUpdate' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1234567,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => '10000000000000',
                'source_type'         => 'payout',
                'status'              => 'processed',
                'utr'                 => 11111111121,
                'source_account_id'   => 111111111,
                'bank_account_type'   => 'current',
                'channel'             => 'hello',
            ]
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid channel name: hello',
                ],
            ]
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCohesiveCreatePayoutWithoutTdsPayoutToBeQueuedFalseForPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'notes'                => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => false,
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ]
        ],
    ],

    'testUpdateAttachmentWithProxyAuth'                                     => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => 'payouts/pout_JLYXwEbdcktqV1/attachments',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'SUCCESS'
            ]
        ],
    ],

    'testUpdateAttachmentWithPayoutDetailsAndAdditionalInfoNull'                                     => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => 'payouts/pout_JLYXwEbdcktqV1/attachments',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'SUCCESS'
            ]
        ],
    ],

    'testUpdateAttachmentWithPayoutDetailsAndAdditionalInfoNotNull'                                     => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => 'payouts/pout_JLYXwEbdcktqV1/attachments',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'SUCCESS'
            ]
        ],
    ],

    'testUpdateAttachmentWithTds'                                           => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => 'payouts/pout_JLYXwEbdcktqV1/attachments',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'SUCCESS'
            ]
        ],
    ],
    'testUpdateAttachmentForPayoutLink'                                     => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts_internal/attachments',
            'content' => [
                "attachments" => [
                    [
                        "file_id"   => "file_JLYYnaOtQ0Xgzt",
                        "file_name" => "new file.pdf",
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'SUCCESS'
            ]
        ],
    ],
    'testUpdateAttachmentPayoutLinkWithoutPayoutDetails'                    => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts_internal/attachments',
            'content' => [
                "attachments" => [
                    [
                        "file_id"   => "file_JLYYnaOtQ0Xgzt",
                        "file_name" => "new file.pdf",
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'SUCCESS'
            ]
        ],
    ],

    'testCohesiveUploadAndCreatePayoutWithDifferentAttachmentFailureFlow' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp' =>'0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Invalid file_hash for attachment',
                    ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_FILE_HASH_FOR_ATTACHMENT,
        ],
    ],

    'testDownloadAttachmentsInPayoutReportWithInvalidTimeRangeType' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'url'     => '/payouts/attachments/download',
            'content' => [
                'account_number'  => '2224440041626905',
                'from' => 'randomstring',
                'to'   => 'randomstring',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'from must be an integer.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCohesiveUploadAndUpdatePayoutWithDifferentAttachmentFailFlow' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts/{id}/attachments',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Invalid file_hash for attachment',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_FILE_HASH_FOR_ATTACHMENT,
        ],
    ],

    'testDownloadAttachmentsInPayoutReportWithInvalidTimeRange' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'url'     => '/payouts/attachments/download',
            'content' => [
                'account_number'  => '2224440041626905',
                'from' => 1,
                'to'   => 1,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'from must be between 946684800 and 4765046400',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCohesiveUploadAndCreatePayoutWithFileHashMissingFailFlow' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 50000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'otp' =>'0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'attachments' => [
                    [
                        'file_id'   => 'file_JLYYnaOtQ0Xgzt',
                        'file_name' => 'new file.pdf',
                    ]
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'file_hash missing for attachment',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FILE_HASH_MISSING_FOR_ATTACHMENT,
        ],
    ],

    'testCohesiveUploadAndUpdatePayoutWithFileHashMissingFailFlow' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'     => '/payouts/{id}/attachments',
            'content' => [
                'attachments' => [
                    [
                        'file_id'   => 'file_JLYYnaOtQ0Xgzt',
                        'file_name' => 'new file.pdf',
                    ]
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'file_hash missing for attachment',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FILE_HASH_MISSING_FOR_ATTACHMENT,
        ],
    ],

    'testDownloadAttachmentsInPayoutReportWithNoAttachments' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'url'     => '/payouts/attachments/download',
            'content' => [
                'account_number'  => '2224440041626905',
                'from' => 1621201921,
                'to'   => 1621202497,
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testDownloadAttachmentsInPayoutReportWithAttachmentsForMultiplePayoutIds' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'url'     => '/payouts/attachments/download',
            'content' => [
                'account_number'  => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testDownloadAttachmentsInPayoutReportWithAttachments' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'url'     => '/payouts/attachments/download',
            'content' => [
                'account_number'  => '2224440041626905',
                'from' => Carbon::now(Timezone::IST)->subDays(1)->getTimestamp(),
                'to'   => Carbon::now(Timezone::IST)->getTimestamp(),
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testEmailAttachmentsInPayoutReportViaMetro' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'url'     => '/payouts/attachments/download',
            'content' => [
                'account_number'  => '2224440041626905',
                'send_email' => true,
                'receiver_email_ids' => ['abc@gmail.com'],
                'from' => 1621201921,
                'to'   => 1621202497,
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testEmailAttachmentsInPayoutReportViaSQS' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'url'     => '/payouts/attachments/download',
            'content' => [
                'account_number'  => '2224440041626905',
                'send_email' => true,
                'receiver_email_ids' => ['abc@gmail.com'],
                'from' => 1621201921,
                'to'   => 1621202497,
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testEmailAttachmentsInPayoutReportWithoutEmailIds' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'url'     => '/payouts/attachments/download',
            'content' => [
                'account_number'  => '2224440041626905',
                'send_email' => true
            ],
        ],
        'response' => [
            'content' => [
                'zip_file_id' => '',
                'message' => 'No receiver email found for Payout report'
            ],
        ],
    ],

    'testEmailAttachmentsInPayoutReportWithEmailIds' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'url'     => '/payouts/attachments/download',
            'content' => [
                'account_number'  => '2224440041626905',
                'send_email' => true,
                'receiver_email_ids' => ['abc@gmail.com'],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testDataMigrationOnHoldToProcessed' => [
        'request' => [
            'method' => 'POST',
            'url'     => '/payout_service_data_migration',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'dispatch_count' => 1
            ]
        ]
    ],

    'testDualWriteForPayoutServicePayout' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payouts_service/dual_write',
            'content' => [
                'payout_id' => 'randomid111111',
                'timestamp' => 946684801
            ]
        ],
        'response' => [
            'content' => [
                'status' => 'success'
            ]
        ]
    ],

    'testODBalanceCheckForPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 20000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true,
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'fund_account_id' => 'fa_100000000000fa',
                'amount'          => 20000,
                'currency'        => 'INR',
                'notes'           => [
                    'abc' => 'xyz'
                ],
                'status'          => 'processing',
                'purpose'         => 'refund',
                'utr'             => null,
                'mode'            => 'IMPS',
                'reference_id'    => null,
                'narration'       => 'Batman',
                'batch_id'        => null,
                'failure_reason'  => NULL,
            ],
        ],
    ],

    'testPayoutCreateAndProcessWith404ResponseForLedgerInLedgerReverseShadowMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => '2000000',
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'entity'          => 'payout',
                'fund_account_id' => 'fa_100000000000fa',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'notes'           => [
                    'abc' => 'xyz'
                ],
                'status'          => 'processing',
                'purpose'         => 'refund',
                'utr'             => null,
                'mode'            => 'IMPS',
                'reference_id'    => null,
                'narration'       => 'Batman',
                'batch_id'        => null,
                'failure_reason'  => NULL,
            ],
        ],
    ],

    'testPayoutCreateWithQueueIfLowBalanceFlagAndProcessWithInsufficientBalanceResponseInLedgerRS' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'queue_if_low_balance' => 1,
                'account_number'       => '2224440041626905',
                'amount'               => '2000000',
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'entity'          => 'payout',
                'fund_account_id' => 'fa_100000000000fa',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'notes'           => [
                    'abc' => 'xyz'
                ],
                'status'          => 'queued',
                'purpose'         => 'refund',
                'utr'             => null,
                'mode'            => 'IMPS',
                'reference_id'    => null,
                'narration'       => 'Batman',
                'batch_id'        => null,
                'failure_reason'  => NULL,
            ],
        ],
    ],

    'testPayoutCreateWithQueueIfLowBalanceFlagNotSetAndProcessWithInsufficientBalanceResponseInLedgerRS' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => '2000000',
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the payout operation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
        ],
    ],

    'testPayoutsBlockedFromMasterMerchantSharedAccount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payouts not supported for the debit account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPayoutApproveForCallbackFromNewWFSWhenAsyncNotEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts_internal/{id}/approve',
            'content' => [
                'queue_if_low_balance'  => false,
                'type' => 'workflow_callbacks_approved',
            ],
        ],
        'response' => [
            'content' => [
                "entity" => "payout",
            ],
        ],
    ],

    'testPayoutApproveForCallbackFromNewWFSWhenAsyncEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/payouts_internal/{id}/approve',
            'content' => [
                'queue_if_low_balance'  => false,
                'type' => 'workflow_callbacks_approved',
            ],
        ],
        'response' => [
            'content' => [
                "entity" => "payout",
            ],
        ],
    ],
    'testPartnerBankOnHoldPayoutForDirectAccount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'payout',
                'notes'           => [
                    'abc'         => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'status'          => 'queued',
                'mode'            => 'IMPS',
                'notes'           => [
                    'abc'         => 'xyz',
                ],
            ],
        ],
    ],
    'testPartnerBankOnHoldPayoutForDirectAccountWithExcludeMerchant' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'payout',
                'notes'           => [
                    'abc'         => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc'         => 'xyz',
                ],
            ],
        ],
    ],
    'testPartnerBankOnHoldPayoutForDirectAccountWithRazorxOff' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'payout',
                'notes'           => [
                    'abc'         => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc'         => 'xyz',
                ],
            ],
        ],
    ],
    'testProcessPartnerBankOnHoldPayoutForDirectAccount' => [
        'request'  => [
            'method'    => 'POST',
            'url'       => '/payouts/onhold/process/downtime',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
    'testFailOnHoldPayoutsWhenSlaBreachedForDirectAccount' => [
        'request'  => [
            'method'    => 'POST',
            'url'       => '/payouts/onhold/process/downtime',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
    'testProcessPartnerBankOnHoldPayoutAndMoveToBeneBankDowntime' => [
        'request'  => [
            'method'    => 'POST',
            'url'       => '/payouts/onhold/process/downtime',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testDashboardSummaryWithPartnerBankOnHoldPayout' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payouts/_meta/summary',
        ],
        'response' => [
            'content' => [
                'bacc_xba00000000000' => [
                    'queued' =>  [
                        'gateway_degraded' => [
                            'balance'       => 10000000,
                            'count'         => 1,
                            'total_amount'  => 2000000,
                            'total_fees'    => 0,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateSubAccountPayoutWithNonZeroPricing' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payout failed. Contact support for help.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubAccountPayoutWithInactiveMasterBASD' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payouts not supported for the debit account.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetAttachmentSignedUrlForPayoutOnlyPresentOnPayoutService' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payouts/{payout_id}/attachment/{attachment_id}',
        ],
        'response' => [
            'content' => [
                'id'            => 'file_testing',
                'type'          => 'delivery_proof',
                'name'          => 'myfile2.pdf',
                'bucket'        => 'test_bucket',
                'mime'          => 'text/csv',
                'extension'     => 'csv',
                'merchant_id'   => '10000000000000',
                'store'         => 's3',
                'signed_url'    => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf'
            ],
        ],
    ],

    'testGetAttachmentSignedUrlForAttachmentNotLinkedToPayout' => [
        'request'   => [
            'method' => 'GET',
            'url'    => '/payouts/{payout_id}/attachment/{attachment_id}',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'BAD_REQUEST_ATTACHMENT_NOT_LINKED_TO_PAYOUT',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description'         => 'Attachment not linked to payout',
        ],
    ],

    'testBulkTemplatesIncorrectInputKey' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/batch/template',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'random_key'  => 'random-value',
                'file_extension'  => 'csv',
                'payout_method' => 'amazonpay',
                'beneficiary_info' => 'details'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'random_key is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testBulkTemplatesIncorrectInputValue' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/batch/template',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'file_extension'  => 'random',
                'payout_method' => 'amazonpay',
                'beneficiary_info' => 'details'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'The selected file extension is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testBulkTemplatesSuccess' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/batch/template',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'file_extension'  => 'csv',
                'payout_method' => 'amazonpay',
                'beneficiary_info' => 'details'
            ],
        ],
        'response' => [
            'content' => [
                'signed_url' => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf'
            ],
            'status_code' => 200
        ],
    ],

    'testGetBatchRows' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/batch/batch_abcd/rows',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'count'         => 3,
                'skip'          => 0,
                'order_by'      => 'created_at',
                'order_type'    => 'DESC'
            ],
        ],
        'response' => [
            'content' => [
                'count' => 3,
                'type'  => 'java.util.Collection',
                'data'  => [
                    [
                        'created_at'        => 1681121569,
                        'updated_at'        => 1681121569,
                        'id'                => 'Lc3FCR5UkORots',
                        'batch_id'          => 'Lc3D38BgW3c77T',
                        'sequence_number'   => 7,
                        'row_data'          => '{\"column name 1\":\"value 1\",\"column name 2\":\"value 1\"}',
                        'response_data'     => null,
                        'status'            => "CREATED",
                    ],
                    [
                        'created_at'        => 1681121569,
                        'updated_at'        => 1681121569,
                        'id'                => 'Lc3FCR0jOZyfZm',
                        'batch_id'          => 'Lc3D38BgW3c77T',
                        'sequence_number'   => 6,
                        'row_data'          => '{\"column name 1\":\"value 2\",\"column name 1\":\"value 2\"}',
                        'response_data'     => null,
                        'status'            => "CREATED",
                    ],
                    [
                        'created_at'        => 1681121569,
                        'updated_at'        => 1681121569,
                        'id'                => 'Lc3FCQuFNq0CR1',
                        'batch_id'          => 'Lc3D38BgW3c77T',
                        'sequence_number'   => 5,
                        'row_data'          => '{\"column name 1\":\"value 3\",\"column name 1\":\"value 3\"}',
                        'response_data'     => null,
                        'status'            => "CREATED",
                    ]
                ]
            ],
            'status_code' => 200
        ],
    ],

    'testProcessBatchIncorrectOTP' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/batch/batch_abcd/process',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'otp'                   => '1234',
                'token'                 => 'LDv00lvWOpc3tn',
                'total_payout_amount'   => 1000,
                'config'    => [
                    'payout_purpose' => 'refund',
                    'account_number' => '100200300400'
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Verification failed because of incorrect OTP.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP
        ],
    ],

    'testProcessBatchSuccess' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/batch/batch_abcd/process',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'otp'                   => '0007',
                'token'                 => 'LDv00lvWOpc3tn',
                'total_payout_amount'   => 1000,
                'config'    => [
                    'payout_purpose' => 'refund',
                    'account_number' => '100200300400'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'C3fzDCb4hA4F6b',
                'created_at'       => 1551782255,
                'updated_at'       => 1551782255,
                'entity_id'        => 'C28Q0mJgoSfWC1',
                'name'             => 0,
                'batch_type_id'    => 'payout',
                'is_scheduled'     => false,
                'upload_count'     => 0,
                'total_count'      => 3,
                'failure_count'    => 0,
                'success_count'    => 0,
                'amount'           => 0,
                'attempts'         => 0,
                'status'           => 'CREATED',
                'processed_amount' => 0
            ],
            'status_code' => 200
        ],
    ],

    'testOwnerApprovePayoutUsingBearerAuthWithPartnerReadWriteScope' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method'  => 'POST',
            'url'     => '/payouts/{id}/approve',
            'content' => [
                'remarks' => 'Approving P2P payout',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testRejectPayoutUsingBearerAuthWithPartnerReadWriteScope' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method'  => 'POST',
            'url'     => '/payouts/{id}/reject',
            'content' => [
                'remarks' => 'Rejecting P2P payout'
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testPendingPayoutWebhookForMerchantWithOAuthApprovalEnabled' => [
        'entity'   => 'event',
        'event'    => 'payout.pending',
        'contains' => [
            'payout',
        ],
        'payload' => [
            'payout' => [
                'entity' => [
                    'entity'     => 'payout',
                    'status'     => 'pending',
                    'id'         => 'FV57s8rpBqOD6w',
                ],
            ],
        ],
    ],

    'testGetPartnerBankStatus' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/partner-bank/status',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content'     => [
                'direct_rbl_imps' => [
                    'account_type' => 'direct',
                    'channel'      => 'RBL',
                    'mode'         => 'IMPS',
                    'status'       => 'downtime'
                ],
                'direct_icici_upi' => [
                    'account_type' => 'direct',
                    'channel'      => 'ICICI',
                    'mode'         => 'UPI',
                    'status'       => 'uptime'
                ]
            ],
            'status_code' => 200
        ],
    ],
];
