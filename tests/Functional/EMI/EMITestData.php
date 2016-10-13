<?php

use RZP\Gateway\HdfcGateway\HdfcGatewayErrorCode;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAddEmiPlans' => [
        'request' => [
            'content' => [
                'bank' => 'HDFC',
                'duration' => 3,
                'rate' => 1045,
                'methods' => 'card',
                'min_amount' => 400000
            ],
            'method' => 'POST',
            'url' => '/emi',
        ],
        'response' => [
            'content' => [
                'bank' => 'HDFC',
                'duration' => 3,
                'rate' => 1045,
                'methods' => 'card',
                'min_amount' => 400000
            ],
        ],
    ],

    'testFetchAllEmiPlans' => [
        'request' => [
            'content' => [
            ],
            'url' => '/emi',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'HDFC' => [
                    'min_amount' => 400000,
                    'plans' => [
                        3 => 10.45
                    ]
                ]
            ],
        ],
    ],

    'testFetchEmiPlanUsingPlanId' => [
        'request' => [
            'content' => [
            ],
            'url' => '/emi/{id}',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'bank' => 'HDFC',
                'rate' => 1045,
                'duration' => 3,
                'methods' => 'card',
                'min_amount' => 400000
            ],
        ],
    ],

    'testDeleteEmiPlan' => [
        'request' => [
            'content' => [
            ],
            'url' => '/emi/{id}',
            'method' => 'delete'
        ],
        'response' => [
            'content' => [
                'bank' => 'HDFC',
                'rate' => 1045,
                'duration' => 3,
                'methods' => 'card',
                'min_amount' => 400000
            ],
        ],
    ],
];