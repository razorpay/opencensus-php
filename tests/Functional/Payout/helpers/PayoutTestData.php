<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\Status as PayoutStatus;
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

    'testApprovePayoutWithComment' => [
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
            'content' => [
                'token'   => 'BUIj3m2Nx2VvVj',
                'otp'     => '1234',
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

    'testBulkRejectPayoutsWithoutComment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/reject/bulk',
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
                'fund_account_id' => 'fa_100000000002fa',
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
                'amount'   => 20000100,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
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
                        'user_id'                   => null,
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
                        'user_id'                   => null,
                        'mode'                      => 'UPI',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc124'
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
                        'user_id'                   => null,
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
                        'user_id'                   => null,
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
                        'user_id'                   => null,
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
                        'user_id'                   => null,
                        'mode'                      => 'IMPS',
                        'reference_id'              => null,
                        'narration'                 => '123',
                        'idempotency_key'           => 'batch_abc124'
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
                        'user_id'                   => null,
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
                        'user_id'                   => null,
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

    'testDashboardSummary' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/_meta/summary',
        ],
        'response' => [
            'content' => [
                'bacc_ABCde1234ABCde' => [
                    'queued' =>  [
                        'balance'       => 10000000,
                        'count'         => 1,
                        'total_amount'  => 20000099,
                        'total_fees'    => 1770,
                    ],
                    'pending' => [
                        'count'         => 1,
                        'total_amount'  => 54321,
                    ]
                ],
                'bacc_DEcba4321DEcba' => [
                    'queued' =>  [
                        'balance'       => 10000000,
                        'count'         => 1,
                        'total_amount'  => 30000099,
                        'total_fees'    => 1770,
                    ],
                    'pending' => [
                        'count'         => 1,
                        'total_amount'  => 12345,
                    ]
                ]
            ],
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

    'testCreatePayoutForCitiToCardViaNEFT' => [
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
                'tax'             => 90,
                'fees'            => 590,
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
                'amount'                => 20000000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'narration'             => 'Batman',
                'mode'                  => 'IMPS',
                'fund_account_id'       => 'fa_100000000002fa',
                'queue_if_low_balance'  => 1,
                'notes'                 => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'RBL does not support IMPS payouts to CARD',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
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
            'method'  => 'GET',
            'url'     => '/payouts/_meta/workflows',
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
                'amount'          => 30000000,
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
                    [
                        'purpose'       => 'Give Mehul A Bonus',
                        'purpose_type'  => 'settlement',
                    ],
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

    'testAdd101CustomPayoutPurposes' => [
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
                    'description' => 'You have reached the maximum limit (100) of custom payout purposes that can be created.',
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
                'fund_account_id' => 'fa_EIXgVWknyiroq6',
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
                'fund_account_id' => 'fa_EIXgVWknyiroq6',
                'amount'          => 100,
                'currency'        => 'INR',
                'status'          => 'processing',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
            ],
        ],
    ],

    'testPayoutToAmexCardWithNullIssuerSupportedModeButFeatureDisabledOrRazorxTimeout' => [
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
                    "expiry_year"  => 21,
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

    'testPayoutToAmexCardWithNullIssuerNotSupportedMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account_id' => 'fa_EIXgVWknyiroq6',
                'amount'          => 100,
                'mode'            => 'UPI',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'UPI is not a valid mode for issuer AMEX',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPayoutToAmexCardWithSupportedIssuerSupportedMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account_id' => 'fa_EIXgVWknyiroq6',
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
                'fund_account_id' => 'fa_EIXgVWknyiroq6',
                'amount'          => 100,
                'currency'        => 'INR',
                'status'          => 'processing',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
            ],
        ],
    ],

    'testPayoutToAmexCardWithSupportedIssuerNotSupportedMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'fund_account_id' => 'fa_EIXgVWknyiroq6',
                'amount'          => 100,
                'mode'            => 'UPI',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'UPI is not a valid mode for issuer SCBL',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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
                    "expiry_year"  => 21,
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

    'testRejectPayoutWithSuperAdmin' => [
        'request'  => [
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'rejected',
            ],
        ],
    ],

    'testRejectPayoutWithOrdinaryAdmin' => [
        'request'  => [
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access Denied',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testCreateMerchantPayoutOnDemandDoesNotTriggerWorkflow' => [
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
];
