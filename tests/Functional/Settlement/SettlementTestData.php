<?php

use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;
use RZP\Models\Settlement\Status as SettlementStatus;

return [
    'fetchAndMatchSettlement' => [
        'merchant_id'               => '10000000000000',
        'amount'                    => 1952600,
        'fees'                      => 47200,
        'tax'                       => 7200,
        'failure_reason'            => null,
        'attempts'                  => 1,
        'status'                    => SettlementStatus::CREATED,
        'batch_fund_transfer_id'    => null,
    ],

    'matchSettlementAttempt' => [
        'version'                   => 'V3',
        'merchant_id'               => '10000000000000',
        'bank_status_code'          => null,
        'status'                    => AttemptStatus::CREATED,
        'utr'                       => null,
        'remarks'                   => null,
        'failure_reason'            => null,
        'date_time'                 => null,
        'cms_ref_no'                => null,
        'batch_fund_transfer_id'    => null,
    ],

    'testSettlementForMultipleMerchants' => [
        'axis' => [
            'count'     => 2,
            'txnCount'  => 4,
        ]
    ],

    'testSettlementForReversalOfDirectTransfer' => [
        'method'  => 'POST',
        'url'     => '/schedules/update_next_run/',
        'content' => [
            'type' => 'settlement',
        ],
    ],
];
