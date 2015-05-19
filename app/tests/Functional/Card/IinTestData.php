I<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testAddIin' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin' => 112333,
                'network' => 'RuPay',
                'type' => 'debit',
            ],
        ],
        'response' => [
            'content' => [
                'iin' => 112333,
                'network' => 'RuPay',
                'type' => 'debit',
            ],
        ],
    ],

    'testGetIin' => [
        'request' => [
            'url' => '/iins/607500',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'iin'       => 607500,
                'category'  => 'STANDARD',
                'network'   => 'RuPay',
                'type'      => 'debit',
                'country'   => 'IN',
                'issuer'    => 'PUNJAB NATIONAL BANK',
                'trivia'    => 'random trivia'
            ]
        ],
    ],

    'testGetIins' => [
        'request' => [
            'url' => '/iins',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 6,
                'items' => [
                    [
                    ]
                ]
            ]
        ],
    ],
];