<?php

return [
    'testInstantRefunds' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/refunds/',
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
            ],
        ],
    ],

    'testFlipkartRefunds' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/refunds/',
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
            ],
        ],
    ],

    'testFlipkartRefundsWithFeatureShowRefundPublicStatus' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/refunds/',
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
            ],
        ],
    ],

    'testSnapdealRefunds' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/refunds/',
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
            ],
        ],
    ],

    'testSnapdealRefundsWithRefundPendingStatusFeature' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/refunds/',
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
            ],
        ],
    ],

    'testTimestampsOnGetRefundApi' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/refunds/',
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
            ],
        ],
    ],
];
