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
                'name'    => 'test',
                'email'   => 'test@razorpay.com',
                'contact' => '1234567890',
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'test',
                'email' => 'test@razorpay.com',
                'contact' => '1234567890',
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
];