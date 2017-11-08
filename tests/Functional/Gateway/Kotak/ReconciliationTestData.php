<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;
use RZP\Models\Settlement\Status as SettlementStatus;

return [
    'fetchAndMatchBatchDataSettlement' => [
        'channel'           => 'kotak',
        'amount'            => 4382000,
        'fees'              => 118000,
        'tax'               => 18000,
        'api_fee'           => 0,
        'gateway_fee'       => 0,
        'total_count'       => 1,
        'transaction_count' => 10,
    ],

    'fetchAndMatchBatchDataPayout' => [
        'type'              => 'payout',
        'entity'            => 'batch_fund_transfer',
        'channel'           => 'kotak',
        'amount'            => 5000,
        'fees'              => 3010,
        'tax'               => 460,
        'api_fee'           => 0,
        'gateway_fee'       => 0,
        'total_count'       => 5,
        'transaction_count' => 5,
    ],

    'fetchAndMatchSettlementsForReconSuccess' => [
        'channel'           => "kotak",
        'merchant_id'       => '10000000000000',
        'amount'            => 4382000,
        'fees'              => 118000,
        'tax'               => 18000,
        'failure_reason'    => null,
        'attempts'          => 1,
    ],

    'fetchAndMatchSettlementsForRetryReconSuccess' => [
        'channel'           => "kotak",
        'merchant_id'       => '10000000000000',
        'amount'            => 4382000,
        'fees'              => 118000,
        'tax'               => 18000,
        'failure_reason'    => null,
        'attempts'          => 2,
    ],

    'fetchAndMatchSettlementsForReconFailure' => [
        'channel'           => "kotak",
        'merchant_id'       => '10000000000000',
        'amount'            => 4382000,
        'fees'              => 118000,
        'tax'               => 18000,
        'failure_reason'    => 'Reconciliation',
        'status'            => SettlementStatus::FAILED,
        'attempts'          => 1,
        'remarks'           => 'This is a string which test characters count limit. This is a string which test characters count limit. This is a string which test characters count limit. This is a string which test characters count limit. This is a string which test characters count li',
    ],

    // status is not matched as we keep it created till 10pm
    'matchSettlementAttemptForReconSuccess' => [
        'channel'           => 'kotak',
        'version'           => 'V3',
        'bank_status_code'  => 'P',
        //'status'            => 'created',
        'remarks'           => '',
        'failure_reason'    => null,
    ],

    'matchSettlementAttemptForReconFailure' => [
        'channel'          => 'kotak',
        'version'          => 'V3',
        'bank_status_code' => 'P',
        'status'           => AttemptStatus::FAILED,
        'remarks'          => 'This is a string which test characters count limit. This is a string which test characters count limit. This is a string which test characters count limit. This is a string which test characters count limit. This is a string which test characters count li',
        'failure_reason'   => 'Reconciliation',
    ],

    'testRetryRecon' => [
        'request' => [
            'url' => '/settlements/retry/kotak',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
        ]
    ],

    'testRetryReconWithoutSettlementIds' => [
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
            'class'               => RZP\Models\Settlement\SettlementFailureException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_SETTLEMENTS_FAILED,
        ],
    ]
];