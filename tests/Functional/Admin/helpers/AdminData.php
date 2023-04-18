<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreateAdmin' => [
        'request' => [
            'url' => '/admins',
            'method' => 'post',
            'content' => [
                'name'                  => 'test admin',
                'email'                 => 'xyz@rzp.com',
                'username'              => 'harshil',
                'password'              => 'Random!12#',
                'password_confirmation' => 'Random!12#',
                'remember_token'        => 'yes',
                'employee_code'         => 'rzp_1',
                'branch_code'           => 'krmgla',
                'supervisor_code'       => 'shk',
                'location_code'         => '560030',
                'department_code'       => 'tech',
                'roles'                 => ['role_RzpMngerRoleId'],
            ],
        ],
        'response' => [
            'content' => [
                'name'               => 'test admin',
                'email'              => 'xyz@rzp.com',
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

    'testCreateAdminESAfterDenialOfApproval' => [
        'request' => [
            'url' => '/admins',
            'method' => 'post',
            'content' => [
                'name'                  => 'test admin',
                'email'                 => 'xyz@razorpay.com',
                'username'              => 'harshil',
                'password'              => 'Random!12#',
                'password_confirmation' => 'Random!12#',
                'remember_token'        => 'yes',
                'employee_code'         => 'rzp_1',
                'branch_code'           => 'krmgla',
                'supervisor_code'       => 'shk',
                'location_code'         => '560030',
                'department_code'       => 'tech',
                'roles'                 => ['role_RzpMngerRoleId'],
            ],
        ],
        'response' => [
            'content' =>
                [],
            'status_code' => 200,
        ]
    ],

    'testCreateAdminESPasswordEncrypted' => [
        'request' => [
            'url' => '/admins',
            'method' => 'post',
            'content' => [
                'name'                  => 'test admin',
                'email'                 => 'xyz@razorpay.com',
                'username'              => 'harshil',
                'password'              => 'Random!12#',
                'password_confirmation' => 'Random!12#',
                'remember_token'        => 'yes',
                'employee_code'         => 'rzp_1',
                'branch_code'           => 'krmgla',
                'supervisor_code'       => 'shk',
                'location_code'         => '560030',
                'department_code'       => 'tech',
                'roles'                 => ['role_RzpMngerRoleId'],
            ],
        ],
        'response' => [
            'content' =>
                [
                    'entity_name' => "admin",
                    'maker' => "test admin",
                    'maker_id' => "RzrpySprAdmnId",
                    'maker_type' => "admin",
                    'type' => "maker",
                    'url' => "https://api.razorpay.com/v1/admins",

                    'method' => "POST",
                    'payload' => [

                        'name' => "test admin",
                        'email' => "xyz@razorpay.com",
                        'username' => "harshil",
                        'remember_token' => "yes",
                        'employee_code' => "rzp_1",
                        'branch_code' => "krmgla",
                        'supervisor_code' => "shk",
                        'location_code' => "560030",
                        'department_code' => "tech",
                    ],
                    'state' => "open",
                    'controller' => "RZP\Http\Controllers\OrganizationController@createAdmin",
                    'route' => "admin_create",
                    'permission' => "create_admin",
                    'diff' =>
                        [
                            'new' => [
                                'name' => "test admin",
                                'email' => "xyz@razorpay.com",
                                'username' => "harshil",
                                'password' => "**********",
                                'password_confirmation' => "**********",
                                'remember_token' => "yes",
                                'employee_code' => "rzp_1",
                                'branch_code' => "krmgla",
                                'supervisor_code' => "shk",
                                'location_code' => "560030",
                                'department_code' => "tech",
                                'org_id' => "100000razorpay",
                            ]
                        ],
                ],
        ],
        'status_code' => 200,
    ],

    'testCreateAdminESAfterAcceptanceOfApproval' => [
        'request' => [
            'url' => '/admins',
            'method' => 'post',
            'content' => [
                'name'                  => 'test admin',
                'email'                 => 'xyz@razorpay.com',
                'username'              => 'harshil',
                'password'              => 'Random!12#',
                'password_confirmation' => 'Random!12#',
                'remember_token'        => 'yes',
                'employee_code'         => 'rzp_1',
                'branch_code'           => 'krmgla',
                'supervisor_code'       => 'shk',
                'location_code'         => '560030',
                'department_code'       => 'tech',
                'roles'                 => ['role_RzpMngerRoleId'],
            ],
        ],
        'response' => [
            'content' =>
                [
                ],
        ],
        'status_code' => 200,
],

    'testCreateAdminWithWrongEmailDomain' => [
        'request' => [
            'url' => '/admins',
            'method' => 'post',
            'content' => [
                'name'               => 'test admin',
                'email'              => 'xyz@razorpay.com',
                'username'           => 'harshil',
                'password'           => 'random!12#',
                'remember_token'     => 'yes',
                'password'              => 'random!12#',
                'password_confirmation' => 'random!12#',
                'employee_code'      => 'rzp_1',
                'branch_code'        => 'krmgla',
                'supervisor_code'    => 'shk',
                'location_code'      => '560030',
                'department_code'    => 'tech',
                'roles'                 => ['role_RzpMngerRoleId'],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ADMIN_EMAIL_HOSTNAME,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ADMIN_EMAIL_HOSTNAME,
        ],
    ],

    'testCreateAdminWithExistingEmailSameOrg' => [
        'request' => [
            'url' => '/admins',
            'method' => 'post',
            'content' => [
                'name'               => 'test admin',
                'email'              => 'xyz@rzp.com',
                'username'           => 'harshil',
                'password'           => 'random!12#',
                'remember_token'     => 'yes',
                'password'              => 'random!12#',
                'password_confirmation' => 'random!12#',
                'employee_code'      => 'rzp_1',
                'branch_code'        => 'krmgla',
                'supervisor_code'    => 'shk',
                'location_code'      => '560030',
                'department_code'    => 'tech',
                'roles'                 => ['role_RzpMngerRoleId'],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateAdminWithExistingEmailOfDeletedAdmin' => [
        'request' => [
            'url' => '/admins',
            'method' => 'post',
            'content' => [
                'name'                  => 'test admin',
                'username'              => 'harshil',
                'password'              => 'Random!12#',
                'password_confirmation' => 'Random!12#',
                'remember_token'        => 'yes',
                'employee_code'         => 'rzp_1',
                'branch_code'           => 'krmgla',
                'supervisor_code'       => 'shk',
                'location_code'         => '560030',
                'department_code'       => 'tech',
                'roles'                 => ['role_RzpMngerRoleId'],
            ],
        ],
        'response' => [
            'content' => [
                'name'               => 'test admin',
                'email'              => 'xyz@rzp.com',
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

    'testCreateAdminWithExistingEmailDifferentOrg' => [
        'request' => [
            'url' => '/admins',
            'method' => 'post',
            'content' => [
                'name'               => 'test admin',
                'email'              => 'xyz@rzp.com',
                'username'           => 'harshil',
                'password'              => 'Random!12#',
                'password_confirmation' => 'Random!12#',
                'remember_token'     => 'yes',
                'employee_code'      => 'rzp_1',
                'branch_code'        => 'krmgla',
                'supervisor_code'    => 'shk',
                'location_code'      => '560030',
                'department_code'    => 'tech',
                'roles'                 => ['role_RzpMngerRoleId'],
            ],
        ],
        'response'  => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],

    'testGetAdmin' => [
        'request' => [
            'url' => '/admin/%s/fetch',
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
            'url' => '/admin/%s',
            'method' => 'put',
            'content' => [
                'name' => 'test',
                'password' => 'M123!#asd',
                'password_confirmation' => 'M123!#asd'
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'test',
            ],
            'status_code' => 200,
        ],
    ],

    'testAdminVerify2faFlagOtp' => [
        'request'  => [
            'url'     => '/admins/2fa/verify',
            'method'  => 'post',
            'content' => [
                'username' => 'testadmin@rzp.com',
                'password' => 'Heimdall!234',
                'otp' => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'testadmin@rzp.com',
            ],
            'status_code' => 200,
        ],
    ],

    'testAdminVerify2faFlagOtpFailure' => [
        'request'  => [
            'url'     => '/admins/2fa/verify',
            'method'  => 'post',
            'content' => [
                'username' => 'testadmin@rzp.com',
                'password' => 'Heimdall!234',
                'otp' => '0007',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_AUTHENTICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED,
        ],
    ],

    'testAdmin2faLogin' => [
        'request'  => [
            'url'     => '/admin/authenticate',
            'method'  => 'post',
            'content' => [
                'username' => 'testadmin@rzp.com',
                'password' => 'Heimdall!234',
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'testadmin@rzp.com',
            ],
            'status_code' => 200,
        ],
    ],

    'testAdmin2faLoginFailure' => [
        'request'  => [
            'url'     => '/admin/authenticate',
            'method'  => 'post',
            'content' => [
                'username' => 'testadmin@rzp.com',
                'password' => 'Heimdall!234',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_AUTHENTICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED,
        ],
    ],

    'testAdminResend2faFlagOtp' => [
        'request'  => [
            'url'     => '/admins/2fa/otp_resend',
            'method'  => 'post',
            'content' => [
                'username' => 'testadmin@rzp.com',
                'password' => 'Heimdall!234',
                "otp" => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'otp_send' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testAdminEdit2faFlagOrg' => [
        'request'  => [
            'url'     => '/admins/2fa',
            'method'  => 'post',
            'content' => [
                "second_factor_auth" => true,
            ],
        ],
        'response' => [
            'content' => [
                'admin_second_factor_auth' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testAdminUnlockAccount' => [
        'request'  => [
            'url'     => '/admins/account/%s',
            'method'  => 'put',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'locked' => false,
            ],
            'status_code' => 200,
        ],
    ],

    'testDeleteAllRolesAdmin' => [
        'request' => [
            'url' => '/admin/%s',
            'method' => 'put',
            'content' => [
                'name' => 'test',
                'roles' => [],
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testDeleteAllGroupsAdmin' => [
        'request' => [
            'url' => '/admin/%s',
            'method' => 'put',
            'content' => [
                'name' => 'test',
                'groups' => [],
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testDeleteAdmin' => [
        'request' => [
            'url' => '/admin/%s',
            'method' => 'delete',
        ],
        'response' => [
            'content' => [
                'deleted' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testDeleteAdminFailed' => [
        'request' => [
            'url' => '/admin/%s',
            'method' => 'delete',
        ],
        'response' => [
            'content' => [
                'deleted' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testGetMultipleAdmin' => [
        'request' => [
            'url' => '/admins',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'count' => 4,
            ],
            'status_code' => 200,
        ],
    ],

    'testGetCurrentAdmin' => [
        'request' => [
            'url' => '/current_admin',
            'method' => 'post',
            'content' => [
                'token' => 'secondTokenAdminToken1234',
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testLockUnusedAccounts' => [
        'request' => [
            'url' => '/admins/lock_accounts',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 4,
            ],
        ],
    ],

    'testLockedAdminAccess' => [
        'request' => [
            'url' => '/admin/%s/fetch',
            'method' => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_ACCOUNT_LOCKED
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED
        ]
    ],

    'testLoginUserDoesNotExist' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username' => 'test admin not exist',
                'password' => 'test password',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_AUTHENTICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED,
        ],
    ],

    'testDisabledAdminAccess' => [
        'request' => [
            'method' => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_ACCOUNT_DISABLED
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_ACCOUNT_DISABLED
        ]
    ],

    'testLoginOauth' => [
        'request' => [
            'url' => '/admin/oauth_login',
            'method' => 'post',
            'content' => [
                'email' => 'test@email.com',
                'oauth_access_token' => 'test oauth token',
                'oauth_provider_id'  => 'test oauth provider id',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLoginOauthWithIncorrectAccessToken' => [
        'request' => [
            'url' => '/admin/oauth_login',
            'method' => 'post',
            'content' => [
                'email' => 'test@email.com',
                'oauth_access_token' => 'test oauth token',
                'oauth_provider_id'  => 'test oauth provider id',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_TOKEN_INVALID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_TOKEN_INVALID,
        ],
    ],

    'testSelfEditAdminFailed' => [
        'request' => [
            'url' => '/admin/%s',
            'method' => 'put',
            'content' => [
                'name' => 'test asd',
                'password' => 'M123!#asd',
                'password_confirmation' => 'M123!#asd'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ADMIN_SELF_EDIT_PROHIBITED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ADMIN_SELF_EDIT_PROHIBITED,
        ],
    ],

    'testForgotPasswordSuccess' => [
        'request' => [
            'url' => '/admin/forgot_password',
            'method' => 'post',
            'content' => [
                'email' => 'abc@razorpay.com',
                'reset_password_url' => 'hello.com'
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testAdminUnlockOnResetPasswordSuccess' => [
        'request' => [
            'url' => '/admin/reset_password',
            'method' => 'post',
            'content' => [
                'email'                 => 'abc@razorpay.com',
                'password'              => 'Heimdall!4#2',
                'password_confirmation' => 'Heimdall!4#2',
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testForgotPasswordInvalidUser' => [
        'request' => [
            'url' => '/admin/forgot_password',
            'method' => 'post',
            'content' => [
                'email' => 'xyz@razorpay.com',
                'reset_password_url' => 'hello.com'
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testForgotPasswordResetUrlBlank' => [
        'request' => [
            'url' => '/admin/forgot_password',
            'method' => 'post',
            'content' => [
                'email' => 'xyz@razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPasswordResetSuccess' => [
        'request' => [
            'url' => '/admin/reset_password',
            'method' => 'post',
            'content' => [
                'email'                 => 'abc@razorpay.com',
                'password'              => 'Heimdall!4#2',
                'password_confirmation' => 'Heimdall!4#2',
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testAdminUnlockFailOnPasswordResetFail' => [
        'request' => [
            'url' => '/admin/reset_password',
            'method' => 'post',
            'content' => [
                'email'                 => 'abc@razorpay.com',
                'password'              => 'Heimdall!4#2',
                'password_confirmation' => 'Heimdall!4#2',
                'token'                 => 'dummytoken'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_INVALID_PASSWORD_RESET_TOKEN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_INVALID_PASSWORD_RESET_TOKEN,
        ],
    ],

    'testPasswordResetTokenMismatch' => [
        'request' => [
            'url' => '/admin/reset_password',
            'method' => 'post',
            'content' => [
                'email'                 => 'abc@razorpay.com',
                'password'              => 'Heimdall!4#2',
                'password_confirmation' => 'Heimdall!4#2',
                'token'                 => 'dummytoken'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_INVALID_PASSWORD_RESET_TOKEN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_INVALID_PASSWORD_RESET_TOKEN,
        ],
    ],

    'testPasswordResetPasswordMismatch' => [
        'request' => [
            'url' => '/admin/reset_password',
            'method' => 'post',
            'content' => [
                'email'                 => 'abc@razorpay.com',
                'password'              => 'Heimdall!4#2',
                'password_confirmation' => 'Heimdall!4#28',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPasswordResetInvalidPassword' => [
        'request' => [
            'url' => '/admin/reset_password',
            'method' => 'post',
            'content' => [
                'email'                 => 'abc@razorpay.com',
                'password'              => 'p',
                'password_confirmation' => 'p',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPasswordResetMaxRetain' => [
        'request' => [
            'url' => '/admin/reset_password',
            'method' => 'post',
            'content' => [
                'email'                 => 'abc@razorpay.com',
                'password'              => 'M!2#uWdx',
                'password_confirmation' => 'M!2#uWdx',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPasswordResetInvalidAuthType' => [
        'request' => [
            'url' => '/admin/reset_password',
            'method' => 'post',
            'content' => [
                'email'                 => 'abc@razorpay.com',
                'password'              => 'M!2#uWdx',
                'password_confirmation' => 'M!2#uWdx',
                'token'                 => 'dummytoken',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAdminLogout' => [
        'request' => [
            'url'     => '/admin/logout',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateAdminWithoutPassword' => [
        'request' => [
            'url' => '/admins',
            'method' => 'post',
            'content' => [
                'name'                  => 'test admin',
                'email'                 => 'xyz@rzp.com',
                'username'              => 'harshil',
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
                    'description' => 'The password field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateAdminWithOAuth' => [
        'request' => [
            'url' => '/admins',
            'method' => 'post',
            'content' => [
                'name'                  => 'test admin',
                'email'                 => 'xyz@abc.com',
                'username'              => 'harshil',
                'oauth_access_token'    => 'google',
                'oauth_provider_id'     => '123',
                'remember_token'        => 'yes',
                'employee_code'         => 'rzp_1',
                'branch_code'           => 'krmgla',
                'supervisor_code'       => 'shk',
                'location_code'         => '560030',
                'department_code'       => 'tech',
                'roles'                 => ['role_RzpMngerRoleId'],
            ],
        ],
        'response'  => [
            'content' => [
                'name'               => 'test admin',
                'email'              => 'xyz@abc.com',
                'username'           => 'harshil',
                'oauth_provider_id'  => '123',
                'employee_code'      => 'rzp_1',
                'branch_code'        => 'krmgla',
                'supervisor_code'    => 'shk',
                'location_code'      => '560030',
                'department_code'    => 'tech',
            ],
            'status_code' => 200,
        ],
    ],

    'testConfigKeysSet' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:terminal_selection_log_verbose'        => '1',
                'config:pricing_rule_selection_log_verbose'    => '1',
            ],
        ],
        'response' => [
            [
                'key'       => 'config:terminal_selection_log_verbose',
                'new_value' => '1',
            ],
        ],
    ],

    'testConfigKeysSetWithAdminWithOnlyUpdateConfigKeyPermission' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:rx_account_number_series_prefix' => [
                        '100000Razorpay' => '3434'
                    ],
                'config:rx_shared_account_allowed_channels' => [
                    'yesbank',
                    'icici',
                    'kotak'
                ],
            ],
        ],
        'response' => [
            [
                'key'       => 'config:rx_account_number_series_prefix',
                'new_value' => [
                    '100000Razorpay' => '3434'
                ],
            ],
            [
                'key'       => 'config:rx_shared_account_allowed_channels',
                'new_value' => [
                    'yesbank',
                    'icici',
                    'kotak'
                ],
            ]
        ],
    ],

    'testConfigKeysSetWithSpecificKeyPermissions' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:rx_account_number_series_prefix' => [
                    '100000Razorpay' => '3434'
                ],
                'config:rx_shared_account_allowed_channels' => [
                    'yesbank',
                    'icici',
                    'kotak'
                ],
            ],
        ],
        'response' => [
            [
                'key'       => 'config:rx_account_number_series_prefix',
                'new_value' => [
                    '100000Razorpay' => '3434'
                ],
            ],
            [
                'key'       => 'config:rx_shared_account_allowed_channels',
                'new_value' => [
                    'yesbank',
                    'icici',
                    'kotak'
                ],
            ]
        ],
    ],

    'testConfigKeysSetWithCompletelyWrongPermission' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:rx_account_number_series_prefix' => [
                    '100000Razorpay' => '3434'
                ],
                'config:rx_shared_account_allowed_channels' => [
                    'yesbank',
                    'icici',
                    'kotak'
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testConfigKeysSetWithMissingPermission' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:rx_account_number_series_prefix' => [
                    '100000Razorpay' => '3434'
                ],
                'config:rx_shared_account_allowed_channels' => [
                    'yesbank',
                    'icici',
                    'kotak'
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testConfigKeysSetConfigWithNoPermission' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:rx_account_number_series_prefix' => [
                    '100000Razorpay' => '3434'
                ],
                'config:terminal_selection_log_verbose' => 1,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testConfigKeysFetch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/config/keys',
        ],
        'response' => [
            'config:terminal_selection_log_verbose'        => '1',
            'config:pricing_rule_selection_log_verbose'    => '1',
        ],
    ],

    'testAdminAllEntitiesApi' => [
        'request' => [
            'url'       => '/admin/entities/all',
            'method'    => 'get'
        ],
        'response' => [
            'content' => [
                'entities' => []
            ]
        ]
    ],

    'testAdminAllEntitiesApiNoPaymentTenantRolesNonRzpOrg' => [
        'request' => [
            'url'       => '/admin/entities/all',
            'method'    => 'get'
        ],
        'response' => [
            'content' => [
                'entities' => []
            ]
        ]
    ],

    'testAdminAllEntitiesApiNoPaymentTenantRoles' => [
        'request' => [
            'url'       => '/admin/entities/all',
            'method'    => 'get'
        ],
        'response' => [
            'content' => [
                'entities' => []
            ]
        ]
    ],

    'testAdminAllEntitiesApiWithPaymentsTenantRole' => [
        'request' => [
            'url'       => '/admin/entities/all',
            'method'    => 'get'
        ],
        'response' => [
            'content' => [
                'entities' => []
            ]
        ]
    ],

    'testAdminAllEntitiesApiWithPaymentsExternalTenantRole' => [
        'request' => [
            'url'       => '/admin/entities/all',
            'method'    => 'get'
        ],
        'response' => [
            'content' => [
                'entities' => []
            ]
        ]
    ],

    //
    // Additional request content and assertions are done in test method
    // for different cases.
    //
    'testFetchSoftDeletedEntityForAdmin' => [
        'request' => [
            'url'      => '/admin/org',
            'method'   => 'get',
            'content'  => [
                'auth_type' => 'google_auth',
            ],
        ],
        'response' => [
            'content'  => [],
        ],
    ],

    //
    // Additional request content and assertions are done in test method
    // for different cases.
    //
    'testFindSoftDeletedEntityForAdmin' => [
        'request' => [
            'url'     => '/admin/org/org_10000000000001',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content'  => [],
        ],
    ],

    'testUpdateGeoIps' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/geoip/update',
            'content' => [
                'eureka_key_index' => 1
            ],
        ],
        'response' => [
            'content' => [
                'total'   => 3,
                'success' => 1
            ]
        ],
    ],

    'testDbMetaDataQuery' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/db_meta_query',
            'content' => [
                'query' => 'show indexes from merchants;',
            ],
        ],
        'response'  => [
            'content'     => [
                [
                    'Key_name' => 'PRIMARY',
                ],
            ],
        ],
    ],

    'testDbMetaDataQueryWithInvalidQuery' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/db_meta_query',
            'content' => [
                'query' => 'truncate table merchants;',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => ErrorCode::BAD_REQUEST_INVALID_QUERY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testTaxPaymentAdminRouteHitsServiceMethod'                           => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/tax-payments/admin',
            'content' => [
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testTaxPaymentAdminRouteFailsWithoutPermission'                           => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/tax-payments/admin',
            'content' => [
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testOAuthApplicationUpdateServiceMethod'                             => [
         'request'  => [
            'url'     => '/admin-oauth/applications/8ckeirnw84ifke',
            'method'  => 'POST',
             'server'  => [
                 'HTTP_X-Dashboard-User-Id' => '20000000000000',
             ],
            'content' => [
                "type" => "tally",
                "merchant_id" => "10000000000000",
                "client_details" => [
                    [
                        "id"    => "HQunkUT2hOwf18",
                        "type"  => "tally"
                    ],
                    [
                        "id"=> "HQunkqAstmVqhk",
                        "type"=> "tally"
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testPayoutLinkAdminRouteHitsServiceMethod'                           => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/payout-links/admin',
            'content' => [
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testPayoutLinkAdminRouteFailsWithoutPermission'                           => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/payout-links/admin',
            'content' => [
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
      ],

    'testPayoutLinkPullStatusRouteReachesServiceWithPermission'                           => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/payout-links/xyz/pullPayoutStatus',
            'content' => [
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'xyz is not a valid id',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPayoutLinkPullStatusRouteFailsWithoutPermission'                           => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/payout-links/xyz/pullPayoutStatus',
            'content' => [
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testAdminP2pEntitiesApi' => [
        'request' => [
            'url'       => '/admin/entities/all',
            'method'    => 'get'
        ],
        'response' => [
            'content' => [
                'entities' => []
            ]
        ]
    ],

    'testExternalAdminGetEntities' => [
        'request'  => [
            'url'    => '/external_admin/entities/all',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entities' => [

                ],
            ],
        ],
    ],

    'testExternalAdminFetchAllowedEntityById' => [
        'request'  => [
            'url'    => '/external_admin/',
            'method' => 'get',
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testExternalAdminFetchBlockedEntityByIdShouldFail' => [
        'request'   => [
            'url'    => '/external_admin/terminal/',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Access Denied',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testExternalAdminFetchEntityMultipleDisallowedParamsShouldFail' => [
        'request'   => [
            'url'    => '/external_admin/payment?last4=1234',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'error' => [

                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testAdminEntitySyncByIDSuccess' => [
        'request'  => [
            'url'    => '/admin/entity_sync/merchant/10000000000000',
            'method' => 'post',
            'content' => [
                'from_mode' => 'live',
	            'to_mode' => 'test',
	            'fields_to_sync' => ['account_code'],
            ]
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testAdminEntitySyncByIDFailureByWrongEntity' => [
        'request'  => [
            'url'    => '/admin/entity_sync/merchanty/10000000000000',
            'method' => 'post',
            'content' => [
                'from_mode' => 'live',
	            'to_mode' => 'test',
	            'fields_to_sync' => ['account_code'],
            ]
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAdminEntitySyncByIDFailureByWrongMode' => [
        'request'  => [
            'url'    => '/admin/entity_sync/merchant/10000000000000',
            'method' => 'post',
            'content' => [
                'from_mode' => 'livee',
                'to_mode' => 'test',
                'fields_to_sync' => ['account_code'],
            ]
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAdminEntitySyncByIDFailureByNonSyncEntity' => [
        'request'  => [
            'url'    => '/admin/entity_sync/card/10000000000000',
            'method' => 'post',
            'content' => [
                'from_mode' => 'live',
                'to_mode' => 'test',
                'fields_to_sync' => ['account_code'],
            ]
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ONLY_SYNCED_ENTITIES_CAN_BE_SYNCED,
        ],
    ],

    'testExternalAdminFetchEntityMultipleBlockedEntityShouldFail' => [
        'request'   => [
            'url'    => '/external_admin/terminal/',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Access Denied',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testExternalAdminFetchEntityMultiple' => [
        'request'  => [
            'url'    => '/external_admin/payment?status=created',
            'method' => 'get',
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testExternalAdminFetchEntityMultipleLimitedCount' => [
        'request'  => [
            'url'    => '/external_admin/payment?count=20',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'count' => 5,
            ],
        ],
    ],

    'testEnableInstantRefunds'  =>[
      'request'     => [
          'url'     => '/admin/enable_instant_refunds/20000000000000',
          'method'  => 'POST',
          'server'  => [
              'HTTP_X-Dashboard-User-Id'    => '20000000000000',
              'HTTP_X-Dashboard'            => true,
              'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
          ],
          'content' => [
              'features' => [
                  'merchant_enable_refunds'
              ],
              'should_sync' =>  true,
          ]
      ],
      'response'    => [
          'content' =>[
          ],
          'status_code' => 200,
          'success'     => true,
      ],
    ],

    'testDisableInstantRefunds'  =>[
        'request'  => [
            'url'     => '/admin/disable_instant_refunds/20000000000000',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Dashboard'            => true,
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
            'content' => [
                'features' => [
                    'merchant_enable_refunds'
                ],
                'should_sync' =>  true,
            ]
        ],
        'response' => [
            'content' =>[
            ],
            'status_code' => 200,
            'success'     => true,
        ],
    ],

    'testToggleWhatsappNotificationOn'   =>  [
        'request'  => [
            'url'     => '/admin/toggle_whatsapp_notification/20000000000000',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Dashboard'            => true,
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
            'content' => [
                'source' => 'pg.settings.config',
                'enable' =>  true,
            ]
        ],
       'response' => [
           'content' => [
           ],
           'status_code' => 200,
           'success'     => true,
       ],
    ],

    'testToggleWhatsappNotificationOff'   =>  [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Dashboard'            => true,
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
            'url'     => '/admin/toggle_whatsapp_notification/20000000000000',
            'content' => [
                'source' => 'pg.settings.config',
                'enable' =>  false,
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
            'success'     => true,
        ],
    ],

    'testAdminFetchUserByEmail' => [
        'request' => [
            'method' => 'GET',
            'url' => '/admin/user?email=random@random.com'
        ],
        'response' => [
            'content' => [
                "entity"=> "collection",
                "count"=> 1,
                "admin"=> true,
                "items"=>
                    [
                        [
                            "email"=> "random@random.com",
                        ]
                    ]
            ]
        ]
    ],

    'testAdminFetchUserByMobile' => [
        'request' => [
            'method' => 'GET',
            'url' => '/admin/user?contact_mobile=9878909877'
        ],
        'response' => [
            'content' => [
                "entity"=> "collection",
                "count"=> 1,
                "admin"=> true,
                "items"=>
                    [
                        [
                            "contact_mobile"=> "9878909877",
                        ]
                    ]
            ]
        ]
    ],

    'testAdminFetchUserByMobileMultipleMatches' => [
        'request' => [
            'method' => 'GET',
            'url' => '/admin/user?contact_mobile=9878909876'
        ],
        'response' => [
            'content' => [
                "entity"=> "collection",
                "count"=> 2,
                "admin"=> true,
                "items"=>
                    [
                        [
                            "contact_mobile"=> "9878909876",
                        ],
                        [
                            "contact_mobile"=> "9878909876",
                        ]
                    ]
            ]
        ]
    ],

    'testAdminFetchUserByMobileNoMatches' => [
        'request' => [
            'method' => 'GET',
            'url' => '/admin/user?contact_mobile=9878909877'
        ],
        'response' => [
            'content' => [
                "entity"=> "collection",
                "count"=> 0,
                "admin"=> true,
                "items"=> []
            ]
        ]
    ],

    'testAdminFetchUserByMobileInvalidMobileNumberFormat' => [
        'request' => [
            'method' => 'GET',
            'url' => '/admin/user?contact_mobile=666557'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT,
        ],
    ],

    'testAdminFetchPaymentFraudByPaymentId' => [
        'request' => [
            'method' => 'GET',
            'url' => '/admin/payment_fraud?payment_id=100000Razorpay'
        ],
        'response' => [
            'content' => [
                "entity"=> "collection",
                "count"=> 1,
                "admin"=> true,
                "items"=> []
            ]
        ]
    ],

    'testAdminFetchPaymentFraudByArn' => [
        'request' => [
            'method' => 'GET',
            'url' => '/admin/payment_fraud?arn=100000Razorpay0000'
        ],
        'response' => [
            'content' => [
                "entity"=> "collection",
                "count"=> 1,
                "admin"=> true,
                "items"=> []
            ]
        ]
    ],

    'testRiskThresholdFieldNotVisibleOnMerchantEntity' => [
        'request' => [
            'url' => '/admin/merchant/10000000000000',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                "org_id" => "100000razorpay",
                'entity' => 'merchant',
                'id' => '10000000000000',
                'email' => "test@razorpay.com",
            ],
            'status_code' => 200,
        ],
    ],

    'testBulkAssignRole' => [
        'request' => [
            'url' => '/admin/bulk_assign_role',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'total_count'      => 1,
                'valid_count'      => 1,
                'failed_count'     => 0,
                'failed_emails'    => [],
                'not_found_emails' => [],
            ],
        ],
    ],

    'testAdminFetchBankTransfersWithBankingRole' => [
        'request' => [
            'method' => 'GET',
            'url' => '/admin/bank_transfer'
        ],
        'response' => [
            'content' => [
                "entity"=> "collection",
                "admin"=> true,
            ]
        ]
    ],

    'testAdminFetchCardsWithBankingRole' => [
        'request' => [
            'method' => 'GET',
            'url' => '/admin/card'
        ],
        'response' => [
            'content' => [
                "entity"=> "collection",
                "admin"=> true,
            ]
        ]
    ],

    'testAdminFetchCardByIdWithBankingRole' => [
        'request' => [
            'method' => 'GET',
            'url' => '/admin/card'
        ],
        'response' => [
            'content' => [
                "entity"=> "card",
                "admin"=> true,
            ]
        ],
    ],

    'testAdminFetchCardByIdNotFound' => [
        'request' => [
            'method' => 'GET',
            'url' => '/admin/card'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testAdminFetchBankTransferByIdWithBankingRole' => [
        'request' => [
            'method' => 'GET',
            'url' => '/admin/bank_transfer'
        ],
        'response' => [
            'content' => [
                "entity"=> "bank_transfer",
                "admin"=> true,
            ]
        ],
    ],

    'testAdminFetchBankTransferByIdNotFound' => [
        'request' => [
            'method' => 'GET',
            'url' => '/admin/bank_transfer'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],
];
