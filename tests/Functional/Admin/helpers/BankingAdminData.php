<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testInactiveIdamAdminLoginDueToDormancy' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username'  => 'randomadmin123@rzp.com',
                'password'  => 'test123456'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ADMIN_DISABLED_BY_DORMANCY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR
        ],
    ],

    'testInactiveIdamAdminLogin' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username'  => 'randomadmin123@rzp.com',
                'password'  => 'test123456'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ADMIN_DISABLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR
        ],
    ],

    'testActiveIdamAdminLoginDueToDormancy' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username'  => 'randomadmin123@rzp.com',
                'password'  => 'test123456'
            ],
        ],
        'response' => [
            'content' => [
                'email' => "randomadmin123@rzp.com",
                'name' => "Test User",
                'username' => "testadmin",
                'oauth_provider_id' => "google",
                'org_id' => "org_CLTnQqDj9Si8bx",
                'user_type' => null,
                'employee_code' => "rzp_1",
                'branch_code' => "krmgla",
                'department_code' => "tech",
                'supervisor_code' => "shk",
                'location_code' => "560030",
                'disabled' => false,
                'locked' => false,
                'deleted_at' => null,
                'allow_all_merchants' => false,
                'roles' => [],
            ],
            'status_code' => 200,
        ]
    ],

    'testActiveUserWithoutAdminMeta' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username'  => 'randomadmin123@rzp.com',
                'password'  => 'test123456'
            ],
        ],
        'response' => [
            'content' => [
                'email' => "randomadmin123@rzp.com",
                'name' => "Test User",
                'username' => "testadmin",
                'oauth_provider_id' => "google",
                'org_id' => "org_CLTnQqDj9Si8bx",
                'user_type' => null,
                'employee_code' => "rzp_1",
                'disabled' => false,
                'locked' => false,
                'deleted_at' => null,
                'allow_all_merchants' => false,
                'roles' => [],
            ],
            'status_code' => 200,
        ]
    ],

    'testOrgAdminsDisableForActiveUsers' => [
        'request' => [
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => 3,
                'failure' => 0,
            ],
            'status_code' => 200
        ]
    ],

    'testOrgAdminsDisableForInactiveUsers' => [
        'request' => [
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => 2,
                'failure' => 0,
            ],
            'status_code' => 200
        ]
    ],

    'testOrgAdminsDisableWhenAlreadyDisabled' => [
        'request' => [
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => 0,
                'failure' => 0,
            ],
            'status_code' => 200
        ]
    ],
];
