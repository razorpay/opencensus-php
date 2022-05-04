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

    'testSettlementCreateFromNewService' => [
        'merchant_id'               => '10000000000000',
        'channel'                   => 'axis2',
        'balance_type'              => 'primary',
        'amount'                    => 1000,
        'fees'                      => 12,
        'tax'                       => 13,
        'settlement_id'             => 'ABXUHPMNHULR13',
        'status'                    => 'processed',
        'type'                      => 'normal',
        'details'                   => [
            'payment' => [
                'type' => 'credit',
                'amount' => 1200,
                'count'  => 34,
            ],
            'refund' => [
                'type'  => 'debit',
                'amount' => -200,
                'count'  => 2,
            ]
        ]
    ],

    'testSettlementForMultipleMerchants' => [
        'axis' => [
            'count'     => 2,
            'txnCount'  => 4,
        ]
    ],

    'testSettleToPartnerWithDefaultConfig' => [
        'axis' => [
            'count'     => 2,
            'txnCount'  => 4,
        ]
    ],

    'testSettleToPartnerWithOverriddenConfig' => [
        'axis' => [
            'count'     => 2,
            'txnCount'  => 4,
        ]
    ],

    'testSettleToPartnerWhenNoMerchantBA' => [
        'axis2' => [
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

    'testSettlementCreateFromNewServiceSettlementDetails' => [
        [
            'component' => 'payment',
            'amount'    => 1200,
            'count'     => 34,
            'type'      => 'credit'
        ],
        [
            'component' => 'refund',
            'amount'    => 200,
            'count'     => 2,
            'type'      => 'debit'
        ],
    ],
    'testGefuFileCreation' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/IUXvshap3Hbzos/send_gifu_file',
            'content' => []
        ],
        'response' => [
            'content' => [
                'success' => true,
                'status'  => 'mocked',
                'bucket'  => 'test'
            ]
        ]
    ],
    'testGefuFileCreationWithoutPoolAccount' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/IUXvshap3Hbzos/send_gifu_file',
            'content' => []
        ],
        'response' => [
            'content' => [
                'success' => true,
                'status'  => 'mocked',
                'bucket'  => 'test'
            ]
        ]
    ],
];
