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
                'org_id'      => 'org_6dLbNSpv5XbCOF',
            ],
            'status_code' => 200,
        ],
    ],
    'testGetRole' => [
        'request' => [
            'url' => '/orgs/org_6dLbNSpv5XbCOF/roles',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity'      => 'role',
                'name'        => 'manager',
                'description' => 'Manager of roles',
                'org_id'      => 'org_6dLbNSpv5XbCOF',
            ],
            'status_code' => 200,
        ],
    ],
];
