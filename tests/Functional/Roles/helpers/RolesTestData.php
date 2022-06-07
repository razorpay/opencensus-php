<?php

return [
    'testFetchRoles' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/access/roles',
            'content' => [
                'type'          => 'custom',
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'custom' =>
                    array (
                        0 =>
                            array (
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC 1',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'members' => 3,
                            ),
                        1 =>
                            array (
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC 2',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'members' => 2,
                            ),
                        2 =>
                            array (
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC 3',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'members' => 1,
                            ),
                    ),
            ],
        ],
    ],
];
