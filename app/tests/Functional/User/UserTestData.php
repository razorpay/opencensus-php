<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testCreateUser' => [
        'request' => [
            'url' => '/users',
            'method' => 'post',
            'content' => [
                'name'    => 'testc',
                'email'   => 'testc@razorpay.com',
                'contact' => '1234567899',
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'testc',
                'email' => 'testc@razorpay.com',
                'contact' => '1234567899',
            ],
        ],
    ],

    'testUpdateUser' => [
        'request' => [
            'url' => '/users/1000000000user',
            'method' => 'put',
            'content' => [
                'name'    => 'test1',
                'email'   => 'test1@razorpay.com',
                'contact' => '1234567809',
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'test1',
                'email' => 'test1@razorpay.com',
                'contact' => '1234567809',
            ],
        ],
    ],

    'testGetUser' => [
        'request' => [
            'url' => '/users/1000000000user',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'id' => '1000000000user',
                'name'    => 'test',
                'email' => 'test@razorpay.com',
                'contact' => '1234567890',
            ],
        ],
    ],

    'testDeleteUser' => [
        'request' => [
            'url' => '/users/1000000000user',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetUserMethods' => [
        'request' => [
            'url' => '/users/1000000000user/methods',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'id'            => '100000userbank',
                        'user_id'       => '1000000000user',
                        'method'        => 'netbanking',
                        'bank'          => 'HDFC',
                    ],
                    [
                        'id'            => '1000userwallet',
                        'user_id'       => '1000000000user',
                        'method'        => 'wallet',
                        'wallet'        => 'paytm',
                    ],

                ]

            ],
        ],
    ],

    'testGetUserMethod' => [
        'request' => [
            'url' => '/users/1000000000user/methods/1000userwallet',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'id'            => '1000userwallet',
                'user_id'       => '1000000000user',
                'method'        => 'wallet',
                'wallet'        => 'paytm',
            ],
        ],
    ],

    'testDeleteUserMethod' => [
        'request' => [
            'url' => '/users/1000000000user/methods/1000userwallet',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAddUserMethodCard' => [
        'request' => [
            'url' => '/users/1000000000user/methods',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card_id' => '10000savedcard',
            ],
        ],
        'response' => [
            'content' => [
                'method' => 'card',
                'card_id' => '10000savedcard',
                'wallet' => null,
                'bank' => null
            ],
        ],
    ],

    'testAddUserMethodWallet' => [
        'request' => [
            'url' => '/users/1000000000user/methods',
            'method' => 'post',
            'content' => [
                'method' => 'wallet',
                'wallet' => 'paytm',
            ],
        ],
        'response' => [
            'content' => [
                'method' => 'wallet',
                'card_id' => null,
                'wallet' => 'paytm',
                'bank' => null,
            ],
        ],
    ],

    'testAddUserMethodNetbanking' => [
        'request' => [
            'url' => '/users/1000000000user/methods',
            'method' => 'post',
            'content' => [
                'method' => 'netbanking',
                'bank' => 'HDFC',
            ],
        ],
        'response' => [
            'content' => [
                'method' => 'netbanking',
                'card_id' => null,
                'wallet' => null,
                'bank' => 'HDFC',
            ],
        ],
    ]
];