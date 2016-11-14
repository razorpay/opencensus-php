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
                'name'               => 'test admin',
                'email'              => 'xyz@abc.com',
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
        'response' => [
            'content' => [
                'name'               => 'test admin',
                'email'              => 'xyz@abc.com',
                'username'           => 'harshil',
                'remember_token'     => 'yes',
                'oauth_access_token' => 'oauth123',
                'oauth_provider_id'  => 'google',
                'employee_code'      => 'rzp_1',
                'branch_code'        => 'krmgla',
                'supervisor_code'    => 'shk',
                'location_code'      => '560030',
                'department_code'    => 'tech',
            ],
            'status_code' => 200,
        ]
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

    'testGetAdmin' => [
        'request' => [
            'url' => '/orgs/%s/admins/%s',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'name' => 'test admin',
                'email' => 'xyz@abc.com',
                'username' => 'harshil',
            ],
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
    ],

    'testWeakPassword' => [
        'request' => [
            'url' => '/orgs/%s/admins',
            'method' => 'post',
            'content' => [
                'name'               => 'testadmin',
                'email'              => 'xyz@abc.com',
                'username'           => 'harshil',
                'password'           => 'helloworld',
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
                    'description' => 'Password is too weak. Please choose a new password.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ]
];
