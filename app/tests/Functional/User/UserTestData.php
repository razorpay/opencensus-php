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
    
];