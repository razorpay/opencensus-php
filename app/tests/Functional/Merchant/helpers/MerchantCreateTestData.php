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
                'pricing_plan_id' => '1In3Yh5Mluj605',
                'live' => false,
                'activated' => false,
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

    'testBalanceAfterCreatedMerchant' => [
        'request' => [
            'url' => '/merchants//balance'
        ],
        'response' => [
        ]
    ]
];