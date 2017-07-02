<?php

use Carbon\Carbon;

return [
    'fetchAndMatchSettlement' => [
        'channel'           => "kotak",
        'merchant_id'       => '10000000000000',
        'amount'            => 4881500,
        'fees'              => 118000,
        'service_tax'       => 18000,
        'failure_reason'    => null,
        'attempts'     => 1,
    ],

    'fetchAndMatchBatchDataSettlement' => [
        'channel'           => 'kotak',
        'amount'            => 4881500,
        'fees'              => 118000,
        'service_tax'       => 18000,
        'api_fee'           => 0,
        'gateway_fee'       => 0,
        'total_count'       => 1,
        'transaction_count' => 10,
    ],

    'matchSettlementAttempt' => [
        'channel'           => 'kotak',
        'version'           => 'V3',
        'merchant_id'       => '10000000000000',
        'bank_status_code'  => null,
        'status'            => 'created',
        'utr'               => null,
        'remarks'           => null,
        'failure_reason'    => null,
        'date_time'         => null,
        'cms_ref_no'        => null
    ],

    'testSettlementForMultipleMerchants' => [
        'kotak' => [
            'count' => 2,
            'transaction_count' => 4,
            'settlement_text_file' => [],
            'settlement_excel_file' => [],
        ]
    ],
];