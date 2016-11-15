<?php

return [
    'testCreateRole' => [
        'request' => [
            'url' => '/orgs/%s/roles',
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
            ],
            'status_code' => 200,
        ],
    ],
    'testGetRole' => [
        'request' => [
            'url' => '/orgs/%s/roles/%s',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                // 'entity'      => 'role',
                'name'        => 'manager',
                'description' => 'Manager of roles',
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateRoleWithPermissions' => [
        'request' => [
            'url' => '/orgs/%s/roles',
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
            ],
            'status_code' => 200,
        ],
    ],

    'testDeleteRole' => [
        'request' => [
            'url' => '/orgs/%s/roles/%s',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],
];
