<?php

return [
    'testGetMerchantsFromEsByQ' => [
        'request' => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'q'              => 'jitendra',
                'account_status' => 'activated',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMerchantsFromEsByAccountStatusAll' => [
        'request' => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'account_status' => 'all',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMerchantsFromEsByAccountStatusArchived' => [
        'request' => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'account_status' => 'archived',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMerchantsFromEsByQAndAccountStatusArchived' => [
        'request' => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'q'              => 'ojha',
                'account_status' => 'archived',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMerchantsFromEsByAllSubAccounts' => [
        'request' => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'sub_accounts' => '1',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMerchantsFromEsBySubAccounts' => [
        'request' => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'sub_accounts' => '10000000000012',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMerchantsFromEsByQAndSubAccounts' => [
        'request' => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'q'            => 'shashank',
                'sub_accounts' => '10000000000013',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMerchantsFromEsByAccountStatusAndSubAccounts' => [
        'request' => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'account_status' => 'archived',
                'sub_accounts'   => '1',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMerchantsFromEsByQAndAssertExactResponse' => [
        'request' => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'q'              => 'jitendra',
                'account_status' => 'activated',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'admin'  => true,
                'items'  => [
                    [
                        'id'             => '10000000000012',
                        'org_id'         => '100000razorpay',
                        'name'           => 'jitendra selva',
                        'email'          => 'email.selva@test.com',
                        'parent_id'      => null,
                        'activated'      => true,
                        // 'activated_at'   => 1504638504,
                        'archived_at'    => null,
                        'suspended_at'   => null,
                        'website'        => 'www.selva.test',
                        'billing_label'  => 'Selva Label',
                        // 'created_at'     => 1504638504,
                        // 'updated_at'     => 1504638504,
                        'tag_list'       => [],
                        'is_marketplace' => false,
                        'referrer'       => 'test admin',
                        'merchant_detail' => [
                            'merchant_id'         => '10000000000012',
                            'steps_finished'      => '[]',
                            'activation_progress' => 0,
                            'submitted_at'        => null,
                            // 'updated_at'          => 1504638504,
                        ],
                        'entity' => 'merchant',
                        'admin'  => true,
                    ],
                    [
                        'id'              => '10000000000011',
                        'org_id'          => '100000razorpay',
                        'name'            => 'jitendra ojha',
                        'email'           => 'email.ojha@test.com',
                        'parent_id'       => null,
                        'activated'       => true,
                        // 'activated_at'    => 1504638504,
                        'archived_at'     => null,
                        'suspended_at'    => null,
                        'website'         => 'www.ojha.test',
                        'billing_label'   => 'Ojha Label',
                        // 'created_at'      => 1504638504,
                        // 'updated_at'      => 1504638504,
                        'tag_list'        => [],
                        'is_marketplace'  => false,
                        'referrer'        => null,
                        'merchant_detail' => [
                            'merchant_id'         => '10000000000011',
                            'steps_finished'      => '[]',
                            'activation_progress' => 0,
                            'submitted_at'        => null,
                            // 'updated_at'          => 1504638504,
                        ],
                        'entity' => 'merchant',
                        'admin'  => true,
                    ],
                ],
            ],
        ],
    ],

    'testGetMerchantIdsFromEsByQAndAssertExactResponse' => [
        'request' => [
            'url'     => '/admins/merchant_ids',
            'method'  => 'GET',
            'content' => [
                'q'              => 'jitendra',
                'account_status' => 'activated',
            ],
        ],
        'response' => [
            'content' => [
                '10000000000011' => null,
                '10000000000015' => null,
                '10000000000014' => null,
                '10000000000012' => 'test admin',
                '10000000000013' => null,
            ],
        ],
    ],
];
