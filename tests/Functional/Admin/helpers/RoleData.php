<?php

return [
    'testCreateRole' => [
        'request' => [
            'url' => '/orgs/org_6dLbNSpv5XbCOF/roles',
            'method' => 'post',
            'content' => [
                'name' => 'manager',
                'description' => 'Manager of roles',
            ],
        ],
        'response' => [
            'content' => [
                // 'entity'      => 'role',
                'name'        => 'manager',
                'description' => 'Manager of roles',
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
                // 'entity'      => 'role',
                'name'        => 'manager',
                'description' => 'Manager of roles',
                'org_id'      => 'org_6dLbNSpv5XbCOF',
            ],
            'status_code' => 200,
        ],
    ],
];
