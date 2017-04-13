<?php

return [

    'testGetPermission' => [
        'request' => [
            'url' => '/permissions',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'name' => 'test permission',
                'description' => 'test description',
                'category' => 'test category',
            ],
            'status_code' => 200,
        ],
    ],

    'testGetMultipleForRazorpayOrg' => [
        'request' => [
            'url' => '/orgs/%s/permissions',
            'method' => 'get',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePermission' => [
        'request' => [
            'url' => '/permissions',
            'method' => 'post',
            'content' => [
                'name' => 'test permission',
                'description' => 'test description',
                'category' => 'test category',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'test permission',
                'description' => 'test description',
                'category' => 'test category',
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePermissionWithOrg' => [
        'request' => [
            'url' => '/permissions',
            'method' => 'post',
            'content' => [
                'name' => 'test permission',
                'description' => 'test description',
                'category' => 'test category',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'test permission',
                'description' => 'test description',
                'category' => 'test category',
            ],
            'status_code' => 200,
        ],
    ],

    'testDeletePermission' => [
        'request' => [
            'url' => '/permissions',
            'method' => 'delete',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'deleted' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testEditPermission' => [
        'request' => [
            'url' => '/permissions',
            'method' => 'put',
            'content' => [
                'description' => 'test desc2',
            ],
        ],
        'response' => [
            'content' => [
                'description' => 'test desc2',
            ],
            'status_code' => 200,
        ],
    ],

    'testGetRolesForPermission' => [
        'request' => [
            'url'    => '/permissions/%s/roles',
            'method' => 'get',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ]
];
