<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\HdfcGateway\HdfcGatewayErrorCode;

return [
    'testAddEmiPlans' => [
        'request' => [
            'content' => [
                'bank' => 'HDFC',
                'duration' => 3,
                'rate' => 1045,
                'methods' => 'card',
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
                'min_amount' => 300000
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
                'min_amount' => 300000
            ],
        ],
    ],
];