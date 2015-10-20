<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testCreateMerchant' => [
        'request' => [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
                'name'  => 'Tester',
                'email' => 'test@localhost.com',
            ],
            'url' => '/merchants',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'name' => 'Tester',
                'email' => 'test@localhost.com',
                'pricing_plan_id' => '2atGxLIYLyHWg7',
                'live' => false,
                'activated' => false,
                'hold_funds' => false,
                'activated_at' => null,
                'receipt_email_enabled' => true,
                'transaction_report_email' => [
                    'test@localhost.com'
                ]
            ],
        ],
    ],

    'testGetTerminalsInTestForCreatedMerchant' => [
        'request' => [
            'url' => '/merchants/1X4hRFHFx4UiXt/terminals',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count' => 0,
                'entity' => 'collection',
                'items' => [
                ]
            ]
        ],
    ],

    'testGetTerminalsInLiveForCreatedMerchant' => [
        'request' => [
            'url' => '/merchants/1X4hRFHFx4UiXt/terminals',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count' => 0,
                'entity' => 'collection',
                'items' => []
            ]
        ],
    ],

    'testBalanceInTestAfterCreatedMerchant' => [
        'request' => [
            'url' => '/merchants/1X4hRFHFx4UiXt/balance',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'balance' => 0
            ]
        ]
    ],

    'testBalanceInLiveAfterCreatedMerchant' => [
        'request' => [
            'url' => '/merchants/1X4hRFHFx4UiXt/balance',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'balance' => 0
            ]
        ],
    ],

    'testGetBankAccountsAfterCreatedMerchant' => [
        'request' => [
            'url' => '/merchants/1X4hRFHFx4UiXt/banks',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'HDFC' => 'HDFC Bank',
                    'UTIB' => 'Axis Bank',
                ],
                'disabled' => [
                ],
            ]
        ]
    ],
];
