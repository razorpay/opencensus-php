<?php

return [
    'fetchAndMatchBatchDataSettlement' => [
        'channel' => 'kotak',
        'amount' => 4385000,
        'fees' => 115000,
        'service_tax' => 15000,
        'api_fee' => 0,
        'gateway_fee' => 0,
        'total_count' => 1,
        'transaction_count' => 10,
    ],

    'fetchAndMatchBatchDataPayout' => [
        'type' => 'payout',
        'entity' => 'batch_settlement',
        'channel' => 'kotak',
        'amount' => 5000,
        'fees' => 2935,
        'service_tax' => 385,
        'api_fee' => 0,
        'gateway_fee' => 0,
        'total_count' => 5,
        'transaction_count' => 5,
    ],

    'fetchAndMatchSettlementsForReconSuccess' => [
        'channel' => "kotak",
        'merchant_id' => '10000000000000',
        'amount' =>  4385000,
        'fees' =>  115000,
        'service_tax' =>  15000,
        'failure_reason' => null,
    ],

    'fetchAndMatchSettlementsForReconFailure' => [
        'channel' => "kotak",
        'merchant_id' => '10000000000000',
        'amount' =>  4385000,
        'fees' =>  115000,
        'service_tax' =>  15000,
        'failure_reason' => 'Reconciliation',
        'status' => 'failed',
    ],

    'matchSettlementAttemptForReconSuccess' => [
        'entity_type' => 'settlement',
        'channel' => 'kotak',
        'version' => 'V2',
        'bank_status_code' => 'P',
        'status' => 'created',
        'remarks' => '',
        'failure_reason' => null,
    ],

    'matchSettlementAttemptForReconFailure' => [
        'entity_type' => 'settlement',
        'channel' => 'kotak',
        'version' => 'V2',
        'bank_status_code' => 'P',
        'status' => 'failed',
        'remarks' => 'This is a string which test characters count limit. This is a string which test characters count limit. This is a string which test characters count limit. This is a string which test characters count limit. This is a string which test characters count li',
        'failure_reason' => 'Reconciliation',
    ],

    'testRetryRecon' => [
        'request' => [
            'url' => '/settlements/retry/kotak',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
        ]
    ]
];