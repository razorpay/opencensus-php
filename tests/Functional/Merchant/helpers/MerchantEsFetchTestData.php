<?php

use RZP\Error\ErrorCode;

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
        'request'  => [
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

    'testGetMerchantsFromEsByMiqSharingDateHappy' => [
        'request'  => [
            'url'     => '/admins/unified_dashboard_merchants',
            'method'  => 'GET',
            'content' => [
                'miq_sharing_date' => strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
                'testing_credentials_date' => strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'admin'  => true,
                'items'  => [
                    [
                        'id'              => '10000000000011',
                        'org_id'          => '100000razorpay',
                        'name'            => 'jitendra ojha',
                        'email'           => 'email.ojha@test.com',
                        'parent_id'       => null,
                        'activated'       => true,
                        'archived_at'     => null,
                        'suspended_at'    => null,
                        'website'         => 'www.ojha.test',
                        'billing_label'   => 'Ojha Label',
                        'tag_list'        => [],
                        'is_marketplace'  => false,
                        'referrer'        => null,
                        'merchant_business_detail' => [
                            'miq_sharing_date' => strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
                            'testing_credentials_date' => strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
                        ],
                        'merchant_detail' => [
                            'merchant_id'         => '10000000000011',
                            'steps_finished'      => '[]',
                            'activation_progress' => 0,
                            'submitted_at'        => null,
                        ],
                        'entity' => 'merchant',
                        'admin'  => true,
                    ],
                ],
                'total_merchants_onboarded' => 1
            ],
        ],
    ],
    'testGetMerchantsFromEsByMiqSharingDateUnHappy' => [
        'request'  => [
            'url'     => '/admins/unified_dashboard_merchants',
            'method'  => 'GET',
            'content' => [
                'miq_sharing_date' => strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
                'testing_credentials_date' => strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'Access Denied',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testGetMerchantsFromEsByBusinessTypeRegistered' => [
        'request'  => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'business_type_bucket' => 'registered',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMerchantsFromEsByBusinessTypeNonRegistered' => [
        'request'  => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'business_type_bucket' => 'unregistered',
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

    'testGetMerchantsFromEsByPartnerType' => [
        'request' => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'partner_type' => 'reseller',
            ],
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'id'           => '10000000000014',
                        'partner_type' => 'reseller',
                    ]
                ],
            ],
        ],
    ],

    'testGetMerchantsFromEsByActivationSource' => [
        'request'  => [
            'url'     => '/admins/merchants',
            'method'  => 'GET',
            'content' => [
                'activation_source' => 'banking',
            ],
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'id'                => '10000000000014',
                        'activation_source' => 'banking',
                    ]
                ],
            ],
        ],
    ],

    'testGetPartnerActivationFromEsByActivationStatus' => [
        'request' => [
            'url'     => '/admins/partner/activation',
            'method'  => 'GET',
            'content' => [
                'activation_status' => 'under_review',
            ],
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'merchant_id'       => '10000000000014',
                        'activation_status' => 'under_review',
                    ]
                ],
            ],
        ],
    ],

    'testGetPartnerActivationFromEsByQ' => [
        'request' => [
            'url'     => '/admins/partner/activation',
            'method'  => 'GET',
            'content' => [
                'q' => 'test',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],
];
