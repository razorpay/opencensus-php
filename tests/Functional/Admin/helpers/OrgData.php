<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateOrg' => [
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
                'custom_code' => 'hdfc',
                'admin' => [
                    'name' => 'superadmin',
                    'branch_code' => 'a',
                    'employee_code' => 'a',
                    'location_code' => 'a',
                    'department_code' => 'a',
                    'supervisor_code' => 'a',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'email_domains' => [
                    'hdfc.com',
                    'fbapi.com'
                ],
                'allow_sign_up' => false,
                'email' => 'test@hdfc.com',
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
            ],
            'status_code' => 200,
        ],
    ],

    'testEditOrg' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'put',
            'content' => [
                'email_domains' => ['fbapi.com'],
                'email' => 'test@hdfc.com',
                'allow_sign_up' => true,
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
                'custom_code' => 'hdfc',
            ],
        ],
        'response' => [
            'content' => [
                'email_domains' => [
                    'fbapi.com'
                ],
                'allow_sign_up' => true,
                'email' => 'test@hdfc.com',
                'display_name' => 'HDFC Bank',
                'business_name' => 'HDFC Bank Public Limited',
                'auth_type' => 'password',
            ],
            'status_code' => 200,
        ],
    ],

    'testfetchMultipleOrg' => [
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
                        'id' => 'org_RazorpayOrgnId',
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
                    ],
                    [
                        'id' => 'org_HDFCbankOrgnId',
                        'display_name' => 'Razorpay',
                        'business_name' => 'Razorpay Software Pvt Ltd',
                        'email' => 'test@hdfcbank.com',
                        'email_domains' => [
                            'hdfcbank.com',
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
                'email' => 'sreeram12@gmail.com'
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
                'id' => 'org_RazorpayOrgnId',
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
                'hostname'  => 'dashboard.razorpay.com',
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
];
