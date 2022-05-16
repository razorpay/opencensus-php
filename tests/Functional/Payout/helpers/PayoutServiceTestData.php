<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\Payout\QueuedReasons;

return [
    'testCreatePayoutEntry' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create',
            'content' => [
                "id"                   => "Gg7sgBZgvYjlSB",
                "mode"                 => "IMPS",
                "currency"             => "INR",
                "purpose"              => "refund",
                "fund_account_id"      => "fa_100000000000fa",
                "balance_id"           => "GhidjxhfiCL7WT",
                "merchant_id"          => "10000000000000",
                "origin"               => "api",
                "channel"              => "",
                "amount"               => 100,
                "status"               => "create_request_submitted",
                "type"                 => "",
                "reference_id"         => null,
                "narration"            => "test Merchant Fund Transfer",
                "fee_type"             => "",
                "queue_if_low_balance" => false,
                "notes"                => [],
                "workflow_details"     => [
                    'id'                       => '',
                    'config_id'                => '',
                    'workflow_service_enabled' => false
                ]
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'create_request_submitted',
                'error'  => null
            ],
        ],
    ],

    'testCreatePayoutServiceTransaction' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_ledger',
            'content' => [
                "id" => "Gg7sgBZgvYjlSB",
            ],
        ],
        'response' => [
            'content' => [
                'status'        => 'created',
                'error'         => null,
                'queued_reason' => null,
                'status_code'   => null
            ],
        ],
    ],

    'testCreatePayoutServiceFtaCreation' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_fta/Gg7sgBZgvYjlSB',
            'content' => [
                "id" => "Gg7sgBZgvYjlSB",
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'created',
                'error'  => null
            ],
        ],
    ],

    'testCreatePayoutServicePaymentCreation' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payouts_service/payments/create/axis',
            'content' => [
                "amount" => 50000,
                "currency" => "INR",
                "email" => "gaurav.kumar@example.com",
                "contact" => 9123456789,
                "method" => "card",
                "card" =>
                    [
                        "number" => "5104060000000008",
                        "name" => "Gaurav Kumar",
                        "expiry_month" => "01",
                        "expiry_year" => \Carbon\Carbon::now()->addYear()->format('y')
                    ],
                "auth_type" => "skip"
            ],
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreatePayout' => [
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

    'testCreatePayoutWithFeeRewards' => [
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
                'tax'             => 0,
                'fees'            => 500,
            ],
        ],
    ],

    'testCreatePayoutInternalContact' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
            ],
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'                       => '2224440041626905',
                'amount'                               => 100,
                'currency'                             => 'INR',
                'purpose'                              => 'refund',
                'narration'                            => 'test Merchant Fund Transfer',
                'mode'                                 => 'IMPS',
                'enable_workflow_for_internal_contact' => true,
                'fund_account_id'                      => 'fa_100000000000fa',
                'origin'                               => 'api',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
                'origin'          => 'api',
            ],
        ],
    ],

    'testCreatePayoutInternalContactWithoutFeatureFlag' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
            ],
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'                       => '2224440041626905',
                'amount'                               => 100,
                'currency'                             => 'INR',
                'purpose'                              => 'refund',
                'narration'                            => 'Batman',
                'mode'                                 => 'IMPS',
                'enable_workflow_for_internal_contact' => false,
                'fund_account_id'                      => 'fa_100000000000fa',
                'origin'                               => 'api',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'mode'            => 'IMPS',
                'origin'          => 'api',
            ],
        ],
    ],

    'testGetPayoutById' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/pout_Gg7sgBZgvYjlSB',
            'server' => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "id"                =>   "pout_Gg7sgBZgvYjlSB",
                "entity"            =>   "payout",
                "fund_account_id"   =>   "fa_100000000000fa",
                "amount"            =>   100,
                "currency"          =>   "INR",
                "merchant_id"       =>   "10000000000000",
                "notes"             =>   "",
                "fees"              =>   0,
                "tax"               =>   0,
                "status"            =>   "processing",
                "purpose"           =>   "refund",
                "utr"               =>   "",
                "reference_id"      =>   null,
                "narration"         =>   "test Merchant Fund Transfer",
                "batch_id"          =>   "",
                "initiated_at"      =>   1614325830,
                "failure_reason"    =>   null,
                "created_at"        =>   1614325826,
                "fee_type"          =>   null
            ],
        ],
    ],

    'testValidatePayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/validate_payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'OK'
            ],
        ],
    ],

    'testGetPayoutAnalytics' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payouts/analytics',
            'server' => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'data' => [
                    'payouts_count' => [
                        'result' => [
                                    [
                                        'value' => 3
                                    ]
                                    ],
                                   'last_updated_at' => 1637643003
                            ],
                    'payouts_daywise' => [
                        'result' => [
                                [
                                    'value' => 0,
                                    'timestamp' =>  1635051003,
                                ],
                                [
                                    'value' => 100,
                                    'timestamp' => 1635137403,
                                ]
                            ],
                            'last_updated_at' => 1637643003
                        ],
                    'payouts' => [
                        'result' => [
                                    [
                                        'value' => 300
                                    ]
                                ],
                                'last_updated_at' => 1637643003
                            ],
                    ]
            ],
        ],
    ],

    'testValidatePayoutFailCase' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/validate_payouts',
            'content' => [
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account number field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutServiceFailure' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                "id"              => "Gg7sgBZgvYjlSB",
                "merchant_id"     => "10000000000000",
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testCreateReversalEntry' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/reversal/create',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testUpdateFTAAndPayoutProcessed' => [
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

    'testUpdateFTAAndPayoutToFailed' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'INVALID',
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

    'testUpdateFTAAndPayoutDetailsFailure' => [
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
                'failure_reason'      => 'Test for webhook and email not firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_type'         => 'payout',
                'status'              => 'PROCESSED',
                'utr'                 => 928337183,
            ],
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ]
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],


    'testUpdateFTAAndPayoutStatusFailure' => [
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
                'failure_reason'      => 'Test for webhook and email not firing',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_type'         => 'payout',
                'status'              => 'PROCESSED',
                'utr'                 => 928337183,
            ],
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ]
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testCreatePayoutForCard' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
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
                'status'          => 'processing',
                'mode'            => 'NEFT',
                'tax'             => 90,
                'fees'            => 590,
            ],
        ],
    ],

    'testServiceCancelQueuedPayoutProxyAuth' => [
        'request'  => [
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'cancelled',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
            ],
        ],
    ],

    'testServiceCancelQueuedPayoutPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
            'content' => [
                'remarks' => 'test remark'
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'cancelled',
                'mode'            => 'IMPS',
                'tax'             => 90,
                'fees'            => 590,
            ],
        ],
    ],

    'testCreatePayoutForOnHoldPayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
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
                'status'          => 'processing',
                'mode'            => 'NEFT',
                'tax'             => 90,
                'fees'            => 590,
            ],
        ],
    ],

    'testCreatePayoutWithIdempotencyKey' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
            ],
            'server' => [
                'HTTP_' . \RZP\Http\RequestHeader::X_PAYOUT_IDEMPOTENCY => 'idem_key_test',
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
                'mode'            => 'NEFT',
                'tax'             => 90,
                'fees'            => 590,
            ],
        ],
    ],

    'testCreateOnHoldPayoutViaPayoutService' => [
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
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testCreateQueuedPayoutViaPayoutService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 500,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'NEFT',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 500,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'mode'            => 'NEFT',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testCreateQueuedPayoutViaAPIWhenWorkflowAndOnHoldEnabledForMerchant' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 500,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'NEFT',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 500,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'mode'            => 'NEFT',
                'tax'             => 0,
                'fees'            => 0,
            ],
        ],
    ],

    'testRetryPayoutService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/retry',
            'content' => [
                'payout_ids' => []
            ],
        ],
        'response' => [
            'content' => [
                'total_count'       => 1,
                'success_count'     => 1,
                'failure_count'     => 0,
                'failed_payout_ids' => [],
            ],
        ],
    ],

    'testRetryPayoutServiceFail' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/retry',
            'content' => [
                'payout_ids' => []
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testCreateLedgerForQueuedPayoutCreatedViaService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_ledger',
            'content' => [
                "id"                   => "Gg7sgBZgvYjlSB",
                "queue_if_low_balance" => true,
            ],
        ],
        'response' => [
            'content' => [
                'status'        => 'queued',
                'error'         => null,
                "queued_reason" => QueuedReasons::LOW_BALANCE,
            ],
        ],
    ],

    'testCreateLedgerForOnHoldPayoutCreatedViaPayoutService' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_ledger',
            'content' => [
                "id"  => "Gg7sgBZgvYjlSB",
            ],
        ],
        'response' => [
            'content' => [
                'status'        => 'created',
                'error'         => null,
                'queued_reason' => null,
                'status_code'   => null
            ],
        ],
    ],

    'testServiceCancelFailure' => [
        'request'  => [
            'method' => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Service Failure',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testCreateLedgerForStatusCodeValueFowLowBalance' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_ledger',
            'content' => [
                "id"                   => "Gg7sgBZgvYjlSB",
            ],
        ],
        'response' => [
            'content' => [
                'status'        => 'failed',
                'error'         => 'Insufficient balance to process payout',
                'queued_reason' => null,
                'status_code'   => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING
            ],
        ],
    ],

    'testCreateWorkflowPayoutEntry' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_workflow_for_payout',
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ],
            'content' => [
                "id"                   => "Gg7sgBZgvYjlSB",
                "mode"                 => "IMPS",
                "currency"             => "INR",
                "purpose"              => "refund",
                "fund_account_id"      => "fa_100000000000fa",
                "balance_id"           => "GhidjxhfiCL7WT",
                "channel"              => "",
                "amount"               => 54321,
                "type"                 => "",
                "reference_id"         => null,
                "narration"            => "test Merchant Fund Transfer",
                "fee_type"             => "",
                "queue_if_low_balance" => false,
                "notes"                => [],
            ],
        ],
        'response' => [
            'content' => [
                'is_workflow_activated'  => true,
                'error'                  => null,
            ],
        ],
    ],

    'testCreateWorkflowPayoutEntryDuplicateRequest' =>   [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_workflow_for_payout',
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ],
            'content' => [
                "id"                   => "Gg7sgBZgvYjlSB",
                "mode"                 => "IMPS",
                "currency"             => "INR",
                "purpose"              => "refund",
                "fund_account_id"      => "fa_100000000000fa",
                "balance_id"           => "GhidjxhfiCL7WT",
                "channel"              => "",
                "amount"               => 54321,
                "type"                 => "",
                "reference_id"         => null,
                "narration"            => "test Merchant Fund Transfer",
                "fee_type"             => "",
                "queue_if_low_balance" => false,
                "notes"                => [],
            ],
        ],
        'response' => [
            'content' => [
                'is_workflow_activated'  => true,
                'error'                  => null,
            ],
        ],
    ],

    'testCreateWorkflowPayoutEntryForNonWorkflowPayout' => [
            'request'  => [
                'method'  => 'POST',
                'url'     => '/payouts_service/create_workflow_for_payout',
                'server' => [
                    'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
                ],
                'content' => [
                    "id"                   => "Gg7sgBZgvYjlSB",
                    "mode"                 => "IMPS",
                    "currency"             => "INR",
                    "purpose"              => "refund",
                    "fund_account_id"      => "fa_100000000000fa",
                    "balance_id"           => "GhidjxhfiCL7WT",
                    "channel"              => "",
                    "amount"               => 54321,
                    "type"                 => "",
                    "reference_id"         => null,
                    "narration"            => "test Merchant Fund Transfer",
                    "fee_type"             => "",
                    "queue_if_low_balance" => false,
                    "notes"                => []
                ],
            ],
            'response' => [
                'content' => [
                    'is_workflow_activated'  => false,
                    'error'                  => null,
                ],
            ],
        ],

    'testAdminFetchPayoutsViaService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/payouts.payouts/Gg7sgBZgvYjlSB',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAdminFetchReversalsViaService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/payouts.reversals/Gg7sgBZgvYjlSB',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAdminFetchPayoutLogsViaService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/payouts.payout_logs/Gg7sgBZgvYjlSB',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAdminFetchPayoutSourcesViaService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/payouts.payout_sources/Gg7sgBZgvYjlSB',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testStatusUpdateToPayoutServiceForProcessedStatusWhenCallerIsFtsWebhook' => [
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

    'testCreatePayoutEntryViaPayoutsLinkWithWFEnabled' => [
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
];
