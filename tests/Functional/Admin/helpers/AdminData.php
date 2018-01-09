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
                'password'              => 'random!12#',
                'password_confirmation' => 'random!12#',
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

    'testCreateAdminWithExistingEmail' => [
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
                'password'              => 'random!12#',
                'password_confirmation' => 'random!12#',
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

    'testEditAdminOnAppAuth' => [
        'request' => [
            'url' => '/admin-app-auth/%s',
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

    'testGetMerchantIds' => [
        'request' => [
            'url' => '/orgs/%s/admins/%s/merchant_ids',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
            ],
        ],
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

    'testFailedLoginOauth' => [
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
    'testGetAdminByEmailOnAppAuth' => [
        'request' => [
            'url'     => '/admins/get-multiple-app-auth?email=testadmin@rzp.com',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content'   => [
                'name'  => 'test admin app auth'
            ],
            'status_code' => 200,
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_INVALID_ADMIN_EMAIL,
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
                'terminal_selection_log_verbose'        => '1',
                'pricing_rule_selection_log_verbose'    => '1',
            ],
        ],
        'response' => [
            [
                'key'       => 'terminal_selection_log_verbose',
                'new_value' => '1',
            ],
        ],
    ],

    'testConfigKeysFetch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/config/keys',
        ],
        'response' => [
            'terminal_selection_log_verbose'        => '1',
            'pricing_rule_selection_log_verbose'    => '1',
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
    ]
];
