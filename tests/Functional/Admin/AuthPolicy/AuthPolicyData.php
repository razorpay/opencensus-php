<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAdminLogin' => [
        'request' => [
            'url' => '/orgs/org_RazorpayOrgnId/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username' => 'admin@rzp.io',
                'password' => 'test123456'
            ],
        ],
        'response' => [
            'content' => [
                'name'               => 'test admin',
                'email'              => 'admin@rzp.io',
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

    'testWeakPassword' => [
        'request' => [
            'url' => '/orgs/%s/admins',
            'method' => 'post',
            'content' => [
                'name'               => 'testadmin',
                'email'              => 'xyz@rzp.com',
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
