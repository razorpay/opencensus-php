<?php

return [
    'testCreatePermission' => [
        'request' => [
            'url' => '/permissions',
            'method' => 'post',
            'content' => [
                'name' => 'see_all_merchants',
                'description' => 'Ability to see all merchants',
            ],
        ],
        'response' => [
            'content' => [
                'name'        => 'see_all_merchants',
                'description' => 'Ability to see all merchants',
            ],
            'status_code' => 200,
        ],
    ],
    'testGetPermission' => [
        'request' => [
            'url' => '/permissions',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'name'        => 'see_all_merchants',
                'description' => 'Ability to see all merchants',
            ],
            'status_code' => 200,
        ],
    ],
];
