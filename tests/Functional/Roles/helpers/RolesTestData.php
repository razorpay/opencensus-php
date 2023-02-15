<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testGetRolesForPermissionName' => [
        'request' => [
            'url'    => '/cac/admin/role_map',
            'method' => 'get',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],

    'testFetchRoleMap' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/cac/role_map',
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
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        1 =>
                            array (
                                'id' => '100customRole2',
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC 2',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        2 =>
                            array (
                                'id' => '100customRole3',
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC 3',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                    ),
                'standard' =>
                    array (
                        0 =>
                            array (
                                'id' => 'admin',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Admin',
                                'description' => 'Perform all tasks except for team management',
                                'type' => 'standard',
                                'copy_disable' => true,
                                'members' => 0,
                            ),
                        1 =>
                            array (
                                'id' => 'owner',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Owner',
                                'description' => NULL,
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        2 =>
                            array (
                                'id' => 'vendor',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Vendor',
                                'description' => NULL,
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        3 =>
                            array (
                                'id' => 'finance',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Finance',
                                'description' => 'Create and issue payouts and contacts',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        4 =>
                            array (
                                'id' => 'operations',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Operations',
                                'description' => 'Create and manage Payout Links',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        5 =>
                            array (
                                'id' => 'chartered_accountant',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Chartered Accountant',
                                'description' => 'Export reports only. No dashboard access',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        6 =>
                            array (
                                'id' => 'view_only',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'View Only',
                                'description' => 'View data only and download reports',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                    ),
            )
        ],
    ],

    'testFetchRoleMapWithOnlyStandardRoles' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/cac/role_map',
            'content' => [
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => array (
                'standard' =>
                    array (
                        0 =>
                            array (
                                'id' => 'admin',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Admin',
                                'description' => 'Perform all tasks except for team management',
                                'type' => 'standard',
                                'copy_disable' => true,
                                'members' => 0,
                            ),
                        1 =>
                            array (
                                'id' => 'owner',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Owner',
                                'description' => NULL,
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        2 =>
                            array (
                                'id' => 'vendor',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Vendor',
                                'description' => NULL,
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        3 =>
                            array (
                                'id' => 'finance',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Finance',
                                'description' => 'Create and issue payouts and contacts',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        4 =>
                            array (
                                'id' => 'operations',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Operations',
                                'description' => 'Create and manage Payout Links',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        5 =>
                            array (
                                'id' => 'chartered_accountant',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Chartered Accountant',
                                'description' => 'Export reports only. No dashboard access',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        6 =>
                            array (
                                'id' => 'view_only',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'View Only',
                                'description' => 'View data only and download reports',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                    ),
            )
        ],
    ],

    'testFetchRolesWithStandardRolesOnly' => [
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
                'standard' =>
                    array (
                        0 =>
                            array (
                                'id' => 'admin',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Admin',
                                'description' => 'Perform all tasks except for team management',
                                'type' => 'standard',
                                'copy_disable' => true,
                                'members' => 0,
                            ),
                        1 =>
                            array (
                                'id' => 'finance',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Finance',
                                'description' => 'Create and issue payouts and contacts',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        2 =>
                            array (
                                'id' => 'operations',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Operations',
                                'description' => 'Create and manage Payout Links',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        3 =>
                            array (
                                'id' => 'chartered_accountant',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Chartered Accountant',
                                'description' => 'Export reports only. No dashboard access',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        4 =>
                            array (
                                'id' => 'view_only',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'View Only',
                                'description' => 'View data only and download reports',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                    ),
            )
        ],
    ],

    'testFetchRoleMap' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/cac/role_map',
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
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        1 =>
                            array (
                                'id' => '100customRole2',
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC 2',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        2 =>
                            array (
                                'id' => '100customRole3',
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC 3',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                    ),
                'standard' =>
                    array (
                        0 =>
                            array (
                                'id' => 'owner',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Owner',
                                'description' => NULL,
                                'type' => 'standard',
                                'copy_disable' => true,
                                'members' => 0,
                            ),
                        1 =>
                            array (
                                'id' => 'admin',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Admin',
                                'description' => 'Perform all tasks except for team management',
                                'type' => 'standard',
                                'copy_disable' => true,
                                'members' => 0,
                            ),
                        2 =>
                            array (
                                'id' => 'finance',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Finance',
                                'description' => 'Create and issue payouts and contacts',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        3 =>
                            array (
                                'id' => 'operations',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Operations',
                                'description' => 'Create and manage Payout Links',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        4 =>
                            array (
                                'id' => 'chartered_accountant',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Chartered Accountant',
                                'description' => 'Export reports only. No dashboard access',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        5 =>
                            array (
                                'id' => 'view_only',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'View Only',
                                'description' => 'View data only and download reports',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        6 =>
                            array (
                                'id' => 'vendor',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Vendor',
                                'description' => NULL,
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                    ),
            )
        ],
    ],

    'testFetchRoleMapWithOnlyStandardRoles' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/cac/role_map',
            'content' => [
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => array (
                'standard' =>
                    array (
                        0 =>
                            array (
                                'id' => 'owner',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Owner',
                                'description' => NULL,
                                'type' => 'standard',
                                'copy_disable' => true,
                                'members' => 0,
                            ),
                        1 =>
                            array (
                                'id' => 'admin',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Admin',
                                'description' => 'Perform all tasks except for team management',
                                'type' => 'standard',
                                'copy_disable' => true,
                                'members' => 0,
                            ),
                        2 =>
                            array (
                                'id' => 'finance',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Finance',
                                'description' => 'Create and issue payouts and contacts',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        3 =>
                            array (
                                'id' => 'operations',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Operations',
                                'description' => 'Create and manage Payout Links',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        4 =>
                            array (
                                'id' => 'chartered_accountant',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Chartered Accountant',
                                'description' => 'Export reports only. No dashboard access',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        5 =>
                            array (
                                'id' => 'view_only',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'View Only',
                                'description' => 'View data only and download reports',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        6 =>
                            array (
                                'id' => 'vendor',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Vendor',
                                'description' => NULL,
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                    ),
            )
        ],
    ],

    'testFetchRolesWithNoExistingFinanceUser' => [
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
                                'copy_disable' => false,
                                'members' => 3,
                            ),
                        1 =>
                            array (
                                'id' => '100customRole2',
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC 2',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'copy_disable' => false,
                                'members' => 2,
                            ),
                        2 =>
                            array (
                                'id' => '100customRole3',
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC 3',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'copy_disable' => false,
                                'members' => 1,
                            ),
                    ),
                'standard' =>
                    array (
                        0 =>
                            array (
                                'id' => 'admin',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Admin',
                                'description' => 'Perform all tasks except for team management',
                                'type' => 'standard',
                                'copy_disable' => true,
                                'members' => 0,
                            ),
                        1 =>
                            array (
                                'id' => 'finance',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Finance',
                                'description' => 'Create and issue payouts and contacts',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        2 =>
                            array (
                                'id' => 'operations',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Operations',
                                'description' => 'Create and manage Payout Links',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        3 =>
                            array (
                                'id' => 'chartered_accountant',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Chartered Accountant',
                                'description' => 'Export reports only. No dashboard access',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        4 =>
                            array (
                                'id' => 'view_only',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'View Only',
                                'description' => 'View data only and download reports',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                    ),
            )
        ],
    ],

    'testFetchRolesWithExistingFinanceUser' => [
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
                                'id' => '100customRole3',
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC A',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'copy_disable' => false,
                                'members' => 1,
                            ),
                        1 =>
                            array (
                                'id' => '100customRole2',
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC B',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'copy_disable' => false,
                                'members' => 2,
                            ),
                        2 =>
                            array (
                                'id' => '100customRole1',
                                'merchant_id' => '100000merchant',
                                'name' => 'CAC C',
                                'description' => 'Test custom role',
                                'type' => 'custom',
                                'copy_disable' => false,
                                'members' => 3,
                            ),
                    ),
                'standard' =>
                    array (
                        0 =>
                            array (
                                'id' => 'admin',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Admin',
                                'description' => 'Perform all tasks except for team management',
                                'type' => 'standard',
                                'copy_disable' => true,
                                'members' => 0,
                            ),
                        1 =>
                            array (
                                'id' => 'finance_l1',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Finance L1',
                                'description' => 'Create and issue payouts and contacts',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 1,
                            ),
                        2 =>
                            array (
                                'id' => 'finance_l2',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Finance L2',
                                'description' => 'Create and issue payouts and contacts',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        3 =>
                            array (
                                'id' => 'finance_l3',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Finance L3',
                                'description' => 'Create and issue payouts and contacts',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        4 =>
                            array (
                                'id' => 'operations',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Operations',
                                'description' => 'Create and manage Payout Links',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        5 =>
                            array (
                                'id' => 'chartered_accountant',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'Chartered Accountant',
                                'description' => 'Export reports only. No dashboard access',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                        6 =>
                            array (
                                'id' => 'view_only',
                                'merchant_id' => '100000Razorpay',
                                'name' => 'View Only',
                                'description' => 'View data only and download reports',
                                'type' => 'standard',
                                'copy_disable' => false,
                                'members' => 0,
                            ),
                    ),
            )
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
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
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

    'testEditRoleSendEmail' => [
        'request'  => [
            'method'  => 'PATCH',
            'url'   => '/cac/role/100customRole1',
            'content'   => [
                'name' => 'test role edit',
                'description' => 'test description edit',
                'access_policy_ids' => ["XaccessPolicy2"]
            ],
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
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

    'testFetchSelfRole' => [
        'request'  => [
            'method'  => 'GET',
            'url'   => '/cac/self/role',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => array (
                'id' => 'owner',
                'name' => 'Owner',
                'description' => NULL,
                'type' => 'standard',
                'merchant_id' => '100000Razorpay',
                'access_policy_ids' => [],
                'members' => 1,
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

    'testFetchAuthZRolesByRoleIdSuccess' => [
        'request'  => [
            'method'  => 'GET',
            'url'   => '/cac/role/100customRole2/authz_roles',
            'content'   => [],
        ],
        'response' => [
            'content' => array (
                'role_id' => '100customRole2',
                'authz_roles' =>
                    array (
                        0 => 'authz_roles_1',
                        1 => 'authz_roles_2',
                        2 => 'authz_roles_3',
                    ),
            ),
        ],
    ],
    'testFetchAuthZRolesByRoleIdFailure' => [
        'request'  => [
            'method'  => 'GET',
            'url'   => '/cac/role/100customRole2/authz_roles',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_AUTHZ_ROLES_NOT_FOUND,
                ],
            ],
            'status_code' => 404,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_AUTHZ_ROLES_NOT_FOUND ,
        ],
    ],
];
