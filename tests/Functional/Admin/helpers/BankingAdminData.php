<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testInactiveIdamAdminLoginDueToDormancy' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username'  => 'randomadmin123@rzp.com',
                'password'  => 'test123456'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ADMIN_DISABLED_BY_DORMANCY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR
        ],
    ],

    'testInactiveIdamAdminLogin' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username'  => 'randomadmin123@rzp.com',
                'password'  => 'test123456'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ADMIN_DISABLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR
        ],
    ],

    'testActiveIdamAdminLoginDueToDormancy' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username'  => 'randomadmin123@rzp.com',
                'password'  => 'test123456'
            ],
        ],
        'response' => [
            'content' => [
                'email' => "randomadmin123@rzp.com",
                'name' => "Test User",
                'username' => "testadmin",
                'oauth_provider_id' => "google",
                'org_id' => "org_CLTnQqDj9Si8bx",
                'user_type' => null,
                'employee_code' => "rzp_1",
                'branch_code' => "krmgla",
                'department_code' => "tech",
                'supervisor_code' => "shk",
                'location_code' => "560030",
                'disabled' => false,
                'locked' => false,
                'deleted_at' => null,
                'allow_all_merchants' => false,
                'roles' => [],
            ],
            'status_code' => 200,
        ]
    ],

    'testActiveUserWithoutAdminMeta' => [
        'request' => [
            'url' => '/admin/authenticate',
            'method' => 'post',
            'content' => [
                'username'  => 'randomadmin123@rzp.com',
                'password'  => 'test123456'
            ],
        ],
        'response' => [
            'content' => [
                'email' => "randomadmin123@rzp.com",
                'name' => "Test User",
                'username' => "testadmin",
                'oauth_provider_id' => "google",
                'org_id' => "org_CLTnQqDj9Si8bx",
                'user_type' => null,
                'employee_code' => "rzp_1",
                'disabled' => false,
                'locked' => false,
                'deleted_at' => null,
                'allow_all_merchants' => false,
                'roles' => [],
            ],
            'status_code' => 200,
        ]
    ],

    'testOrgAdminsDisableForActiveUsers' => [
        'request' => [
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => 3,
                'failure' => 0,
            ],
            'status_code' => 200
        ]
    ],

    'testOrgAdminsDisableForInactiveUsers' => [
        'request' => [
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => 2,
                'failure' => 0,
            ],
            'status_code' => 200
        ]
    ],

    'testOrgAdminsDisableWhenAlreadyDisabled' => [
        'request' => [
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => 0,
                'failure' => 0,
            ],
            'status_code' => 200
        ]
    ],

    'testCreateOrgAdminWithoutDomainInUniqueIdentifier' => [
        'request' => [
            'method'    => 'POST',
            'headers'   => [
                'X-Org-Id'      => 'org_100000razorpay',
                'X-Admin-Token' => 'dummy-token'
            ],
            'url' => '/org/admins',
            'content' => [
                'auth_mode'         => 'adfs',
                'unique_identifier' => 'xv6vxwe7',
                'full_name'         => 'John Doe',
                'email'             => 'John.doe@axisbank.com',
                'username'          => 'username',
                'user_roles'        => [
                    'NcigjG1NJtRmtG'
                ],
                'expire_at'         => '1708625257'
            ]
        ],
        'response' => [
            'content' => [
                'unique_identifier' => 'xv6vxwe7@axisbank.com',
                'full_name'         => 'John Doe',
                'email'             => 'john.doe@axisbank.com',
                'user_roles'        => [
                    'role_NcigjG1NJtRmtG'
                ],
                'expire_at'         => 1709188200,
                'account_status'    => 'enable',
                'user_disabled_at'  => null,
                'last_login_at'     => null,
            ],
            'status_code' => 200
        ]
    ],
    
    'testCreateOrgAdminWithDomainInUniqueIdentifier' => [
        'request' => [
            'method'    => 'POST',
            'headers'   => [
                'X-Org-Id'      => 'org_100000razorpay',
                'X-Admin-Token' => 'dummy-token'
            ],
            'url' => '/org/admins',
            'content' => [
                'auth_mode'         => 'adfs',
                'unique_identifier' => 'xv6vxwe7@axisbank.com',
                'full_name'         => 'John Doe',
                'email'             => 'John.doe@axisbank.com',
                'username'          => 'username',
                'user_roles'        => [
                    'NcigjG1NJtRmtG'
                ],
                'expire_at'         => '1708625257'
            ]
        ],
        'response' => [
            'content' => [
                'unique_identifier' => 'xv6vxwe7@axisbank.com',
                'full_name'         => 'John Doe',
                'email'             => 'john.doe@axisbank.com',
                'user_roles'        => [
                    'role_NcigjG1NJtRmtG'
                ],
                'expire_at'         => 1709188200,
                'account_status'    => 'enable',
                'user_disabled_at'  => null,
                'last_login_at'     => null,
            ],
            'status_code' => 200
        ]
    ],

    'testGetOrgAdminWithoutDomainInUniqueIdentifier' => [
        'request' => [
            'method' => 'GET',
            'url' => '/org/admins',
            'headers' => [
                'X-Org-Id'      => 'org_100000razorpay',
                'X-Admin-Token' => 'dummy-token'
            ],
        ],
        'response' => [
            'content' => [
                'unique_identifier' => 'xv6vxwe7@axisbank.com',
                'full_name'         => 'Test User',
                'email'             => 'randomadmin123@rzp.com',
                'user_roles'        => [
                    'role_NcigjG1NJtRmtG'
                ],
                'expire_at'         => null,
                'account_status'    => 'enable',
                'user_disabled_at'  => null,
                'last_login_at'     => null,
            ],
            'status_code' => 200
        ]
    ],

    'testGetOrgAdminWithDomainInUniqueIdentifier' => [
        'request' => [
            'method' => 'GET',
            'url' => '/org/admins',
            'headers' => [
                'X-Org-Id'      => 'org_100000razorpay',
                'X-Admin-Token' => 'dummy-token'
            ],
        ],
        'response' => [
            'content' => [
                'unique_identifier' => 'xv6vxwe7@axisbank.com',
                'full_name'         => 'Test User',
                'email'             => 'randomadmin123@rzp.com',
                'user_roles'        => [
                    'role_NcigjG1NJtRmtG'
                ],
                'expire_at'         => null,
                'account_status'    => 'enable',
                'user_disabled_at'  => null,
                'last_login_at'     => null,
            ],
            'status_code' => 200
        ]
    ],

    'testUpdateOrgAdminWithoutDomainInUniqueIdentifier' => [
        'request' => [
            'method'    => 'PUT',
            'headers'   => [
                'X-Org-Id'      => 'org_100000razorpay',
                'X-Admin-Token' => 'dummy-token'
            ],
            'url' => '/org/admins',
            'content' =>  [
                "account_status" => "enable",
                "expire_at"      => '1713943621',
                "user_roles"     => [
                    "role_NnjOftbCqef4aK",
                    "role_NfvKQQrxbs6yrY"
                ],
                "full_name"           => "Test User"
            ]
        ],
        'response' => [
            'content' =>  [
                'unique_identifier' => 'xv6vxwe7@axisbank.com',
                'full_name' => 'Test User',
                'email' => 'randomadmin123@rzp.com',
                'account_status' => 'enable',
                'expire_at' => 1713943621,
                'last_login_at' => null,
                'user_disabled_at' => null,
                'user_roles' => [
                    'role_NfvJe1dbHXASc5',
                    'role_NfvKQQrxbs6yrY',
                ],
            ],
            'status_code' => 200
        ]
    ],
    

    'testUpdateOrgAdminWithDomainInUniqueIdentifier' => [
        'request' => [
            'method'    => 'PUT',
            'headers'   => [
                'X-Org-Id'      => 'org_100000razorpay',
                'X-Admin-Token' => 'dummy-token'
            ],
            'url' => '/org/admins',
            'content' =>  [
                "account_status" => "enable",
                "expire_at"      => '1713943621',
                "user_roles"     => [
                    "role_NnjOftbCqef4aK",
                    "role_NfvKQQrxbs6yrY"
                ],
                "full_name"           => "Test User"
            ]
        ],
        'response' => [
            'content' =>  [
                'unique_identifier' => 'xv6vxwe7@axisbank.com',
                'full_name' => 'Test User',
                'email' => 'randomadmin123@rzp.com',
                'account_status' => 'enable',
                'expire_at' => 1713943621,
                'last_login_at' => null,
                'user_disabled_at' => null,
                'user_roles' => [
                    'role_NfvJe1dbHXASc5',
                    'role_NfvKQQrxbs6yrY',
                ],
            ],
            'status_code' => 200
        ]
    ],
];
