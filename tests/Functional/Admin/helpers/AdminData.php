<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreateAdmin' => [
        'request' => [
            'url' => '/orgs/%s/admins',
            'method' => 'post',
            'content' => [
                'name'                  => 'test admin',
                'email'                 => 'xyz@rzp.com',
                'username'              => 'harshil',
                'password'              => 'random!12#',
                'password_confirmation' => 'random!12#',
                'remember_token'        => 'yes',
                'oauth_access_token'    => 'oauth123',
                'oauth_provider_id'     => 'google',
                'employee_code'         => 'rzp_1',
                'branch_code'           => 'krmgla',
                'supervisor_code'       => 'shk',
                'location_code'         => '560030',
                'department_code'       => 'tech',
            ],
        ],
        'response' => [
            'content' => [
                'name'               => 'test admin',
                'email'              => 'xyz@rzp.com',
                'username'           => 'harshil',
                'remember_token'     => 'yes',
                'employee_code'      => 'rzp_1',
                'branch_code'        => 'krmgla',
                'supervisor_code'    => 'shk',
                'location_code'      => '560030',
                'department_code'    => 'tech',
            ],
            'status_code' => 200,
        ]
    ],

    'testCreateAdminWithWrongEmailDomain' => [
        'request' => [
            'url' => '/orgs/%s/admins',
            'method' => 'post',
            'content' => [
                'name'               => 'test admin',
                'email'              => 'xyz@razorpay.com',
                'username'           => 'harshil',
                'password'           => 'random!12#',
                'remember_token'     => 'yes',
                'oauth_access_token' => 'oauth123',
                'oauth_provider_id'  => 'google',
                'employee_code'      => 'rzp_1',
                'branch_code'        => 'krmgla',
                'supervisor_code'    => 'shk',
                'location_code'      => '560030',
                'department_code'    => 'tech',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ADMIN_EMAIL_IS_NOT_VALID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ADMIN_EMAIL_IS_NOT_VALID
        ],
    ],

    'testGetAdmin' => [
        'request' => [
            'url' => '/orgs/%s/admins/%s',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'name' => 'test admin',
                'email' => 'testadmin@rzp.com',
                'username' => 'harshil',
            ],
            'status_code' => 200,
        ],
    ],

    'testEditAdmin' => [
        'request' => [
            'url' => '/orgs/%s/admins/%s',
            'method' => 'put',
            'content' => [
                'name' => 'test',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'test',
            ],
            'status_code' => 200,
        ],
    ],

    'testDeleteAllRolesAdmin' => [
        'request' => [
            'url' => '/orgs/%s/admins/%s',
            'method' => 'put',
            'content' => [
                'name' => 'test',
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testDeleteAllGroupsAdmin' => [
        'request' => [
            'url' => '/orgs/%s/admins/%s',
            'method' => 'put',
            'content' => [
                'name' => 'test',
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testDeleteAdmin' => [
        'request' => [
            'url' => '/orgs/%s/admins/%s',
            'method' => 'delete',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ]
];
