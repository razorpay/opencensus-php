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
                'activated_at' => null,
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
                'count' => 2,
                'entity' => 'collection',
                'items' => [
                    [
                        'entity' => 'terminal',
                        'gateway' => 'hdfc',
                    ],
                    [
                        'entity' => 'terminal',
                        'gateway' => 'atom',
                    ]
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
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ]
    ],

    'testGetBankAccountsAfterCreatedMerchant' => [
        'request' => [
            'url' => '/merchants/1X4hRFHFx4UiXt/banks',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'UTIB' => 'Axis Bank',
                    'BKID' => 'Bank of India',
                ],
                'disabled' => [
                ],
            ]
        ]
    ],
];