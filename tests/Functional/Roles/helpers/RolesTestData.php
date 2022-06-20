<?php

return [
    'testFetchRoles' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/cac/roles',
            'content' => [
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => array (
                'custom' =>
                    array (
                        0 =>
                            array (
                                'id' => '100customRole1',
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC 1',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'members' => 3,
                            ),
                        1 =>
                            array (
                                'id' => '100customRole2',
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC 2',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'members' => 2,
                            ),
                        2 =>
                            array (
                                'id' => '100customRole3',
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC 3',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'members' => 1,
                            ),
                    ),
                'standard' =>
                    array (
                        0 =>
                            array (
                                'id' => 'finance_l1',
                                'merchant_id' => '100000merchant',
                                'name' => 'finance_l1',
                                'description' => 'Standard role - finance_l1',
                                'type' => 'standard',
                                'members' => 0,
                            ),
                    ),
            ),
        ],
    ],

    'testFetchRoleByIdStandardRole' => [
        'request'  => [
            'method'  => 'GET',
            'url'   => '/cac/role/owner_test',
            'content'   => [],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => array (
                'id' => 'owner_test',
                'name' => 'owner_test',
                'description' => 'Standard role - owner_test',
                'type' => 'standard',
                'merchant_id' => '100000merchant',
                'access_policy_ids' =>
                    array (
                        0 => 'accessPolicy14',
                        1 => 'accessPolicy15',
                        2 => 'accessPolicy16',
                    ),
            )
        ],
    ],

    'testCreateRole' => [
        'request'  => [
            'method'  => 'POST',
            'url'   => '/cac/role',
            'content'   => [
                'name' => 'test role',
                'description' => 'test description',
                'type' => 'custom',
                'access_policy_ids' => ["XaccessPolicy1"]
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => array (
                'name' => 'test role',
                'description' => 'test description',
                'type' => 'custom',
                'merchant_id' => '100000merchant',
            )
        ],
    ],

    'testEditRole' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'   => '/cac/role/100customRole1',
            'content'   => [
                'name' => 'test role edit',
                'description' => 'test description edit',
                'access_policy_ids' => ["XaccessPolicy2"]
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => array (
                'name' => 'test role edit',
                'description' => 'test description edit',
                'type' => 'custom',
                'merchant_id' => '100000merchant',
            )
        ],
    ],

    'testFetchRoleByIdCustomRole' => [
    'request'  => [
        'method'  => 'GET',
        'url'   => '/cac/role',
        'content'   => [],
        'server' => [
            'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
        ],
    ],
    'response' => [
        'content' => array (
            'id' => '100customRole2',
            'name' => 'CAC 2',
            'description' => 'Test custom role',
            'type' => 'custom',
            'merchant_id' => '100000merchant',
            'access_policy_ids' =>
                array (
                    0 => 'accessPolicy10',
                    1 => 'accessPolicy11',
                    2 => 'accessPolicy13',
                ),
        )
    ],
],

    'testDeleteRole' => [
        'request'  => [
            'method'  => 'DELETE',
            'url'   => '/cac/role',
            'content'   => [],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => array ()
        ],
    ],
];
