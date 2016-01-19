<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\HdfcGateway\HdfcGatewayErrorCode;

return [
    'testAddEmiOptions' => [
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

    'testFetchAllEmiOptions' => [
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

    'testFetchEmiUsingPlanId' => [
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
];