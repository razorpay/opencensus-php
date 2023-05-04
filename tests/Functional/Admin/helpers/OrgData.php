<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateOrgBankAccount' => [
        'request' => [
            'url' => '/org/bank_account',
            'method' => 'post',
            'content' => [
                'entity_id'             => '100000razorpay',
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org',
            ],

        ],
        'response' => [
            'content' => [
                'entity_id'             => '100000razorpay',
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org',            ]
        ]
    ],
    'testCreateOrgBankAccount2' => [
        'request' => [
            'url' => '/org/bank_account',
            'method' => 'post',
            'content' => [
                'entity_id'             => '100001razorpay',
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org',
            ],

        ],
        'response' => [
            'content' => [
                'entity_id'             => '100001razorpay',
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org',            ]
        ]
    ],
    'testCreateOrgBankAccountFailureWithFeatureFlag' => [
        'request' => [
            'url' => '/org/bank_account',
            'method' => 'post',
            'content' => [
                'entity_id'             => '100000razorpay',
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org',
            ],

        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_UNAUTHORIZED
        ],
    ],
    'testGetOrgBankAccount' => [
        'request' => [
            'url' => '/org/100000razorpay/bank_account',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity_id'             => '100000razorpay',
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org',            ]
        ]
    ],
    'testGetOrgBankAccount2' => [
        'request' => [
            'url' => '/org/100001razorpay/bank_account',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity_id'             => '100001razorpay',
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org',            ]
        ]
    ],
    'testUpdateOrgBankAccount' => [
        'request' => [
            'url' => '/org/100000razorpay/bank_account',
            'method' => 'put',
            'content' => [
                'account_number'        => '0002020000304030435',
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'             => '100000razorpay',
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030435',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org',
            ]
        ]
    ],
    'testUpdateOrgBankAccount2' => [
        'request' => [
            'url' => '/org/100001razorpay/bank_account',
            'method' => 'put',
            'content' => [
                'account_number'        => '0002020000304030435',
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'             => '100001razorpay',
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030435',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org',
            ]
        ]
    ],
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
                ],
                'external_redirect_url' => 'https://abc.razorpay.com',
                'external_redirect_url_text' => 'Some text',
                'merchant_session_timeout_in_seconds' => 600,
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
                'custom_code'   => 'test custom code',
                'external_redirect_url' => 'https://abc.razorpay.com',
                'external_redirect_url_text' => 'Some text'
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
                'external_redirect_url' => 'https://abc.razorpay.com',
                'external_redirect_url_text' => 'Some text',
                'merchant_session_timeout_in_seconds' => 600,
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
                'external_redirect_url' => 'https://abc.razorpay.com',
                'external_redirect_url_text' => 'Some text'
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
                'merchant_max_wrong_2fa_attempts' => 5,
                'merchant_session_timeout_in_seconds' => 600,
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
                'admin_max_wrong_2fa_attempts' => 7,
                'merchant_session_timeout_in_seconds' => 600,
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
                'second_factor_auth_mode' => 'sms_and_email',
                'merchant_session_timeout_in_seconds' => 600,
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
                'merchant_session_timeout_in_seconds' => 600,
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
                'merchant_session_timeout_in_seconds' => 600,
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
                'merchant_session_timeout_in_seconds' => 600,
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
                'payment_btn_logo_url'  => null,
            ],
            'status_code' => 200,
        ],
    ],

    'testGetOrgByHostnameDevstack' => [
        'request' => [
            'url' => '/orgs/hostname/dashboard-bankingaxis.dev.razorpay.in',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'email'                => 'bankingtest@axis.com',
                'hostname'             => 'dashboard-bankingaxis.dev.razorpay.in',
            ],
            'status_code' => 200,
        ],
    ],

    'testGetOrgByHostnameCurlecDevstack' => [
        'request' => [
            'url' => '/orgs/hostname/dashboard-testing-curlec.dev.razorpay.in',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'email'                => 'testing@curlec.com',
                'hostname'             => 'dashboard-testing-curlec.dev.razorpay.in',
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
                'merchant_session_timeout_in_seconds' => 600,
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
                'merchant_session_timeout_in_seconds' => 600,
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
                'default_pricing_plan_id' => '',
                'merchant_session_timeout_in_seconds' => 600,
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
    'testFeatureAddVAExpiry' => [
        'request' => [
            'url' => '/orgs/hostname/dashboard.razorpay.com',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'features' => [
                    'set_va_default_expiry'
                ]
            ],
            'status_code' => 200,
        ],
    ],
    
    'testCreateOrgWithMerchantSessionTimeoutWithoutSplitz' => [
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
                ],
                'external_redirect_url' => 'https://abc.razorpay.com',
                'external_redirect_url_text' => 'Some text',
                'merchant_session_timeout_in_seconds' => 600,
            ],
        ],
        'response' => [
            'content' => [
                'email'                => 'test@hdfc.com',
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateOrgWithoutMerchantSessionTimeoutWithoutSplitz' => [
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
                ],
                'external_redirect_url' => 'https://abc.razorpay.com',
                'external_redirect_url_text' => 'Some text',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The merchant session timeout in seconds field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],


    'testEditOrgWithoutMerchantSessionTimeout' => [
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
                'external_redirect_url' => 'https://abc.razorpay.com',
                'external_redirect_url_text' => 'Some text',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The merchant session timeout in seconds field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testEditOrgNonIntMerchantSessionTimeout' => [
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
                'external_redirect_url' => 'https://abc.razorpay.com',
                'external_redirect_url_text' => 'Some text',
                'merchant_session_timeout_in_seconds' => 'fifteen'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The merchant session timeout in seconds must be a number.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testEditOrgLessThanMinMerchantSessionTimeout' => [
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
                'external_redirect_url' => 'https://abc.razorpay.com',
                'external_redirect_url_text' => 'Some text',
                'merchant_session_timeout_in_seconds' => 240
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The merchant session timeout in seconds must be at least 300.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testEditOrgWithMerchantSessionTimeout' => [
        'request'  => [
            'url'     => '/orgs',
            'method'  => 'put',
            'content' => [
                'email_domains' => ['fbapi.com'],
                'hostname'      => 'test1.com, test2.com',
                'email'         => 'testrzp@gmail.com',
                'allow_sign_up' => true,
                'display_name'  => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type'     => 'password',
                'custom_code'   => 'test custom code',
                'external_redirect_url' => 'https://abc.razorpay.com',
                'external_redirect_url_text' => 'Some text',
                'merchant_session_timeout_in_seconds' => 600,
            ],
        ],
        'response' => [
            'content' => [
                'email'                => 'testrzp@gmail.com',
                'permissions'          => [],
                'workflow_permissions' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testGetOrgWithMerchantSessionTimeout' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'email'                => 'testrzp@gmail.com',
            ],
            'status_code' => 200,
        ],
    ],

    'testGetOrgByHostnamePreviewURLDevstack' => [
        'request' => [
            'url' => '/orgs/hostname/dashboard-pr-6000.dev.razorpay.in',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'email'                => 'testingpreview@razorpay.com',
                'hostname'             => 'dashboard-pr-6000.dev.razorpay.in',
            ],
            'status_code' => 200,
        ],
    ],

];
