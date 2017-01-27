<?php

return [
    'testRetrieveAccount' => [
        'request' => [
            'url' => '/accounts/acc_10000000000001',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'id' => 'acc_10000000000001',
            ],
        ],
    ],

    'testRetrieveAccounts' => [
        'request' => [
            'url' => '/accounts',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'id' => 'acc_10000000000001',
                    ]
                ],
            ],
        ],
    ],

    'testCreateLinkedAccount' => [
        'request' => [
            'url' => '/accounts',
            'method' => 'post',
            'content' => [
                'name' => 'Linked Account 1',
                'email' => 'linked1@account.com',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'Linked Account 1',
                'email' => 'linked1@account.com',
            ],
        ],
    ],
];
