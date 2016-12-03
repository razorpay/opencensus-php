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
                'name'                  => 'testadmin',
                'email'                 => 'xyz@rzp.com',
                'username'              => 'harshil',
                'password'              => 'helloworld',
                'password_confirmation' => 'helloworld',
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
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testShortPassword' => [
        'request' => [
            'url' => '/orgs/%s/admins',
            'method' => 'post',
            'content' => [
                'name'                  => 'testadmin',
                'email'                 => 'xyz@rzp.com',
                'username'              => 'harshil',
                'password'              => 'x',
                'password_confirmation' => 'helloworld',
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The password must be between 6 and 50 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testLongPassword' => [
        'request' => [
            'url' => '/orgs/%s/admins',
            'method' => 'post',
            'content' => [
                'name'                  => 'testadmin',
                'email'                 => 'xyz@rzp.com',
                'username'              => 'harshil',
                'password'              => '1a2s3d4f5g6h7j8k9l1q',
                'password_confirmation' => '1a2s3d4f5g6h7j8k9l1q',
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Password should be maximum 16 characters',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
];
