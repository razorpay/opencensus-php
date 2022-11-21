<?php

return [
    'testCreateMerchant' => [
        'request' => [
            'content' => [
                'id'     => '1X4hRFHFx4UiXt',
                'name'   => 'Test',
                'email'  => 'test@test.com',
                'groups' => [
                    '10000000000035',
                    '10000000000036',
                ],
                'admins' => [
                    'admin_10000000000016',
                    'admin_10000000000018',
                ],
                'country_code' => 'IN'
            ],
            'url'    => '/merchants',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
                'name'  => 'Test',
                'email' => 'test@test.com',
            ],
        ],
    ],

    'testCreateMerchantMalaysia' => [
        'request' => [
            'content' => [
                'id'     => '1X4hRFHFx4UiXt',
                'name'   => 'Test',
                'email'  => 'test@test.com',
                'groups' => [
                    '10000000000035',
                    '10000000000036',
                ],
                'admins' => [
                    'admin_10000000000016',
                    'admin_10000000000018',
                ],
                'country_code' => 'MY'
            ],
            'url'    => '/merchants',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
                'name'  => 'Test',
                'email' => 'test@test.com',
            ],
        ],
    ],

    'testUpdateMerchantWithBasicDatapoints' => [
        'request' => [
            'content' => [
                'name' => 'Updated New Name',
            ],
            'url'    => '/merchants/10000000000016',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'id'   => '10000000000016',
                'name' => 'Updated New Name',
            ],
        ],
    ],

    'testUpdateMerchantWithGroups' => [
        'request' => [
            'content' => [
                'name'   => 'Updated New Name Again',
                'groups' => [
                    'grp_10000000000032',
                    'grp_10000000000036',
                    'grp_10000000000038',
                ],
            ],
            'url'    => '/merchants/10000000000016',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'id'   => '10000000000016',
                'name' => 'Updated New Name Again',
            ],
        ],
    ],

    'testUpdateMerchantWithAdminsAndGroups' => [
        'request' => [
            'content' => [
                'name'   => 'Updated New Name Again Again',
                'groups' => [
                    'grp_10000000000032',
                    'grp_10000000000039',
                ],
                'admins' => [
                    'admin_10000000000016',
                    'admin_10000000000017',
                    'admin_10000000000018',
                ],
            ],
            'url'    => '/merchants/10000000000016',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'id'   => '10000000000016',
                'name' => 'Updated New Name Again Again',
            ],
        ],
    ],

    'testAddMerchantTags' => [
        'request' => [
            'url'     => '/merchants/10000000000016/tags',
            'method'  => 'POST',
            'content' => [
                'tags' => [
                    'First',
                    'Second',
                    'Third',
                    'Fourth',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'First',
                'Second',
                'Third',
                'Fourth',
            ],
        ],
    ],

    'testRemoveMerchantTag' => [
        'request' => [
            'url'     => '/merchants/10000000000016/tags/First',
            'method'  => 'DELETE',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'Second',
            ],
        ],
    ],

    //
    // Es document assertions data
    //

    'testCreateMerchantExpectedEsTestDoc' => [
        'id'              => '1X4hRFHFx4UiXt',
        'org_id'          => '100000razorpay',
        'name'            => 'Test',
        'email'           => 'test@test.com',
        'parent_id'       => null,
        'activated'       => false,
        'activated_at'    => null,
        'archived_at'     => null,
        'suspended_at'    => null,
        'website'         => null,
        'billing_label'   => 'Test',
        // 'created_at'      => 1504620540,
        // 'updated_at'      => 1504620540,
        'tag_list'        => [],
        'merchant_detail' => [
            'merchant_id'         => '1X4hRFHFx4UiXt',
            'steps_finished'      => '[]',
            'activation_progress' => 0,
            'submitted_at'        => null,
            // 'updated_at'          => 1504620540
        ],
        'admins'          => [
            '10000000000016',
            '10000000000018',
        ],
        'groups'          => [
            '10000000000035',
            '10000000000036',
            '10000000000030',
            '10000000000031',
            '10000000000032',
            '10000000000033',
            '10000000000028',
            '10000000000029',
        ],
        'is_marketplace'  => false,
        'referrer'        => 'test admin'
    ],

    'testCreateMerchantExpectedEsLiveDoc' => [
        'id'              => '1X4hRFHFx4UiXt',
        'org_id'          => '100000razorpay',
        'name'            => 'Test',
        'email'           => 'test@test.com',
        'parent_id'       => null,
        'activated'       => false,
        'activated_at'    => null,
        'archived_at'     => null,
        'suspended_at'    => null,
        'website'         => null,
        'billing_label'   => 'Test',
        // 'created_at'      => 1504620540,
        // 'updated_at'      => 1504620540,
        'tag_list'        => [],
        'merchant_detail' => [
            'merchant_id'         => '1X4hRFHFx4UiXt',
            'steps_finished'      => '[]',
            'activation_progress' => 0,
            'submitted_at'        => null,
            // 'updated_at'          => 1504620540
        ],
        // Admins and groups doesn't get synced to other mode.
        'is_marketplace'  => false,
        'referrer'        => null,
    ],

    'testCreateMerchantMalaysiaExpectedEsTestDoc' => [
        'id'              => '1X4hRFHFx4UiXt',
        'org_id'          => '100000razorpay',
        'name'            => 'Test',
        'email'           => 'test@test.com',
        'parent_id'       => null,
        'activated'       => false,
        'activated_at'    => null,
        'archived_at'     => null,
        'suspended_at'    => null,
        'website'         => null,
        'billing_label'   => 'Test',
        // 'created_at'      => 1504620540,
        // 'updated_at'      => 1504620540,
        'tag_list'        => [],
        'merchant_detail' => [
            'merchant_id'         => '1X4hRFHFx4UiXt',
            'steps_finished'      => '[]',
            'activation_progress' => 0,
            'submitted_at'        => null,
            // 'updated_at'          => 1504620540
        ],
        'admins'          => [
            '10000000000016',
            '10000000000018',
        ],
        'groups'          => [
            '10000000000035',
            '10000000000036',
            '10000000000030',
            '10000000000031',
            '10000000000032',
            '10000000000033',
            '10000000000028',
            '10000000000029',
        ],
        'is_marketplace'  => false,
        'referrer'        => 'test admin'
    ],

    'testCreateMerchantMalaysiaExpectedEsLiveDoc' => [
        'id'              => '1X4hRFHFx4UiXt',
        'org_id'          => '100000razorpay',
        'name'            => 'Test',
        'email'           => 'test@test.com',
        'parent_id'       => null,
        'activated'       => false,
        'activated_at'    => null,
        'archived_at'     => null,
        'suspended_at'    => null,
        'website'         => null,
        'billing_label'   => 'Test',
        // 'created_at'      => 1504620540,
        // 'updated_at'      => 1504620540,
        'tag_list'        => [],
        'merchant_detail' => [
            'merchant_id'         => '1X4hRFHFx4UiXt',
            'steps_finished'      => '[]',
            'activation_progress' => 0,
            'submitted_at'        => null,
            // 'updated_at'          => 1504620540
        ],
        // Admins and groups doesn't get synced to other mode.
        'is_marketplace'  => false,
        'referrer'        => null,
    ],
    //
    // In following test data, many attributes are intentionally
    // missing for convenience; They are redundant for assertions as well.
    //

    'testUpdateMerchantWithBasicDatapointsExpectedEsTestDoc' => [
        'id'       => '10000000000016',
        'org_id'   => '100000razorpay',
        'name'     => 'Updated New Name',
        'tag_list' => [
            'First',
            'Second',
        ],
    ],

    'testUpdateMerchantWithBasicDatapointsExpectedEsLiveDoc' => [
        'id'       => '10000000000016',
        'org_id'   => '100000razorpay',
        'name'     => 'Updated New Name',
        'tag_list' => [],
    ],

    'testUpdateMerchantWithGroupsExpectedEsTestDoc' => [
        'id'     => '10000000000016',
        'org_id' => '100000razorpay',
        'name'   => 'Updated New Name Again',
        'groups' => [
            '10000000000032',
            '10000000000036',
            '10000000000038',
            '10000000000028',
            '10000000000033',
            '10000000000034',
            '10000000000029',
            '10000000000030',
        ],
        'admins' => [],
    ],

    'testUpdateMerchantWithGroupsExpectedEsLiveDoc' => [
        'id'     => '10000000000016',
        'org_id' => '100000razorpay',
        'name'   => 'Updated New Name Again',
        'groups' => [],
        'admins' => [],
    ],

    'testUpdateMerchantWithAdminsAndGroupsExpectedEsTestDoc' => [
        'id'     => '10000000000016',
        'org_id' => '100000razorpay',
        'name'   => 'Updated New Name Again Again',
        'groups' => [
            '10000000000032',
            '10000000000039',
            '10000000000028',
            '10000000000037',
            '10000000000033',
            '10000000000029',
        ],
        'admins' => [
            '10000000000016',
            '10000000000017',
            '10000000000018',
        ],
    ],

    'testUpdateMerchantWithAdminsAndGroupsExpectedEsLiveDoc' => [
        'id'     => '10000000000016',
        'org_id' => '100000razorpay',
        'name'   => 'Updated New Name Again Again',
        'groups' => [],
        'admins' => [],
    ],

    'testAddMerchantTagsExpectedEsTestDoc' => [
        'id'       => '10000000000016',
        'org_id'   => '100000razorpay',
        'tag_list' => [
            'First',
            'Second',
            'Third',
            'Fourth',
        ],
    ],

    'testAddMerchantTagsExpectedEsLiveDoc' => [
        'id'       => '10000000000016',
        'org_id'   => '100000razorpay',
        'tag_list' => [],
    ],

    'testRemoveMerchantTagExpectedEsTestDoc' => [
        'id'       => '10000000000016',
        'org_id'   => '100000razorpay',
        'tag_list' => [
            'Second',
        ],
    ],

    'testRemoveMerchantTagExpectedEsLiveDoc' => [
        'id'       => '10000000000016',
        'org_id'   => '100000razorpay',
        'tag_list' => [],
    ],
];
