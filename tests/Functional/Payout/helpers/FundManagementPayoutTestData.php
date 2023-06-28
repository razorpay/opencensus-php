<?php

return [
    'testFundManagementPayoutCheckQueueDispatch_SingleMerchants_Dedupe' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/ca-fund-management-payouts/cron/check',
            'content' => [
                'merchant_ids' => ['10000000000000', '10000000000000'],
            ]
        ],
        'response' => [
            'content'     => [
                'dispatch_failed'     => [],
                'dispatch_successful' => ['10000000000000'],            ],
            'status_code' => 200
        ],
    ],
    'testFundManagementPayoutCheckQueueDispatch_MultipleMerchants' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/ca-fund-management-payouts/cron/check',
            'content' => [
                'merchant_ids' => ['10000000000000', '10000000000001'],
            ]
        ],
        'response' => [
            'content'     => [
                'dispatch_failed'     => [],
                'dispatch_successful' => ['10000000000000', '10000000000001'],
            ],
            'status_code' => 200
        ],
    ],
    'testFundManagementPayoutCheckQueueDispatch_MultipleMerchants_ValidationFailure_InvalidDataType' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/ca-fund-management-payouts/cron/check',
            'content' => [
                'merchant_ids' => ['10000000000000', '10000000000001'],
            ]
        ],
        'response' => [
            'content'     => [
                'dispatch_failed'     => ['10000000000001'],
                'dispatch_successful' => ['10000000000000'],
            ],
            'status_code' => 200
        ],
    ],
    'testFundManagementPayoutCheckQueueDispatch_MultipleMerchants_ValidationFailure_MissingRequiredField' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/ca-fund-management-payouts/cron/check',
            'content' => [
                'merchant_ids' => ['10000000000000', '10000000000001'],
            ]
        ],
        'response' => [
            'content'     => [
                'dispatch_failed'     => ['10000000000001'],
                'dispatch_successful' => ['10000000000000'],
            ],
            'status_code' => 200
        ],
    ],
    'testFundManagementPayoutConfig_SetConfig'                => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/fund-management-payout/balance-config/merchants/10000000000000',
            'content' => [
                'channel'                     => 'rbl',
                'neft_threshold'              => 500000,
                'lite_balance_threshold'      => 3000000,
                'lite_deficit_allowed'        => 5,
                'fmp_consideration_threshold' => 14400,
                'total_amount_threshold'      => 200000,
            ]
        ],
        'response' => [
            'content'     => [
                'message' => 'Config for 10000000000000 updated successfully'
            ],
            'status_code' => 200
        ],
    ],
    'testFundManagementPayoutConfig_GetConfig'                => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/fund-management-payout/balance-config/merchants/10000000000000',
            'content' => [
            ]
        ],
        'response' => [
            'content'     => [
                'channel'                     => 'rbl',
                'neft_threshold'              => 500000,
                'lite_balance_threshold'      => 3000000,
                'lite_deficit_allowed'        => 5,
                'fmp_consideration_threshold' => 14400,
                'total_amount_threshold'      => 200000,
            ],
            'status_code' => 200
        ],
    ],
    'testFundManagementPayoutConfig_GetConfig_Empty'          => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/fund-management-payout/balance-config/merchants/10000000000000',
            'content' => [
            ]
        ],
        'response' => [
            'content'     => [
                'message' => 'config for 10000000000000 not present'
            ],
            'status_code' => 200
        ],
    ],
    'testFundManagementPayoutConfig_SetConfig_UpdateExisting' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/fund-management-payout/balance-config/merchants/10000000000000',
            'content' => [
                'channel'                     => 'rbl',
                'neft_threshold'              => 60000,
                'lite_balance_threshold'      => 4000000,
                'lite_deficit_allowed'        => 6,
                'fmp_consideration_threshold' => 24400,
                'total_amount_threshold'      => 400000,
            ]
        ],
        'response' => [
            'content'     => [
                'message' => 'Config for 10000000000000 updated successfully'
            ],
            'status_code' => 200
        ],
    ],
];
