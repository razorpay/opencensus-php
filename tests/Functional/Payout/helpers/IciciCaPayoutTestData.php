<?php
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;

return [
    'testIciciAccountStatementFetchV2' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'icici',
            ],
        ],
        'response' => [
            'content' => [
                'account_number' => '2224440041626905',
                'channel'        => 'icici'
            ],
        ],
    ],
    'testCreatePayoutRtgs' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 20000000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'RTGS'
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 20000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'utr'             => null,
                'mode'            => 'RTGS',
                'notes'           => [],
            ],
        ],
    ],

    'testCreatePayoutUpi' => [
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
                    'description' => 'ICICI does not support UPI payouts to VPA',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
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

    'testCreatePayoutImps' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 1000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'IMPS'
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 1000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'utr'             => null,
                'mode'            => 'IMPS',
                'notes'           => [],
            ],
        ],
    ],

    'testCreatingPendingPayoutsForIciciWithSupportedModeChannelDestinationTypeCombo' => [
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
    'testCreatingPendingPayoutsAndApprovalWithOtp' => [
        'request' => [
            'url'     => '/payouts/approve/2fa',
            'method'  => 'POST',
            'content' => [
                'payout_id' => 'fa_100000000000fa',
                'otp'          => '000777',
                'user_comment' => 'abc123pqr'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreatingPendingPayoutsAndRetryApprovalWithOtp' => [
        'request' => [
            'url'     => '/payouts/approve/2fa',
            'method'  => 'POST',
            'content' => [
                'payout_id' => 'fa_100000000000fa',
                'otp'          => '000777',
                'user_comment' => 'abc123pqr'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testRetryApprovalWhenOtpIsAlreadySubmitted' => [
        'request' => [
            'url'     => '/payouts/approve/2fa',
            'method'  => 'POST',
            'content' => [
                'payout_id' => 'fa_100000000000fa',
                'otp'          => '000777',
                'user_comment' => 'abc123pqr'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payout is not in pending state',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_INVALID_STATE,
        ],
    ],

    'testInitiatedWebhookForIcici2FAWithIncorrectOtp' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'INVALID_OTP',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'NEFT',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => '',
                'source_type'         => 'payout',
                'status'              => 'INITIATED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testInitiatedWebhookForIcici2FAWithEmptyBankStatusCode' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => null,
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'NEFT',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => '',
                'source_type'         => 'payout',
                'status'              => 'INITIATED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testProcessedWebhookWithoutInitiatedForIcici2FAPayout' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'success',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'NEFT',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => '',
                'source_type'         => 'payout',
                'status'              => 'PROCESSED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testProcessedWebhookAfterInitiatedForIcici2FAPayout' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'success',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'NEFT',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => '',
                'source_type'         => 'payout',
                'status'              => 'PROCESSED',
                'utr'                 => 928337183,
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'FTA and source updated successfully'
            ],
        ],
    ],

    'testFailedWebhookWithoutInitiatedForIcici2FAPayout' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'OTP_RETRIES_EXHAUSTED',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'NEFT',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => '',
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

    'testFailedWebhookWithInitiatedForIcici2FAPayout' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'OTP_RETRIES_EXHAUSTED',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'NEFT',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => '',
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

    'testFailedWebhookWithoutInitiatedForIcici2FAPayoutProcessType1' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'OTP_RETRIES_EXHAUSTED',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'NEFT',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => '',
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

    'testFailedWebhookWithoutInitiatedForIcici2FAPayoutProcessType2' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'OTP_RETRIES_EXHAUSTED',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'NEFT',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => '',
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

    'testReversedWebhookWithoutInitiatedForIcici2FAPayout' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'reversed',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'NEFT',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check if free payouts consumed get reduced to 0.',
                'source_id'           => '',
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

    'testCreatingPendingPayoutsForIciciWithUnsupportedModeChannelDestinationTypeCombo' => [
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
                    'description' => 'ICICI does not support UPI payouts to VPA',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testCreatePayout' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 1000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'NEFT'
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 1000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'utr'             => null,
                'mode'            => 'NEFT',
                'notes'           => [],
            ],
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

    'testCreateM2PPayoutForMerchantDirectAccountCardMode' => [
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
                    'description' => 'ICICI does not support card payouts to CARD',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

        'testCreateFreePayoutForNEFTModeDirectAccountProxyAuth' => [
            'request' => [
                'method'  => 'POST',
                'url'     => '/payouts',
                'content' => [
                    'account_number'  => '2224440041626905',
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

        'testCreateFreePayoutForIMPSModeDirectAccountPrivateAuth' => [
            'request' => [
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

    'testCreateFreePayoutForUPIModeDirectAccountPrivateAuth' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
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
                    'description' => 'ICICI does not support UPI payouts to VPA',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
        ],
    ],

    'testPayoutCreateWithIcici2FaMerchantNotAllowedFeatureNotEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/2fa/create',
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
                'error' => [
                    'description' => 'ICICI 2FA payout is not enabled.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_ENABLED_FOR_2FA_PAYOUT,
        ],
    ],

    'testPayoutCreateWithIcici2FaInvalidPayoutPayload' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/2fa/create',
            'content' => [
                'amount'          => 2000000,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
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

    'testPayoutCreateWithIcici2FaSuccess' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/2fa/create',
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
                'status'          => 'pending',
                'failure_reason'  => null,
            ],
        ],
    ],

    'testPayoutCreateWithIcici2FaSuccessInternalRoute' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
            ],
            'url'     => '/payouts/2fa/create_internal',
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
                'status'          => 'pending',
                'failure_reason'  => null,
            ],
        ],
    ],

    'testPayoutCreateWithIcici2FaSuccessInternalRouteWithIdempotency' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts/2fa/create_internal',
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
                'status'          => 'pending',
                'failure_reason'  => null,
            ],
        ],
    ],

    'testPayout2faOtpSend' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts/2fa/send_otp',
            'content' => ['payout_id' => 'pout_FUj82QLoJgRcM0'],
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
            'status_code' => 200
        ]
    ],

    'testPayout2faOtpSendInvalidPayload' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts/2fa/send_otp',
            'content' => ['abc' => 'def'],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'abc is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testPayout2faOtpSendPayoutNotInPendingState' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts/2fa/send_otp',
            'content' => ['payout_id' => 'pout_FUj82QLoJgRcM0'],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'ICICI 2FA payout OTP creation is not allowed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_ALLOWED_TO_TRIGGER_2FA_OTP,
        ],
    ],

    'testPayout2faOtpSendMerchantNotEnabled' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts/2fa/send_otp',
            'content' => ['payout_id' => 'pout_FUj82QLoJgRcM0'],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'ICICI 2FA payout OTP creation is not allowed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_ALLOWED_TO_TRIGGER_2FA_OTP,
        ],
    ],

    'testPayoutCreateForIciciCaApiPayoutMerchantEnabled' => [
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
                'error' => [
                    'description' => 'API payouts are not available for this account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testPayoutCreateForIciciCaApiPayoutMerchantNotEnabled' => [
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
                'status'          => 'processing',
                'failure_reason'  => null,
            ],
        ],
    ],

    'testBlockBulkPayoutsForIcici2faWhenMerchantEnabled' => [
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
                            'description' => 'API payouts are not available for this account',
                            'code' => 'BAD_REQUEST_ERROR',
                        ],
                        'http_status_code' => 400
                    ],
                ],
            ],
        ],
    ],

    'testBlockBulkPayoutsForIcici2faWhenMerchantDisabled' => [
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
            ]
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
                                'ifsc'              => 'HDFC0003780',
                                'bank_name'         => 'HDFC Bank',
                                'name'              => 'Vivek Karna',
                                'account_number'    => '50100244702362',
                            ],
                            'active'                => true,
                        ],
                        'amount'                    => 100,
                        'currency'                  => 'INR',
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
                ]
            ],
        ],
    ],

    'testIcici2faPayoutByCapitalCollectionsApp' => [
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

    'testIcici2faPayoutByVendorPaymentsApp' => [
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

    'testIcici2faPayoutByPayrollAppWhenFeatureEnabled' => [
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
                'error' => [
                    'description' => 'API payouts are not available for this account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testIcici2faPayoutByPayrollAppWhenFeatureDisabled' => [
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

    'testIcici2faPayoutForTaxPayment'  => [
        'request'  => [
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ],
            'method'  => 'POST',
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => '',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'payout',
                'amount'    => 2000000,
                'currency'  => 'INR',
                'narration' => 'Batman',
                'purpose'   => 'refund',
                'status'    => 'processing',
                'mode'      => 'IMPS',
                'tax'       => 162,
                'fees'      => 1062,
                'notes'     => [
                    'abc' => 'xyz',
                ],
            ]
        ],
    ],

    'testScheduledPayoutCreateForIcici2faEnabledMerchant' => [
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
                'token'           => 'BUIj3m2Nx2VvVj',
                'otp'             => '0007',
                'scheduled_at'    => '0'
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Scheduled payouts cannot be created using ICICI CA 2FA',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testScheduledPayoutProcessingAutoRejectForIcici' => [
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

    'testBlockingOfApiPayoutsWhenNeither2faNorBaasFeatureIsEnabledAndRedisKeySet' => [
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
                'error' => [
                    'description' => 'API payouts are not available for this account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],
];
