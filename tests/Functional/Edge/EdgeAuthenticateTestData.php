<?php

use Functional\Edge\EdgeAuthenticateTest;
use RZP\Exception\BadRequestException;
use RZP\Http\Middleware\AdminAccess;
use RZP\Http\RequestHeader;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'baseRequest' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/edge/internal/authenticate',
            'server' => [
                'PHP_AUTH_USER' => 'rzp_test',
                'PHP_AUTH_PW'   => env('APP_EDGE_SECRET')
            ],
            'content' => [
                'dashboard' => [
                    'key' => 'rzp_test',
                    'secret' => env('APP_DASHBOARD_SECRET'),
                    'org_id' => Org::RZP_ORG,
                    'headers' => [
                        RequestHeader::X_ADMIN_TOKEN => Org::DEFAULT_ADMIN_TOKEN
                    ]
                ],
                'auth' => 'internal',
                'admin_token_required' => true,
                'apps' => ['dashboard', 'admin_dashboard'],
            ]
        ]
    ],
    'baseResponse' => [
        'response' => [
            'content'     => [
                'admin_id' => Org::SUPER_ADMIN,
                'org_id' => Org::RZP_ORG,
                'roles' => ['SuperAdmin'],
                'permissions' => ['view_homepage', 'update_config_key']
            ],
            'status_code' => 200
        ]
    ],

    'testInternalAuthWithInvalidAdminToken' => [
        'request' => ['content' => ['dashboard' => ['headers' => [RequestHeader::X_ADMIN_TOKEN => Org::DEFAULT_TOKEN . Org::MAKER_TOKEN_PRINCIPAL]]]],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ADMIN_TOKEN_MISMATCH,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ADMIN_TOKEN_MISMATCH,
        ],
    ],

    'testInternalAuthWithAdminTokenAndAccountId' => [
        'request' => ['content' => ['dashboard' => ['account_id' => EdgeAuthenticateTest::TEST_MERCHANT_ID]]]
    ],

    'testInternalAuthWithAdminTokenAndSignedAccountId' => [
    'request' => ['content' => ['dashboard' => ['account_id' => 'acc_' . EdgeAuthenticateTest::TEST_MERCHANT_ID]]]
    ],

    'testInternalAuthWithAdminTokenAndInvalidAccountId' => [
        'request' => ['content' => ['dashboard' => ['account_id' => EdgeAuthenticateTest::TEST_INVALID_MERCHANT_ID]]],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID,
                ],
            ],
            'status_code' => 401,
        ]
    ],
    'testInternalAuthWithAdminTokenAndUnmappedAccountId' => [
        'request' => ['content' => ['dashboard' => ['account_id' => EdgeAuthenticateTest::TEST_UNMAPPED_MERCHANT_ID]]],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ]
    ],
    'testInternalAuthWithoutAdminToken' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TOKEN_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ]
    ],
    'testInternalAuthWithProxyAuthKey' => [
        'request' => ['content' => ['dashboard' => ['key' => 'rzp_test_' . EdgeAuthenticateTest::TEST_MERCHANT_ID]]],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ]
    ],
    'testInternalAuthWithInvalidOrg' => [
        'request' => ['content' => ['dashboard' => ['org_id' => EdgeAuthenticateTest::TEST_INVALID_ORG_ID]]],
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
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],
    'testInternalAuthWithValidOrgHostname' => [
        'request' => ['content' => ['dashboard' => ['headers' => [AdminAccess::ORG_HOSTNAME_HEADER_KEY => EdgeAuthenticateTest::TEST_ORG_HOSTNAME]]]],
    ],

    'testInternalAuthWithMIDInRouteParams' => [
        'request' => ['content' => ['dashboard' => ['route_params' => ['merchant_id' => EdgeAuthenticateTest::TEST_MERCHANT_ID]]]],
    ],

    'testInternalAuthWithUnmappedMIDInRouteParams' => [
        'request' => ['content' => ['dashboard' => ['route_params' => ['merchant_id' => EdgeAuthenticateTest::TEST_UNMAPPED_MERCHANT_ID]]]],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ]
    ],
    'testProxyAuthWithAdminToken' => [
        'request' => ['content' =>
            [
                'dashboard' => ['key' => 'rzp_test_' . EdgeAuthenticateTest::TEST_MERCHANT_ID],
                'auth' => 'proxy'
            ]]
    ],
    'testInvalidProxyAuthWithAdminToken' => [
        'request' => ['content' => [
            'dashboard' => ['key' => 'rzp_test_' . EdgeAuthenticateTest::TEST_INVALID_MERCHANT_ID],
            'auth' => 'proxy'
        ]],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ]
    ],
    'testProxyAuthWithInternalAuthCreds' => [
        'request' => ['content' => [
            'auth' => 'proxy'
        ]],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ]
    ],
    'testProxyAuthWithValidOrgHostname' => [
        'request' => ['content' => ['dashboard' => ['headers' => [AdminAccess::ORG_HOSTNAME_HEADER_KEY => EdgeAuthenticateTest::TEST_ORG_HOSTNAME]]]],
    ],
    'testInvalidAuth' => [
        'request' => ['content' => ['auth' => 'invalid_auth']],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ]
    ],
    'testNonDashboardApp' =>  [
        'request' => ['content' => ['apps' => ['dashboard', 'mailgun'], 'dashboard' => ['secret' => env('APP_MAILGUN_SECRET')]]],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ]
    ],
    'testValidAppButDifferentSecret' => [
        'request' => ['content' => ['dashboard' => ['secret' => env('APP_FRONTEND_GRAPHQL_SECRET')]]],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ]
    ],
    'testInternalAuthWithAdminTokenRequiredFalse' => [
        'request' => ['content' => ['admin_token_required' => false]],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ]
    ]
];
