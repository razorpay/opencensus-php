<?php

return [
    'callScroogeFetchEntities' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/scrooge/entities',
            'content' => [
                'refund_ids' => [
                    'dummy1',
                    'dummy2',
                ],
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'callScroogeFetchEntitiesV2' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/scrooge/entities_fetch',
            'content' => [
                'payment_ids' => [
                    'dummy1',
                    'dummy2',
                ],
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testScroogeFetchPublicEntities' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/scrooge/fetch/public_entities',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],
];
