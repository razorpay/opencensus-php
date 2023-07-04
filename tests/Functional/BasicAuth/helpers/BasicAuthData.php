<?php

use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testNoAuth' => [
        'request' => [
            'url' => '/merchants',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testNoAuthOnJsonpRoute' => [
        'request' => [
            'url' => '/payments/create/jsonp',
            'method' => 'GET',
            'content' => [
                'callback' => 'abdefsdf',
                '_' => '',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED
                ]
            ],
            'status_code' => 401
        ],
    ],

    'testWrongKeyOnPublicJsonpRoute' => [
        'request' => [
            'url' => '/payments/create/jsonp',
            'method' => 'GET',
            'content' => [
                'callback' => 'abdefsdf',
                '_' => '',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY,
                ],
                'http_status_code' => 401,
            ],
            'status_code' => 200
        ],
        'jsonp' => true
    ],

    'testAppRoutesWithPrivateAuth' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ]
            ],
            'status_code' => 400,
        ]
    ],

    'testAdminAuth' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => []
            ],
            'status_code' => 200,
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'admin',
                'id'   => 'RzrpySprAdmnId',
                'meta' => [
                    'org_id' => '100000razorpay',
                ],
            ],
            'credential' => [],
            'roles' => [],
        ],
    ],

    'testAdminProxyAuthOnPrivateRoute' => [
        'request' => [
            'method' => 'GET',
            'url'    => '/balance',
        ],
        'response' => [
            'content' => [],
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'application',
                'id'   => 'admin_dashboard',
                'meta' => [
                    'name' => 'admin_dashboard',
                ],
            ],
            'impersonation' => [
                'type'     => 'admin_merchant',
                'consumer' => [
                    'type' => 'merchant',
                    'id'   => '10000000000000',
                ],
            ],
            'credential' => [],
            'roles' => [],
        ],
    ],

    'testAdminProxyAuthOnProxyRoute' => [
        'request' => [
            'method' => 'GET',
            'url'    => '/balances',
        ],
        'response' => [
            'content' => [],
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'application',
                'id'   => 'admin_dashboard',
                'meta' => [
                    'name' => 'admin_dashboard',
                ],
            ],
            'impersonation' => [
                'type'     => 'admin_merchant',
                'consumer' => [
                    'type' => 'merchant',
                    'id'   => '10000000000000',
                ],
            ],
            'credential' => [],
            'roles' => [],
        ],
    ],

    'testPrivateAuthOnAdminRoute' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testAppRoutesWithInvalidPrivateAuth' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ]
            ],
            'status_code' => 400,
        ]
    ],

    'testPrivateAuthOnPublicRoute' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payments',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_SECRET_SENT_ON_PUBLIC_ROUTE
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testUnauthorizedOnJsonpRoute' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/create/jsonp',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY,
                ],
                // 'http_status_code' => 401,
            ],
            'status_code' => 401,
        ],
    ],

    'testNoSecretOnPrivateRoute' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/1kKG3wHhnPdcg8',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testPublicAuthWithWrongKeyId' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payments',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testPublicAuth' => [
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => false,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'merchant',
                'id'   => '10000000000000',
            ],
            'credential' => [
                'username'   => 'rzp_test_TheTestAuthKey',
                'public_key' => 'rzp_test_TheTestAuthKey',
            ],
        ],
    ],

    'testAppAuthForCron' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payments/timeout',
        ],
        'response' => [
            'content' => [],
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'application',
                'id'   => 'cron',
                'meta' => [
                    'name' => 'cron',
                ],
            ],
            'credential' => [],
        ],
    ],

    'testAppAuthForCronWithXHeader' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payments/timeout',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [],
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'application',
                'id'   => 'cron',
                'meta' => [
                    'name' => 'cron',
                ],
            ],
            'credential' => [],
        ],
    ],

    'testPrivateAuthWithWrongKeyId' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/1kKG3wHhnPdcg8',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testPrimaryPrivateAuthWithoutXHeaderWithWrongSecret' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/1kKG3wHhnPdcg8',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testPrimaryPrivateAuthWithXHeaderWithWrongSecret' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/1kKG3wHhnPdcg8',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testBankingPrivateAuthWithoutXHeaderWithWrongSecret' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'self',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'url'     => '/contacts',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testBankingPrivateAuthWithXHeaderWithWrongSecret' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'self',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'url'     => '/contacts',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testBankingProxyAuthWithWrongUser' => [
        'request'  => [
            'url'    => '/contacts/cont_1000000contact',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testAppAuthWithNoSecret' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testAppAuthNewFlowWithoutPassport' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testAppAuthNewFlowWithPassportForCron' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payments/timeout',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testAppAuthNewFlowWithWrongPassportForCron' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payments/timeout',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testAppAuthNewFlowWithWrongAppForCron' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payments/timeout',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testPrivateAuthOnAppRoute' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testProxyAuthOnPrivateRouteInCloud' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments',
            'content' => [
                'count' => 1
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'items' => [
                ],
            ]
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'user',
                'id'   => 'MerchantUser01',
            ],
            'impersonation' => [
                'type'     => 'user_merchant',
                'consumer' => [
                    'type' => 'merchant',
                    'id'   => '10000000000000',
                ],
            ],
            'roles' => [
                "owner", "statement_balance_view_access", "misc_all_roles", "banking_info_view_access", "payout_create_view",
                "fundaccount_create_view", "contact_create_view", "workflow_view_access", "payout_link_create_view", "tds_categories_view",
                "tax_payment_settings_view", "accounting_view_access", "payout_attachment_create_view", "payout_link_settings_view",
                "payout_view_access", "fundaccount_view_access", "contact_view_access", "payout_link_view_access", "invoice_create_view",
                "invoice_view_access", "tax_create_view", "tax_view_access", "reporting_config", "merchant_analytics",
                "merchant_user_invite_create_view", "merchant_role_create_view", "merchant_user_view_access", "merchant_role_view_access",
                "dev_controls_create_view", "dev_controls_view_access", "accounting_create_view", "billing_invoices", "va_config",
                "business_info_config", "feature_controls", "merchant_misc_config", "merchant_preference_create", "workflow_create_view"
            ],
            'credential' => [],
        ],
    ],

    'testProxyAuthOnPrivateRouteNotInCloud' => [
        'request' => [
            'method' => 'GET',
            'url' => 'payments',
            'content' => [
                'count' => 1
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testProxyAuth' => [
        'request' => [
            'method' => 'GET',
            'url'    => '/webhooks/events/all',
        ],
        'response' => [
            'content' => [],
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'user',
                'id'   => 'MerchantUser01',
            ],
            'impersonation' => [
                'type'     => 'user_merchant',
                'consumer' => [
                    'type' => 'merchant',
                    'id'   => '10000000000000',
                ],
            ],
            'roles' => [
                "owner", "statement_balance_view_access", "misc_all_roles", "banking_info_view_access", "payout_create_view",
                "fundaccount_create_view", "contact_create_view", "workflow_view_access", "payout_link_create_view", "tds_categories_view",
                "tax_payment_settings_view", "accounting_view_access", "payout_attachment_create_view", "payout_link_settings_view",
                "payout_view_access", "fundaccount_view_access", "contact_view_access", "payout_link_view_access", "invoice_create_view",
                "invoice_view_access", "tax_create_view", "tax_view_access", "reporting_config", "merchant_analytics",
                "merchant_user_invite_create_view", "merchant_role_create_view", "merchant_user_view_access", "merchant_role_view_access",
                "dev_controls_create_view", "dev_controls_view_access", "accounting_create_view", "billing_invoices", "va_config",
                "business_info_config", "feature_controls", "merchant_misc_config", "merchant_preference_create", "workflow_create_view"
            ],
            'credential' => [],
        ],
    ],

    'testProxyAuthWithoutAuthzRoles' => [
        'request' => [
            'method' => 'GET',
            'url'    => '/webhooks/events/all',
        ],
        'response' => [
            'content' => [],
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'user',
                'id'   => 'MerchantUser01',
            ],
            'impersonation' => [
                'type'     => 'user_merchant',
                'consumer' => [
                    'type' => 'merchant',
                    'id'   => '10000000000000',
                ],
            ],
            'roles' => [
                'manager'
            ],
            'credential' => [],
        ],
    ],

    'testPrivateAuthKeyNotExpired' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments',
            'content' => [
                'count' => 1
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'items' => [
                ],
            ]
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'merchant',
                'id'   => '10000000000000',
            ],
            'credential' => [
                'username'   => 'rzp_test_TheTestAuthKey',
                'public_key' => 'rzp_test_TheTestAuthKey',
            ],
        ],
    ],

    'testBalancesApiOnPrivateAuth' => [
        'request' => [
            'method'    => 'GET',
            'content'   => [
                'type' => 'banking'
            ]
        ],
        'response' => [
            'content' => [
                    'entity' => 'collection',
                    'count' => 2,
                    'items' =>
                        [
                                [
                                    'type' => 'banking',
                                    'currency' => 'INR',
                                    'balance' => 800000,
                                    'account_number' => 'XXXXXXXXXXXX6905',
                                    'account_type' => 'direct',
                                ],
                                [
                                    'type' => 'banking',
                                    'currency' => 'INR',
                                    'balance' => 300000,
                                    'account_number' => 'XXXXXXXXXXXX6903',
                                    'account_type' => 'shared',
                                ],
                        ],
            ]
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'merchant',
                'id'   => '10000000000000',
            ],
            'credential' => [
                'username'   => 'rzp_test_TheTestAuthKey',
                'public_key' => 'rzp_test_TheTestAuthKey',
            ],
        ],
    ],

    'testPrivateAuthAndPassportJwtIssuedByApi' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments',
            'content' => [
                'count' => 1
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'items' => [
                ],
            ]
        ],
    ],

    'testPrivateAuthKeyExpired' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments',
            'content' => [
                'count' => 1
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testAppAuthWithAccount' => [
        'request' => [
            'method' => 'GET',
            'url' => '/orgs/{id}/self'
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testGraphqlAppAuth' => [
        'request'  => [
            'url'    => '/users/id',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'contact_mobile'          => null,
                'contact_mobile_verified' => false,
                'confirmed'               => true,
                'merchants'               => [
                    [
                        'activated'    => false,
                        'archived_at'  => null,
                        'suspended_at' => null,
                        'role'         => 'owner',
                    ],
                ],
                'invitations'             => [
                ],
                'settings'                => [
                ],
            ],
        ],
    ],

    'testGraphqlAppAuthOnProxyRoute' => [
        'request'  => [
            'url'    => '/primary_balance',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testAdminAuthWithAccount' => [
        'request' => [
            'method' => 'GET',
            'url' => '/dummy/admin'
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'admin',
                'id'   => 'RzrpySprAdmnId',
                'meta' => [
                    'org_id' => '100000razorpay',
                ],
            ],
            'impersonation' => [
                'type'     => 'admin_merchant',
                'consumer' => [
                    'type' => 'merchant',
                    // 'id'   => '10000000000000',
                ],
            ],
            'credential' => [],
            'roles' => [],
        ],
    ],

    'testAccountAuthInvalidIdViaMerchantDashboard' => [
        'request' => [
            'method' => 'GET',
            'url' => '/orgs/{id}/self'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID,
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testAccountAuthInvalidIdViaMerchantDashboardWithXHeader' => [
        'request' => [
            'method' => 'GET',
            'url' => '/orgs/{id}/self',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID,
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testAccountAuthInvalidIdViaAdminDashboard' => [
        'request' => [
            'method' => 'GET',
            'url' => '/orgs/{id}/self'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID,
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testUserWhiteListAuthenticate' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/users/resend-verification',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_NOT_AUTHENTICATED,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testUserWhiteListAuthenticateWithXHeader' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/users/resend-verification',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_NOT_AUTHENTICATED,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testFailedMerchantUserRouteValidation' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'get',
            'content' => [
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testPartnerAuthOnJsonpRoute' => [
        'request'   => [
            'url'     => '/emi',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'HDFC' => [
                    'min_amount' => 500000,
                    'plans' => [
                        '9' => 12,
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testPartnerAuthOnJsonpRouteWrongClientId' => [
        'request'   => [
            'url'     => '/emi',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testPartnerAuthOnJsonpRouteAppMissing' => [
        'request'   => [
            'url'     => '/emi',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testPartnerAuthOnJsonpRouteWrongMerchantForClient' => [
        'request'   => [
            'url'     => '/emi',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testPartnerAuthOnJsonpRouteApiKey' => [
        'request'   => [
            'url'     => '/emi',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testPartnerAuthWithoutAccountIdInHeader' => [
        'request'   => [
            'url'     => '/emi',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Account id is required with partner credentials',
                ]
        ],
            'status_code' => 400,
        ],
    ],

    'testRequestWithPartnerHeadersClientCreds' => [
        'request'   => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testRequestWithPartnerHeadersPurePlatform' => [
        'request'   => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_AUTH_NOT_ALLOWED,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testRequestWithPartnerNoSecret' => [
        'request'   => [
            'url'     => '/customers',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testRequestWithPartnerHeadersClientCredsWrongMode' => [
        'request'   => [
            'url'     => '/customers',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testRequestWithPartnerHeadersWrongClientCreds' => [
        'request'   => [
            'url'     => '/customers',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET,
                ]
            ],
            'status_code' => 401,
        ],
    ],

    'testRequestWithPartnerInactiveMerchantLiveMode' => [
        'request'   => [
            'url'     => '/customers',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_FOR_LIVE_REQUEST,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => PublicErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testRequestWithPartnerHeadersClientCredsNotPartner' => [
        'request'   => [
            'url'     => '/customers',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_AUTH_NOT_ALLOWED,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testPartnerRequestOnNonMappedMerchant' => [
        'request'   => [
            'url'     => '/customers',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testRequestWithTwoFaRequiredWithTwoFaVerifiedTrue'     => [
        'request'       => [
            // URL will be set in the function
            'method'    => 'PUT',
            'content'   => [
                'delay_roll'       => '1',
            ],
            'server'    => [
                'HTTP_X-Dashboard-User-2FA-Verified'        => 'true',
                'HTTP_X-Dashboard-User-Role'                => 'owner',
            ],
        ],

        'response'      => [
            'content'       => [],
        ]
    ],

    'testRequestWithTwoFaRequiredWithTwoFaVerifiedFalseFromBanking'     => [
        'request'       => [
            // URL will be set in the function
            'method'    => 'PUT',
            'content'   => [
                'delay_roll'       => '1',
            ],
            'server'    => [
                'HTTP_X-Dashboard-User-Role'                => 'owner',
                'HTTP_X-Request-Origin'                     => config('applications.banking_service_url'),
            ],
        ],

        'response'      => [
            'content'       => [
                'error'         => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    '_internal'     => [
                        'internal_error_code'       => 'BAD_REQUEST_USER_2FA_VALIDATION_REQUIRED',
                    ],
                ],
            ],
            'status_code'   => 400,
        ],

        'exception'     => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'message'               => 'User\'s 2FA validation is required for this action',
            'internal_error_code'   => 'BAD_REQUEST_USER_2FA_VALIDATION_REQUIRED',
        ],
    ],

    'testRequestWithTwoFaRequiredWithTwoFaVerifiedFalse'     => [
        'request'       => [
            // url is set in the function
            'method'    => 'PUT',
            'content'   => [
                'delay_roll'    => '1',
            ],
        ],

        'response'      => [
            'content'       => [
                'error'         => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    '_internal'     => [
                        'internal_error_code'       => 'BAD_REQUEST_USER_2FA_VALIDATION_REQUIRED',
                    ],
                ],
            ],
            'status_code'   => 400,
        ],

        'exception'     => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'message'               => 'User\'s 2FA validation is required for this action',
            'internal_error_code'   => 'BAD_REQUEST_USER_2FA_VALIDATION_REQUIRED',
        ],
    ],

    'testRequestWithTwoFaRequiredOnlyOnLiveWithTwoFaVerifiedFalse'      => [
        'request'       => [
            // URL will be set in the function
            'method'    => 'PUT',
            'content'   => [
                'delay_roll'       => '1',
            ],
            'server'    => [
                'HTTP_X-Dashboard-User-Role'                => 'owner',
            ],
        ],

        'response'      => [
            'content'       => [],
        ],
    ],

    'testAdminRouteWildcardPermissionFail' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/admins',
            'content' => [
            ],
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => 'BAD_REQUEST_ACCESS_DENIED',
        ],
    ],

    'testPassportTokenForJob' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments',
            'content' => [
                'count' => 1
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'items' => [
                ],
            ]
        ],
    ],

    'testCreateOrderWithAccId' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'account_id'    => 'acc_100000Razorpay',
                'notes'         => ['key' => 'value']
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
            ],
            'status_code' => 200,
        ],
    ],

    'testMerchantAuthWithImpersonationForWhitelisted' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'account_id'    => 'acc_100000Razorpay',
                'notes'         => ['key' => 'value']
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
            ],
            'status_code' => 200,
        ],
    ],

    'testMerchantAuthWithImpersonationForNonWhitelisted' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'account_id'    => 'acc_100000Razorpay',
                'notes'         => ['key' => 'value']
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCOUNT_ID_IN_BODY,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testOrderEditWithAccId' => [
        'request'  => [
            'content' => [
                'notes' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
                'account_id' => 'acc_100000Razorpay',
            ],
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'notes' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testPaymentFetchWithAccId' => [
        'request'   => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'account_id' => 'acc_100000Razorpay',
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testGetPayoutsPurposeApiOnPrivateAuth' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payouts/purposes',
        ],
        'response' => [
            'content' => [
                    'entity' => 'collection',
                    'count' => 7,
                    'items' =>
                                [
                                    [
                                        'purpose' => 'refund',
                                        'purpose_type' => 'refund',
                                    ],
                                    [
                                            'purpose' => 'cashback',
                                            'purpose_type' => 'refund',
                                    ],
                                    [
                                            'purpose' => 'payout',
                                            'purpose_type' => 'settlement',
                                    ],
                                    [
                                            'purpose' => 'salary',
                                            'purpose_type' => 'settlement',
                                    ],
                                    [
                                            'purpose' => 'utility bill',
                                            'purpose_type' => 'settlement',
                                    ],
                                    [
                                            'purpose' => 'vendor bill',
                                            'purpose_type' => 'settlement',
                                    ],
                                    [
                                        'purpose' => 'vendor advance',
                                        'purpose_type' => 'settlement',
                                    ],
                                ]
            ]
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'merchant',
                'id'   => '10000000000000',
            ],
            'credential' => [
                'username'   => 'rzp_test_TheTestAuthKey',
                'public_key' => 'rzp_test_TheTestAuthKey',
            ],
        ],
    ],
];
