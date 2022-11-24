<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAdminLogin' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username' => 'superadmin@razorpay.com',
                'password' => 'test123456'
            ],
        ],
        'response' => [
            'content' => [
                'name'               => 'test admin',
                'email'              => 'superadmin@razorpay.com',
                'username'           => 'harshil',
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
            'url' => '/admins',
            'method' => 'post',
            'content' => [
                'name'                  => 'testadmin',
                'email'                 => 'xyz@rzp.com',
                'username'              => 'harshil',
                'password'              => 'Password@1',
                'password_confirmation' => 'Password@1',
                'remember_token'        => 'yes',
                'oauth_access_token'    => 'oauth123',
                'oauth_provider_id'     => 'google',
                'employee_code'         => 'rzp_1',
                'branch_code'           => 'krmgla',
                'supervisor_code'       => 'shk',
                'location_code'         => '560030',
                'department_code'       => 'tech',
                'roles'                 => ['role_RzpMngerRoleId'],
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
            'url' => '/admins',
            'method' => 'post',
            'content' => [
                'name'                  => 'testadmin',
                'email'                 => 'xyz@rzp.com',
                'username'              => 'harshil',
                'password'              => 'Rzp@x1',
                'password_confirmation' => 'Rzp@x1',
                'remember_token'        => 'yes',
                'oauth_access_token'    => 'oauth123',
                'oauth_provider_id'     => 'google',
                'employee_code'         => 'rzp_1',
                'branch_code'           => 'krmgla',
                'supervisor_code'       => 'shk',
                'location_code'         => '560030',
                'department_code'       => 'tech',
                'roles'                 => ['role_RzpMngerRoleId'],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Password should be atleast 8 characters long',
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
            'url' => '/admins',
            'method' => 'post',
            'content' => [
                'name'                  => 'testadmin',
                'email'                 => 'xyz@rzp.com',
                'username'              => 'harshil',
                'password'              => '1a2s3d4f5g6h7j8k9l1q1a2s3d4f5g6h7j8k9l1q1a2s3d4f5g6h7j8k9l1q1a2s3d4f5g6h7j8k9l1q',
                'password_confirmation' => '1a2s3d4f5g6h7j8k9l1q1a2s3d4f5g6h7j8k9l1q1a2s3d4f5g6h7j8k9l1q1a2s3d4f5g6h7j8k9l1q',
                'remember_token'        => 'yes',
                'oauth_access_token'    => 'oauth123',
                'oauth_provider_id'     => 'google',
                'employee_code'         => 'rzp_1',
                'branch_code'           => 'krmgla',
                'supervisor_code'       => 'shk',
                'location_code'         => '560030',
                'department_code'       => 'tech',
                'roles'                 => ['role_RzpMngerRoleId'],
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

    'testMaxFailedLoginAttempts' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username'  => 'randomemail@rzp.com',
                'password'  => 'test123456'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You have exceeded maxmium number of login attempts. Your account has been locked.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testAdminLoginWhenLocked' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username'  => 'randomemail@rzp.com',
                'password'  => 'test123456'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account has been locked',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testPasswordRetainPolicy' => [
        'request' => [
            'url' => '/admin/%s',
            'method' => 'put',
            'content' => [
                'password'              => '@#12$%^&dfgh',
                'password_confirmation' => '@#12$%^&dfgh',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Password cannot be same as last 10 passwords.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testPasswordRetainPolicyWithNewPassword' => [
        'request' => [
            'url' => '/admin/%s',
            'method' => 'put',
            'content' => [
                'password'              => '@#12$%^&Dfghq',
                'password_confirmation' => '@#12$%^&Dfghq',
            ],
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 200,
        ]
    ],

    'testPasswordChangedAtPolicy' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username' => 'randomemail2@rzp.com',
                'password' => 'test123456'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Account password has expired. Please contact administrator.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testAccessWithWrongOrg' => [
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
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED,
        ],
    ],
    'testSuperAdminLockOnMaxFailedAttempts' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username'  => 'randomemail@rzp.com',
                'password'  => 'test12345jk6'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED
        ],
    ],
];
