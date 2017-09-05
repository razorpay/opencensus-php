<?php

return [
    'testCreateMerchant' => [
        'request' => [
            'content' => [
                'id'     => '1X4hRFHFx4UiXt',
                'name'   => 'Test',
                'email'  => 'test@test.com',
                'groups' => [
                    '10000000000012',
                    '10000000000019',
                    '10000000000027',
                ],
                'admins' => [
                    'admin_10000000000016',
                    'admin_10000000000018',
                ],
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
        'tags'            => [],
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
            '10000000000012',
            '10000000000019',
            '10000000000027',
            '10000000000013',
            '10000000000026',
            '10000000000020',
            '10000000000021',
            '10000000000014',
            '10000000000015',
            '10000000000011',
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
        'tags'            => [],
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
];
