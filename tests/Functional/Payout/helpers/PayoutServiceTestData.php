<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

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
                "channel"              => "",
                "amount"               => 100,
                "status"               => "create_request_submitted",
                "type"                 => "",
                "reference_id"         => null,
                "narration"            => "test Merchant Fund Transfer",
                "fee_type"             => "",
                "queue_if_low_balance" => false
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
                "id"                   => "Gg7sgBZgvYjlSB",
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'created',
                'error'  => null
            ],
        ],
    ],

    'testCreatePayoutServiceFtaCreation' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts_service/create_fta/Gg7sgBZgvYjlSB',
            'content' => [
                "id"                   => "Gg7sgBZgvYjlSB",
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'created',
                'error'  => null
            ],
        ],
    ],

    'testCreatePayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          =>  100,
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
                'amount'          =>  100,
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

    'testCreatePayoutServiceFailure' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                "id"              => "Gg7sgBZgvYjlSB",
                "merchant_id"     => "10000000000000",
                'account_number'  => '2224440041626905',
                'amount'          =>  100,
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

    'testUpdateFTAAndPayoutStatusFailure' => [
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
];
