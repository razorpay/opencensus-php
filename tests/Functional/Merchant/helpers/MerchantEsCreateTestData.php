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
];
