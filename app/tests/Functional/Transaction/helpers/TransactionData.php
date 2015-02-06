<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;

return [
    'testAddAdjustment' => [
        'request' => [
            'content' => [
                'amount' => 100,
                'description' => 'random desc',
                'currency' => 'INR',
            ],
            'url' => '/adjustments',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'amount' => 100,
                'description' => 'random desc',
                'channel' => 'kotak',
                'currency' => 'INR',
            ],
        ],
    ],

    'testGetAdjustment' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'adjustment',
                'amount' => 100,
                'description' => 'random desc',
                'currency' => 'INR',
            ]
        ]
    ],
];