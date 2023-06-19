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

    'testCustomGefuFileCreation' => [
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

    'testCustomGefuFileWithNonDsAndDsTransactionsCreation' => [
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

    'testCustomGefuFileCreationWithMultipleDsTransactionsScenario' => [
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

    'testCustomGefuFileCreationWithTiDbDelay' => [
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

    'testGetConfigForOrgBadRequest' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/settlements/org_config/get',
            'content' => [
                'org_id'               => '100000razorpay'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => "BAD_REQUEST_ERROR",
                    'description' => 'The id provided does not exist',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => "BAD_REQUEST_INVALID_ID",
        ]
    ],

    'testCreateConfigForOrgBadRequest' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/settlements/org_config/create_or_update',
            'content' => [
                'org_id'               => 'org_100000razorpay',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => "BAD_REQUEST_ERROR",
                    'description' => 'The config field is required.',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => "BAD_REQUEST_VALIDATION_FAILURE",
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
