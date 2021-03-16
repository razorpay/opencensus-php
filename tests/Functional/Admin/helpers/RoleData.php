<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateRole' => [
        'request' => [
            'url' => '/roles',
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
            'url' => '/roles/%s',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'name'        => 'SuperAdmin',
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateRoleWithPermissions' => [
        'request' => [
            'url' => '/roles',
            'method' => 'post',
            'content' => [
                'name' => 'manager',
                'description' => 'Manager of roles',
            ],
        ],
        'response' => [
            'content' => [
                'name'        => 'manager',
                'description' => 'Manager of roles',
            ],
            'status_code' => 200,
        ],
    ],

    'testEditRoleDeleteAllPermissions' => [
        'request' => [
            'url' => '/roles/%s',
            'method' => 'put',
            'content' => [
                'permissions' => [],
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testEditRoleEditPermissions' => [
        'request' => [
            'url' => '/roles/%s',
            'method' => 'put',
            'content' => [],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testAddPermissionsToRole' => [
        'request' => [
            'url' => '/roles/add/permissions',
            'method' => 'put',
            'content' => [],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testDeleteRole' => [
        'request' => [
            'url' => '/roles/%s',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'deleted' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testEditRole' => [
        'request' => [
            'url' => '/roles/%s',
            'method' => 'put',
            'content' => [
                'name' => 'test Admin LOL',
            ],
        ],
        'response' => [
            'content' => [
                'name'        => 'test Admin LOL',
            ],
            'status_code' => 200,
        ],
    ],

    'testGetMultipleRoles' => [
        'request' => [
            'url' => '/roles',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testDuplicateRole' => [
        'request' => [
            'url' => '/roles',
            'method' => 'post',
            'content' => [
                'description' => 'Manager of roles',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'error_description' => 'The role with the name already exists',
        ],
    ],

    'testEditSuperAdminRole' => [
        'request' => [
            'url' => '/roles/%s',
            'method' => 'put',
            'content' => [
                'description' => 'Super Admin Role edited',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUPERADMIN_ROLE_NOT_EDITABLE,
            'error_description' => 'SuperAdmin Role is not editable',
        ],
    ],

    'testEditSuperAdminRoleByRazorpay' => [
        'request' => [
            'url' => '/roles/%s',
            'method' => 'put',
            'content' => [
                'name' => 'test edit super admin',
                'description' => 'Super Admin Role edited',
            ],
        ],
        'response' => [
            'content' => [
                    'name' => 'test edit super admin',
                    'description' => 'Super Admin Role edited',
            ],
            'status_code' => 200,
        ],
    ],
];
