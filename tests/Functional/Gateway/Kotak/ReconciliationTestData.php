<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;
use RZP\Models\Settlement\Status as SettlementStatus;

return [
    'fetchAndMatchBatchDataPayout' => [
        'type'              => 'payout',
        'entity'            => 'batch_fund_transfer',
        'channel'           => 'axis',
        'amount'            => 5000,
        'fees'              => 3010,
        'tax'               => 460,
        'api_fee'           => 0,
        'gateway_fee'       => 0,
        'total_count'       => 5,
        'transaction_count' => 5,
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