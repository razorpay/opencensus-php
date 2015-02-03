<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;

return [
    'testAddAdjustment' => [
        'request' => [
            'content' => [
                'amount' => 100,
                'description' => 'A random adjustment passing through',
                'currency' => 'INR',
            ],
            'url' => '/adjustments',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
];