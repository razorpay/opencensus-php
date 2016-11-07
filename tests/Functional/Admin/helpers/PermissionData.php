<?php

return [
    'testCreatePermission' => [
        'request' => [
            'url' => '/orgs/org_6dLbNSpv5XbCOF/permissions',
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
            'url' => '/orgs/org_6dLbNSpv5XbCOF/permissions',
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
