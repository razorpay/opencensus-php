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
];
