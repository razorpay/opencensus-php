<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateOrg' => [
        'request'  => [
            'url'     => '/orgs',
            'method'  => 'post',
            'content' => [
                'hostname'      => 'hdfc.com,fbapi.com',
                'email_domains' => ['hdfc.com', 'fbapi.com'],
                'allow_sign_up' => 0,
                'email'         => 'test@hdfc.com',
                'type'          => 'restricted',
                'display_name'  => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type'     => 'password',
                'custom_code'   => 'test custom code',
                'admin'         => [
                    'name'                  => 'superadmin',
                    'branch_code'           => 'a',
                    'employee_code'         => 'a',
                    'location_code'         => 'a',
                    'department_code'       => 'a',
                    'supervisor_code'       => 'a',
                    'username'              => 'xyz93',
                    'password'              => 'xYZ123!@#',
                    'password_confirmation' => 'xYZ123!@#',
                ],
                'merchant_styles' => [
                    'color_code1' => '123',
                    'color_code2' => '345'
                ]
            ],
        ],
        'response' => [
            'content'     => [
                'email_domains' => [
                    'hdfc.com',
                    'fbapi.com'
                ],
                'allow_sign_up' => false,
                'email'         => 'test@hdfc.com',
                'display_name'  => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type'     => 'password',
                'custom_code'   => 'test custom code'
            ],
            'status_code' => 200,
        ],
    ],

    'testEditOrg' => [
        'request'  => [
            'url'     => '/orgs',
            'method'  => 'put',
            'content' => [
                'email_domains' => ['fbapi.com'],
                'hostname'      => 'test1.com, test2.com',
                'email'         => 'test@hdfc.com',
                'allow_sign_up' => true,
                'display_name'  => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type'     => 'password',
                'custom_code'   => 'test custom code',
            ],
        ],
        'response' => [
            'content'     => [
                'email_domains' => [
                    'fbapi.com'
                ],
                'allow_sign_up' => true,
                'email'         => 'test@hdfc.com',
                'display_name'  => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type'     => 'password',
                'custom_code'   => 'test custom code',
            ],
            'status_code' => 200,
        ],
    ],

    'testEditOrgMerchant2FaAuth' => [
        'request'  => [
            'url'     => '/orgs',
            'method'  => 'put',
            'content' => [
                'email_domains' => ['fbapi.com'],
                'hostname'      => 'test1.com, test2.com',
                'email'         => 'test@hdfc.com',
                'allow_sign_up' => true,
                'display_name'  => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type'     => 'password',
                'custom_code'   => 'test custom code',
                'merchant_second_factor_auth' => true,
                'merchant_max_wrong_2fa_attempts' => 5
            ],
        ],
        'response' => [
            'content'     => [
                'email_domains' => [
                    'fbapi.com'
                ],
                'allow_sign_up' => true,
                'email'         => 'test@hdfc.com',
                'display_name'  => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type'     => 'password',
                'custom_code'   => 'test custom code',
                'merchant_second_factor_auth' => 1,
                'merchant_max_wrong_2fa_attempts' => 5
            ],
            'status_code' => 200,
        ],
    ],

    'testEditOrgAdmin2FaAuth' => [
        'request'  => [
            'url'     => '/orgs',
            'method'  => 'put',
            'content' => [
                'email_domains' => ['fbapi.com'],
                'hostname'      => 'test1.com, test2.com',
                'email'         => 'test@hdfc.com',
                'allow_sign_up' => true,
                'display_name'  => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type'     => 'password',
                'custom_code'   => 'test custom code',
                'admin_second_factor_auth' => true,
                'admin_max_wrong_2fa_attempts' => 7
            ],
        ],
        'response' => [
            'content'     => [
                'email_domains' => [
                    'fbapi.com'
                ],
                'allow_sign_up' => true,
                'email'         => 'test@hdfc.com',
                'display_name'  => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type'     => 'password',
                'custom_code'   => 'test custom code',
                'admin_second_factor_auth' => 1,
                'admin_max_wrong_2fa_attempts' => 7
            ],
            'status_code' => 200,
        ],
    ],

    'testEditOrg2FaAuthMode' => [
        'request'  => [
            'url'     => '/orgs',
            'method'  => 'put',
            'content' => [
                'email_domains' => ['fbapi.com'],
                'hostname'      => 'test1.com, test2.com',
                'email'         => 'test@hdfc.com',
                'allow_sign_up' => true,
                'display_name'  => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type'     => 'password',
                'custom_code'   => 'test custom code',
                'second_factor_auth_mode' => 'sms_and_email'
            ],
        ],
        'response' => [
            'content'     => [
                'email_domains' => [
                    'fbapi.com'
                ],
                'allow_sign_up' => true,
                'email'         => 'test@hdfc.com',
                'display_name'  => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type'     => 'password',
                'custom_code'   => 'test custom code',
                'second_factor_auth_mode' => 'sms_and_email'
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateWithoutPassword' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'post',
            'content' => [
                'hostname'  => 'hdfc.com,fbapi.com',
                'email_domains' => ['hdfc.com', 'fbapi.com'],
                'allow_sign_up' => 0,
                'email' => 'test@hdfc.com',
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
                'custom_code' => 'test custom code',
                'admin' => [
                    'name' => 'superadmin',
                    'branch_code' => 'a',
                    'employee_code' => 'a',
                    'location_code' => 'a',
                    'department_code' => 'a',
                    'supervisor_code' => 'a',
                    'username' => 'xyz93',
                ],
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
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testEditOrgWithPermissions' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'put',
            'content' => [
                'email_domains' => ['fbapi.com'],
                'hostname' => 'test1.com, test2.com',
                'email' => 'test@hdfc.com',
                'allow_sign_up' => true,
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
                'custom_code' => 'test custom code',
            ],
        ],
        'response' => [
            'content' => [
                'email_domains' => [
                    'fbapi.com'
                ],
                'allow_sign_up' => true,
                'email'         => 'test@hdfc.com',
                'display_name'  => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type'     => 'password',
                'custom_code'   => 'test custom code',
            ],
            'status_code' => 200,
        ],
    ],

    'testEditOtherOrg' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'put',
            'content' => [
                'email_domains' => ['fbapi.com'],
                'email' => 'test@hdfc.com',
                'allow_sign_up' => true,
                'display_name' => 'HDFC Bank Edited By RZP',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
                'custom_code' => 'hdfc',
            ],
        ],
        'response' => [
            'content' => [
                'email_domains' => ['fbapi.com'],
                'allow_sign_up' => true,
                'email' => 'test@hdfc.com',
                'display_name' => 'HDFC Bank Edited By RZP',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
            ],
            'status_code' => 200,
        ],
    ],

    'testFetchMultipleOrg' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
                        'id' => 'org_SBINbankOrgnId',
                        'display_name' => 'Razorpay',
                        'business_name' => 'Razorpay Software Pvt Ltd',
                        'email' => 'test@sbi.com',
                        'email_domains' => [
                            'sbi.com',
                        ],
                        'allow_sign_up' => false,
                        'login_logo_url' => null,
                        'main_logo_url' => null,
                        'auth_type' => 'password',
                    ],
                    [
                        'id' => 'org_100000razorpay',
                        'display_name' => 'Razorpay',
                        'business_name' => 'Razorpay Software Pvt Ltd',
                        'email' => 'admin@razorpay.com',
                        'email_domains' => [
                            'razorpay.com',
                            'rzp.io',
                        ],
                        'allow_sign_up' => false,
                        'login_logo_url' => null,
                        'main_logo_url' => null,
                        'auth_type' => 'password',
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testDeleteOrg' => [
        'request' => [
            'url' => '/orgs',
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

    'deleteOrgException' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testGetOrg' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'email'                => 'sreeram12@gmail.com',
                'permissions'          => [],
                'workflow_permissions' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testGetOrgWithFeatureEnabled' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'email'                => 'sreeram12@gmail.com',
                'permissions'          => [],
                'workflow_permissions' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testGetOtherOrg' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'email' => 'testotherrzp@gmail.com'
            ],
            'status_code' => 200,
        ],
    ],

    'testGetOrgByHostname' => [
        'request' => [
            'url' => '/orgs/hostname/dashboard.razorpay.com',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'id' => 'org_100000razorpay',
                'display_name' => 'Razorpay',
                'business_name' => 'Razorpay Software Pvt Ltd',
                'hostname' => 'dashboard.razorpay.com',
                'email' => 'admin@razorpay.com',
                'email_domains' => [
                    'razorpay.com',
                    'rzp.io',
                ],
                'allow_sign_up' => false,
                'login_logo_url' => null,
                'main_logo_url' => null,
                'auth_type' => 'password',
                'payment_apps_logo_url' => null,
                'payment_btn_logo_url'  => null
            ],
            'status_code' => 200,
        ],
    ],

    'testFeatureForOrg' => [
        'request' => [
            'url' => '/orgs/hostname/dashboard.razorpay.com',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'features' => [
                    'disable_announcements'
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateOrgInvalidHostname' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'post',
            'content' => [
                'hostname'  => 'invald@host%.com.com',
                'email_domains' => ['hdfc.com', 'fbapi.com'],
                'allow_sign_up' => 0,
                'email' => 'test@hdfc.com',
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
                'custom_code' => 'hdfc',
                'admin' => [
                    'name' => 'superadmin',
                    'branch_code' => 'a',
                    'employee_code' => 'a',
                    'location_code' => 'a',
                    'department_code' => 'a',
                    'supervisor_code' => 'a',
                    'username' => 'xyz93',
                    'password' => 'XYZ123!@#',
                    'password_confirmation' => 'XYZ123!@#',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid hostname provided',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateOrgNotUniqueHostname' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'post',
            'content' => [
                'hostname'  => 'test1.com',
                'email_domains' => ['hdfc.com', 'fbapi.com'],
                'allow_sign_up' => 0,
                'email' => 'test@hdfc.com',
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
                'custom_code' => 'hdfc',
                'admin' => [
                    'name' => 'superadmin',
                    'branch_code' => 'a',
                    'employee_code' => 'a',
                    'location_code' => 'a',
                    'department_code' => 'a',
                    'supervisor_code' => 'a',
                    'username' => 'xyz93',
                    'password' => 'XYZ123!@#',
                    'password_confirmation' => 'XYZ123!@#',
                ],
                'merchant_styles' => [
                    'color_code1' => '123',
                    'color_code2' => '345'
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The hostname has already been taken.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateOrgHostnameSameAsDeletedHostname' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'put',
            'content' => [
                'allow_sign_up' => 0,
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testCreateOrgInvalidAuthType' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'post',
            'content' => [
                'hostname'  => 'dashboard2.razorpay.com',
                'email_domains' => ['hdfc.com', 'fbapi.com'],
                'allow_sign_up' => 0,
                'email' => 'test@hdfc.com',
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'invalid_auth',
                'custom_code' => 'hdfc',
                'merchant_styles' => [
                    'color_code1' => '123',
                    'color_code2' => '345'
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected auth type is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testEditOrgWithPricingPlan' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'put',
            'content' => [
                'default_pricing_plan_id' => ''
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
];
