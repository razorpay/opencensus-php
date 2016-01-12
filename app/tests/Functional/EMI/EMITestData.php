<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\HdfcGateway\HdfcGatewayErrorCode;

return [
    'testAddEmiOptions' => [
        'request' => [
            'content' => [
                'bank' => 'ICIC',
                'emi_period' => 1,
                'emi_interest' => '10.45'
                'method' => 'credit'
            ],
            'url' => 'emi',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'bank' => 'ICIC',
                'emi_period' => 1,
                'emi_interest' => '10.45'
                'method' => 'credit'
            ]
        ]
    ],

    'testFetchEmiOptions' => [
        'request' => [
            'content' => [
            ],
            'url' => 'emi',
            'method' => 'get'
        ],
        'response' => [
        ],
    ],
];