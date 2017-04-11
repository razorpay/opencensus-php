<?php

use Carbon\Carbon;

return [
    'fetchAndMatchSettlementForV2' => [
        'channel'           => "kotak",
        'merchant_id'       => '10000000000000',
        'amount'            => 4884500,
        'fees'              => 115000,
        'service_tax'       => 15000,
        'failure_reason'    => null,
        'attempts'     => 1,
    ],

    'fetchAndMatchBatchDataSettlement' => [
        'channel'           => 'kotak',
        'amount'            => 4884500,
        'fees'              => 115000,
        'service_tax'       => 15000,
        'api_fee'           => 0,
        'gateway_fee'       => 0,
        'total_count'       => 1,
        'transaction_count' => 10,
    ],

    'matchSettlementAttempt' => [
        'channel'           => 'kotak',
        'version'           => 'V2',
        'bank_status_code'  => 'P',
        'status'            => 'created',
        'bank_status_code'  => null,
        'utr'               => null,
        'remarks'           => null,
        'failure_reason'    => null,
        'date_time'         => null,
        'cms_ref_no'        => null
    ],
];