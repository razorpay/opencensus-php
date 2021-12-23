<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Exception\BadRequestValidationFailureException;

return [
    'testCreateKey' => [
        'request' => [
            'method' => 'POST',
            'url' => '/keys',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'key',
                'expired_at' => null,
            ]
        ]
    ],

    'testCreateKeyForAxisOrgMerchantShouldUseAxisKeyForEncryption' => [
        'request' => [
            'method' => 'POST',
            'url' => '/keys',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'key',
                'expired_at' => null,
            ]
        ]
    ],

    'testInternationalEnableMerchantBulk' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000000'],
                'action'       => "enable_international",
                "international_products" => ["payment_gateway", "payment_pages", "payment_links", "invoices"],
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 1,
                'failed'    => 0,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testInternationalDisableMerchantBulk' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000000'],
                'action'       => "disable_international",
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 1,
                'failed'    => 0,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testEnableInternationalMerchantBulkNewFlow' => [
        'responseWorkflowActionApproval' => [
            'content' => [
                'maker_id'      => "admin_RzrpySprAdmnId",
                'maker_type'    => "admin",
                'maker'         => [
                    'id' => "admin_RzrpySprAdmnId",
                ],
                'permission'    => [
                    'name' => "execute_merchant_enable_international_bulk",
                ],
                'state'         => "executed",
                'state_changer' => [
                    'id' => "admin_RzrpySprAdmnId",
                ],
                'org_id'        => "org_100000razorpay",
                'approved'      => true,
            ]
        ],
    ],

    'testDisableInternationalMerchantBulkNewFlow' => [
        'responseWorkflowActionApproval' => [
            'content' => [
                'maker_id'      => "admin_RzrpySprAdmnId",
                'maker_type'    => "admin",
                'maker'         => [
                    'id' => "admin_RzrpySprAdmnId",
                ],
                'permission'    => [
                    'name' => "execute_merchant_disable_international_bulk",
                ],
                'state'         => "executed",
                'state_changer' => [
                    'id' => "admin_RzrpySprAdmnId",
                ],
                'org_id'        => "org_100000razorpay",
                'approved'      => true,
            ]
        ],
    ],

    'createBulkWorkflowAction' => [
        'disable_international' => [
            'request'  => [
                'content' => [
                    'action'          => 'disable_international',
                    'merchant_ids'    => ['10000000000000'],
                    'risk_attributes' => [
                        'trigger_communication' => '1',
                        'risk_tag'              => 'risk_international_disablement',
                        'risk_source'           => 'high_fts',
                        'risk_reason'           => 'chargeback_and_disputes',
                        'risk_sub_reason'       => 'high_fts',
                    ],
                ],
                'method'  => 'PUT',
                'url'     => '/merchants/bulk',
            ],
            'response' => [
                'status_code' => 200,
                'content'     => [
                    'entity_name'   => 'bulk_workflow_action',
                    'state'         => "open",
                    'maker_type'    => "admin",
                    'org_id'        => "org_100000razorpay",
                    'approved'      => false,
                    'current_level' => 1,
                ],
            ],
        ],
        'enable_international'  => [
            'request'  => [
                'content' => [
                    'action'          => 'enable_international',
                    'merchant_ids'    => ['10000000000000'],
                    'risk_attributes' => [
                        "international_products" => ["payment_gateway", "payment_pages", "payment_links", "invoices"],
                    ],
                ],
                'method'  => 'PUT',
                'url'     => '/merchants/bulk',
            ],
            'response' => [
                'status_code' => 200,
                'content'     => [
                    'entity_name'   => 'bulk_workflow_action',
                    'state'         => "open",
                    'maker_type'    => "admin",
                    'org_id'        => "org_100000razorpay",
                    'approved'      => false,
                    'current_level' => 1,
                ],
            ],
        ],
    ],

    'testSuspendMerchantBulk' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044', '10000000000055'],
                'action'       => "suspend",
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 2,
                'success'   => 2,
                'failed'    => 0,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testSuspendMerchantBulkWithoutPermissionFail' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044', '10000000000055'],
                'action'       => "suspend",
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access Denied',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testEditBulkMerchantAttributes' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044', '10000000000055'],
                'attributes'   => [
                    'hold_funds'           => 1,
                    'whitelisted_ips_live' => ['1.1.1.1', '2.2.2.2']
                ],
            ],
            'server'  => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 2,
                'success'   => 2,
                'failed'    => 0,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testEditBulkMerchantAction' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044', '10000000000055'],
                'action'       => 'hold_funds',
            ],
            'server'  => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 2,
                'success'   => 2,
                'failed'    => 0,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testFailedBulkMerchant' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044', '10000000000055'],
                'action'       => 'hold_funds',
                'attributes'   => [
                    'whitelisted_ips_live' => ['1.1.1.1', '2.2.2.2']
                ],
            ],
            'server'  => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Both Action and Attributes should not be sent.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateKeyForNonActivatedMerchant' => [
        'request' => [
            'method' => 'POST',
            'url' => '/keys',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_KEY_CREATE_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_KEY_CREATE_FAILED,
        ],
    ],

    'testGetMerchant' => [
        'request' => [
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
                'entity' => 'merchant',
                'name'  => 'Tester 2',
                'email' => 'liveandtest@localhost.com',
                'activated' => false,
                'activated_at' => null,
                'methods' => [
                    'merchant_id' => '1X4hRFHFx4UiXt',
                    'paytm' => false,
                    'disabled_banks' => [],
                ],
                'receipt_email_trigger_event' => 'authorized',
            ],
        ],
    ],

    'testGetMerchantDefaultDccMarkup' => [
        'request' => [
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
                'entity' => 'merchant',
                'name'  => 'Tester 2',
                'email' => 'liveandtest@localhost.com',
                'activated' => false,
                'activated_at' => null,
                'methods' => [
                    'merchant_id' => '1X4hRFHFx4UiXt',
                    'paytm' => false,
                    'disabled_banks' => [],
                ],
                'receipt_email_trigger_event' => 'authorized',
            ],
        ],
    ],

    'testGetMerchantDccMarkup' => [
        'request' => [
            'url' => '/merchants/10000000000000',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id'                        => '10000000000000',
                'entity'                    => 'merchant',
                'dcc_markup_percentage'     => 2,
            ],
        ],
    ],

    'testGetMerchantDccMarkupWithMultipleConfigs' => [
        'request' => [
            'url' => '/merchants/10000000000000',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id'                        => '10000000000000',
                'entity'                    => 'merchant',
                'dcc_markup_percentage'     => 2.13,
            ],
        ],
    ],

    'testGetMerchantUsers' => [
        'request' => [
            'url' => '/merchants/1X4hRFHFx4UiXt/users',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                [
                    'role' => 'owner'
                ],
                [
                    'role' => 'manager'
                ]
            ],
        ],
    ],

    'testGetMerchantUsersInternal' => [
        'request' => [
            'url' => '/merchants/1X4hRFHFx4UiXt/users',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                [
                    'role' => 'owner'
                ],
                [
                    'role' => 'manager'
                ]
            ],
        ],
    ],

    'testGetBalance' => [
        'request' => [
            'url' => '/balance',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id'    => '10000000000000',
                'balance' => 1000000,
            ],
        ],
    ],

    'testGetAccountConfig' => [
        'request' => [
            'url' => '/account/config',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'brand_color' => null,
                'transaction_report_email' => [
                    'test@razorpay.com'
                ],
                'default_refund_speed' =>  'normal',
                'fee_bearer' => 'platform',
            ],
        ],
    ],

    'testMerchantEmailGetUserStatus' => [
        'request' => [
            'content' => [
                'email'                  => 'newowner@gmail.com',
                'set_contact_email'      => true,
                'reattach_current_owner' => true
            ],
            'url' => '/merchants/email_user/status',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'logout_sessions_for_users' => []
            ],
        ],
    ],

    'testMerchantEmailUpdateCreateNewUser' => [
        'request' => [
            'content' => [
                'token'                 => '',
                'password'              => 'New124@user',
                'password_confirmation' => 'New124@user',
                'merchant_id'           => '',
            ],
            'url' => '/merchants/email/update/create_user',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testMerchantEmailUpdateCreateNewUserByExpiredToken' => [
        'request'   => [
            'url'     => '/merchants/email/update/create_user',
            'method'  => 'POST',
            'content' => [
                'token'                 => '',
                'password'              => 'New124@user',
                'password_confirmation' => 'New124@user',
                'merchant_id'           => '',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID,
        ],
    ],

    'testMerchantEmailUpdateExistingUser' => [
        'request' => [
            'content' => [
                'email'                  => 'newowner@gmail.com',
                'set_contact_email'      => true,
                'reattach_current_owner' => true
            ],
            'url' => '/merchants/email/update',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'logout_sessions_for_users' => []
            ],
        ],
    ],

    'testMerchantEmailUpdateEmailUserExistUserHasCrossOrgMerchantFail' => [
        'request' => [
            'content' => [
                'email'                  => 'newowner@gmail.com',
                'set_contact_email'      => true,
                'reattach_current_owner' => true
            ],
            'url' => '/merchants/email/update',
            'method' => 'PUT',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'We are unable to change your email Id to newowner@gmail.com. Please reach out to our support team to perform this action',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantFetchKeys' => [
        'request' => [
            'content' => [
            ],
            'url' => '/keys',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    '0' => [
                        'id' => 'rzp_test_TheTestAuthKey',
                        'expired_at' => null
                    ],
                ],
            ]
        ]
    ],

    'testUpdateKeyExpireNow' => [
        'request' => [
            'content' => [
            ],
            'url' => '/keys/rzp_test_TheTestAuthKey',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'old' => [
                ],
                'new' => [
                ]
            ],
        ],
    ],

    'testUpdateKeyExpireInFuture' => [
        'request' => [
            'content' => [
                'delay_roll' => '1'
            ],
            'url' => '/keys/rzp_test_TheTestAuthKey',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'old' => [
                ],
                'new' => [
                ]
            ],
        ],
    ],

    'testUpdateKeyTwice' => [
        'request' => [
            'content' => [
            ],
            'url' => '/keys/rzp_test_TheTestAuthKey',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_KEY_EXPIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_KEY_EXPIRED,
        ],
    ],

    'testRollDemoKey' => [
        'request' => [
            'content' => [
            ],
            'url' => '/keys/rzp_test_1DP5mmOlF5G5ag',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_KEY_OF_DEMO_ACCOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_KEY_OF_DEMO_ACCOUNT,
        ],
    ],

    'testEditMerchant' => [
        'request' => [
            'raw' => json_encode([
                'linked_account_kyc' => '1',
                'website' => 'http://abc.com',
                'category' => '1111',
                'transaction_report_email'  => [
                    'test@razorpay.com'
                ],
                'fee_credits_threshold'       => 1000,
                'receipt_email_trigger_event' => 'captured',
            ]),
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'CONTENT_TYPE'  => 'application/json',
                'HTTP_X-Dashboard' => 'true',
            ]
],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'entity' => 'merchant',
                'linked_account_kyc' => true,
                'category' => '1111',
                'website' => 'http://abc.com',
                'transaction_report_email'  => [
                    'test@razorpay.com'
                ],
                'fee_credits_threshold'       => 1000,
                'receipt_email_trigger_event' => 'captured'
            ]
        ]
    ],

    'testEditMerchantWithHighRiskThreshold' => [
        'request' => [
            'raw' => json_encode([
                'linked_account_kyc' => '1',
                'website' => 'http://abc.com',
                'category' => '1111',
                'transaction_report_email'  => [
                    'test@razorpay.com'
                ],
                'fee_credits_threshold'     => 1000,
                'risk_threshold' => 101
            ]),
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'CONTENT_TYPE'  => 'application/json',
                'HTTP_X-Dashboard' => 'true',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The risk threshold may not be greater than 100.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditMerchantWithNullFeeCreditsThreshold' => [
        'request' => [
            'raw' => json_encode([
                'linked_account_kyc' => '1',
                'website' => 'https://www.example.com',
                'category' => 1111,
                'transaction_report_email'  => [
                    'test@razorpay.com'
                ],
                'fee_credits_threshold'     => null
            ]),
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'CONTENT_TYPE'  => 'application/json',
                'HTTP_X-Dashboard' => 'true',
            ]
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'entity' => 'merchant',
                'linked_account_kyc' => true,
                'category' => '1111',
                'website' => 'https://www.example.com',
                'transaction_report_email'  => [
                    'test@razorpay.com'
                ],
                'fee_credits_threshold'    => null
            ]
        ]
    ],

    'testEditMerchantWithNullAmountCreditsThreshold' => [
        'request' => [
            'raw' => json_encode([
                'linked_account_kyc' => '1',
                'website' => 'https://www.example.com',
                'category' => 1111,
                'transaction_report_email'  => [
                    'test@razorpay.com'
                ],
                'amount_credits_threshold'     => null
            ]),
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'CONTENT_TYPE'  => 'application/json',
                'HTTP_X-Dashboard' => 'true',
            ]
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'entity' => 'merchant',
                'linked_account_kyc' => true,
                'category' => '1111',
                'website' => 'https://www.example.com',
                'transaction_report_email'  => [
                    'test@razorpay.com'
                ],
                'amount_credits_threshold'    => null
            ]
        ]
    ],

    'testEditMerchantWithNullRefundCreditsThreshold' => [
        'request' => [
            'raw' => json_encode([
                'linked_account_kyc' => '1',
                'website' => 'https://www.example.com',
                'category' => 1111,
                'transaction_report_email'  => [
                    'test@razorpay.com'
                ],
                'refund_credits_threshold'     => null
            ]),
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'CONTENT_TYPE'  => 'application/json',
                'HTTP_X-Dashboard' => 'true',
            ]
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'entity' => 'merchant',
                'linked_account_kyc' => true,
                'category' => '1111',
                'website' => 'https://www.example.com',
                'transaction_report_email'  => [
                    'test@razorpay.com'
                ],
                'refund_credits_threshold'    => null
            ]
        ]
    ],

    'testEditMerchantEnableInternationalFail' => [
        'request' => [
            'content' => [
                'international' => '1',
            ],
            'url' => '/merchants/10000000000000',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    // 'description' => PublicErrorDescription::BAD_REQUEST_KEY_OF_DEMO_ACCOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditTransactionEmailWithCsv' => [
        'request' => [
            'content' => [
                'transaction_report_email'  => [
                    'test@razorpay.com',
                    'test2@razorpay.com'
                ]
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-Admin-Username' => 'admin',
                'HTTP_X-Dashboard-User-Email'     => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'transaction_report_email'  => [
                    'test@razorpay.com',
                    'test2@razorpay.com'
                ]
            ]
        ]
    ],

    'testEditTransactionEmailWithError' => [
        'request' => [
            'content' => [
                'transaction_report_email'  => [
                    'test@razorpay.com',
                    'test2razorpay.com'
                ]
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The provided transaction report email is invalid: test2razorpay.com',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantWhitelistedIpsLive' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Forwarded-For' => '1.1.1.1',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 0,
                'items' => []
            ]
        ]
    ],

    'testMerchantFailedWhitelistedIpsLive' => [
        'request'   => [
            'url'    => '/payments',
            'method' => 'get',
            'server' => [
                'HTTP_X-Forwarded-For' => '4.3.2.1',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access Denied',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testMerchantWhitelistedIpsTest' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Forwarded-For' => '1.1.1.1',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 0,
                'items' => []
            ]
        ]
    ],

    'testMerchantFailedWhitelistedIpsTest' => [
        'request'   => [
            'url'    => '/payments',
            'method' => 'get',
            'server' => [
                'HTTP_X-Forwarded-For' => '4.3.2.1',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access Denied',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testEditMerchantWhitelistedIpsLive' => [
        'request'  => [
            'content' => [
                'whitelisted_ips_live' => [
                    '1.1.1.1',
                    '2.2.2.2'
                ],
            ],
            'url'     => '/merchants/1X4hRFHFx4UiXt',
            'method'  => 'put',
            'server'  => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'id'             => '1X4hRFHFx4UiXt',
                'entity'         => 'merchant',
                'whitelisted_ips_live' => [
                    '1.1.1.1',
                    '2.2.2.2'
                ],
            ]
        ]
    ],

    'testEditMerchantInvalidWhitelistedIpsLive' => [
        'request'   => [
            'content' => [
                'whitelisted_ips_live' => [
                    'abc.def.ghi.ekl',
                    '1.1.1.1'
                ],
            ],
            'url'     => '/merchants/1X4hRFHFx4UiXt',
            'method'  => 'put',
            'server'  => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'One or more IPs in the input are invalid',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditMerchantWhitelistedIpsTest' => [
        'request'  => [
            'content' => [
                'whitelisted_ips_test' => [
                    '1.1.1.1',
                    '2.2.2.2'
                ],
            ],
            'url'     => '/merchants/1X4hRFHFx4UiXt',
            'method'  => 'put',
            'server'  => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'id'             => '1X4hRFHFx4UiXt',
                'entity'         => 'merchant',
                'whitelisted_ips_test' => [
                    '1.1.1.1',
                    '2.2.2.2'
                ],
            ]
        ]
    ],

    'testEditMerchantWebsite' => [
        'request'  => [
            'content' => [
                'website' => 'http://abc.com',
            ],
            'url'     => '/merchants/10000000000000',
            'method'  => 'put',
        ],
        'response' => [
            'content' => [
                'id'                  => '10000000000000',
                'entity'              => 'merchant',
                'website'             => 'http://abc.com',
            ]
        ]
    ],

    'testEditMerchantInvalidWhitelistedIpsTest' => [
        'request'  => [
            'content' => [
                'whitelisted_ips_test' => [
                    'abc.def.ghi.ekl',
                    '1.1.1.1'
                ],
            ],
            'url'     => '/merchants/1X4hRFHFx4UiXt',
            'method'  => 'put',
            'server'  => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'One or more IPs in the input are invalid',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantWhitelistedIpsMode' => [
        'request'   => [
            'url'    => '/payments',
            'method' => 'get',
            'server' => [
                'HTTP_X-Forwarded-For' => '4.3.2.1',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access Denied',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testCorrectMerchantOwnerForBanking' => [
        'request'   => [
            'method'  => 'PUT',
            'content' => [],
        ],
        'response'  => [
            'content'     => [

            ],
            'status_code' => 200,
        ],
    ],

    'testCorrectMerchantOwnerForBankingWithSameOwner' => [
        'request'   => [
            'method'  => 'PUT',
            'content' => [],
        ],
        'response'  => [
            'content'     => [

            ],
            'status_code' => 200,
        ],
    ],

    'testNeedClarificationOnWorkflow' => [
        'request'   => [
            'url'     => '/merchant/{workflowId}/need_clarification',
            'method'  => 'PUT',
            'content' => [
                'body'    => 'needs clarification body',
                'subject' => 'needs clarification subject',
            ],
        ],
        'response'  => [
            'content'     => [
                'added_comment' => ['comment' =>  'need_clarification_comment : needs clarification body' ],
                'added_tag'     => 'awaiting-customer-response',
            ],
            'status_code' => 200,
        ],
    ],

    'testCorrectMerchantOwnerForBankingWherePrimaryOwnerHasAdminRole' => [
        'request'   => [
            'method'  => 'PUT',
            'content' => [],
        ],
        'response'  => [
            'content'     => [

            ],
            'status_code' => 200,
        ],
    ],

    'testEditMerchantEmailWithUpdateObserverData' => [
        'request' => [
            'content' => [
                'email' => 'shake@razorpay.com'
            ],
            'url' => '/merchants/10000000000044/email',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'workflow' => [
                    'name' => "Edit Email",
                ],
            ]
        ]
    ],

    'testEditMerchantEmail' => [
        'request' => [
            'content' => [
                'email' => 'shake@razorpay.com',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt/email',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'email' => 'shake@razorpay.com'
            ]
        ]
    ],

    'testEditMerchantEmailWhenOwnerExistsOnBothPgAndX' => [
        'request' => [
            'content' => [
                'email' => 'shake@razorpay.com',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt/email',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'email' => 'shake@razorpay.com'
            ]
        ]
    ],

    'testEditMerchantEmailUserExists' => [
        'request' => [
            'content' => [
                'email' => 'newemail@razorpay.com',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt/email',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'email' => 'newemail@razorpay.com'
            ]
        ]
    ],

    'testEditMerchantEmailUserExistsAndOwnerExistsOnBothPgAndX' => [
        'request' => [
            'content' => [
                'email' => 'newemail@razorpay.com',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt/email',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'email' => 'newemail@razorpay.com'
            ]
        ]
    ],

    'testUfhSignedUrlAccessValidationForSupportRoleFail' => [
        'request' => [
            'url' => '/ufh/file/file_DM6dXJfU4WzeAF/get-signed-url',
            'method' => 'get',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FORBIDDEN,
                ],
            ],
            'status_code' => 403,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN,
        ],
    ],

    'testUfhSignedUrlAccessValidationForSupportRolePass' => [
        'request' => [
            'url' => '/ufh/file/file_DM6dXJfU4WzeAF/get-signed-url',
            'method' => 'get',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'id'         => 'file_DM6dXJfU4WzeAFb',
                'signed_url' => 'http:://random-url'
            ],
            'status_code' => 200,
        ]
    ],

    'testEditMerchantUppercaseEmail' => [
        'request' => [
            'content' => [
                'email' => 'UPPERCASE@Razorpay.com',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt/email',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'email' => 'uppercase@razorpay.com'
            ]
        ]
    ],

    'testEditMerchantEmptyEmail' => [
        'request' => [
            'content' => [
                'email' => '',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt/email',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The email field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditTestAccountMerchantEmail' => [
        'request' => [
            'content' => [
                'email' => 'testing@fail.com',
            ],
            'url' => '/merchants/10000000000000/email',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_OPERATION_NOT_ALLOWED_FOR_TEST_ACCOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_OPERATION_NOT_ALLOWED_FOR_TEST_ACCOUNT,
        ],
    ],

    'testMerchantRestricted2faEnable' => [
        'request' => [
            'url'     => '/merchants/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'second_factor_auth' => true,
            ],
        ],
    ],

    'testMerchant2faEnable' => [
        'request' => [
            'url'     => '/merchants/2fa',
            'method'  => 'PATCH',
            'content' => [
                'second_factor_auth' => true,
                'password'           => 'hello123',
            ],
        ],
        'response' => [
            'content' => [
                'second_factor_auth' => true,
            ],
        ],
    ],

    'testFailedMerchant2faEnableInvalidPass' => [
        'request' => [
            'url'     => '/merchants/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PASSWORD,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PASSWORD,
        ],
    ],

    'testMerchant2faEnableAsCriticalAction'     => [
        'request'       => [
            'url'       => '/merchants/2fa',
            'method'    => 'PATCH',
            'content'   => [
                'second_factor_auth'    => true,
            ],
            'server'    => [
                'HTTP_X-Dashboard-User-2FA-Verified'    => 'true',
            ]
        ],

        'response'      => [
            'content'       => [
                'second_factor_auth'    => true,
            ],
        ],
    ],

    'testMerchant2faDisableAsCriticalAction'     => [
        'request'       => [
            'url'       => '/merchants/2fa',
            'method'    => 'PATCH',
            'content'   => [
                'second_factor_auth'    => 0,
            ],
            'server'    => [
                'HTTP_X-Dashboard-User-2FA-Verified'    => 'true',
            ]
        ],

        'response'      => [
            'content'       => [
                'second_factor_auth'    => false,
            ],
        ],
    ],

    'testMerchant2faDisable' => [
        'request' => [
            'url'     => '/merchants/2fa',
            'method'  => 'PATCH',
            'content' => [
                'second_factor_auth' => 0,
                'password'           => 'hello123',
            ],
        ],
        'response' => [
            'content' => [
                'second_factor_auth' => false,
            ],
        ],
    ],

    'testFailedMerchantEnable2faMobNotPresent' => [
        'request' => [
            'url'     => '/merchants/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_OWNER_2FA_SETUP_MANDATORY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_OWNER_2FA_SETUP_MANDATORY,
        ],
    ],

    'testFailedMerchantEnable2faMobNotVerified' => [
        'request' => [
            'url'     => '/merchants/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_OWNER_2FA_SETUP_MANDATORY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_OWNER_2FA_SETUP_MANDATORY,
        ],
    ],

    'testFailedMerchantEnable2faNotOwner' => [
        'request' => [
            'url'     => '/merchants/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testFailedMerchantRestricted2faEnableUserMobNotVerified' => [
        'request' => [
            'url'     => '/merchants/2fa',
            'method'  => 'PATCH',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
        ],
    ],

    'testEditMerchantConfig' => [
        'request'  => [
            'content' => [
                'brand_color'         => '00bcd4',
                'handle'              => 'LOLO',
                'invoice_label_field' => 'business_name',
                'display_name'        => 'Display',
                'fee_bearer'          => 'customer',
            ],
            'url'     => '/account/config',
            'method'  => 'put',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id'                  => '10000000000000',
                'brand_color'         => '#00BCD4',
                'handle'              => 'LOLO',
                'invoice_label_field' => 'business_name',
                'display_name'        => 'Display',
                'fee_bearer'          => 'customer',
            ]
        ]
    ],

    'testEditMerchantConfigSellerAppRoleFail' => [
        'request'  => [
            'content' => [
                'display_name'        => 'Display',
            ],
            'url'     => '/account/config',
            'method'  => 'put',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
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
    ],

    'testEditMerchantInvalidBrandColor' => [
        'request' => [
            'content' => [
                'brand_color' => '#00bcd4',
            ],
            'url' => '/account/config',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditMerchantFeeCreditsThresholdWithProxyAuth' => [
        'request' => [
            'raw' => json_encode([
                'fee_credits_threshold'     => 1000
            ]),
            'url' => '/account/config',
            'method' => 'put',
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
                'HTTP_X-Dashboard' => 'true',
            ]
        ],
        'response' => [
            'content' => [
                'fee_credits_threshold'    => 1000
            ]
        ]
    ],

    'testEditMerchantAmountCreditsThresholdWithProxyAuth' => [
        'request' => [
            'raw' => json_encode([
                'amount_credits_threshold'     => 1000
            ]),
            'url' => '/account/config',
            'method' => 'put',
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
                'HTTP_X-Dashboard' => 'true',
            ]
        ],
        'response' => [
            'content' => [
                'amount_credits_threshold'    => 1000
            ]
        ]
    ],

    'testEditMerchantRefundCreditsThresholdWithProxyAuth' => [
        'request' => [
            'raw' => json_encode([
                'refund_credits_threshold'     => 1000
            ]),
            'url' => '/account/config',
            'method' => 'put',
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
                'HTTP_X-Dashboard' => 'true',
            ]
        ],
        'response' => [
            'content' => [
                'refund_credits_threshold'    => 1000
            ]
        ]
    ],

    'testEditMerchantInvalidInvoiceNameField' => [
        'request'   => [
            'content' => [
                'invoice_label_field' => 'random',
            ],
            'url'     => '/account/config',
            'method'  => 'put',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected invoice label field is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditMerchantInvalidAutoRefundDelay' => [
        'request' => [
            'content' => [
                'auto_refund_delay' => '40 days',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Auto refund delay should be between 1 and 10 days',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditMerchantInvalidDurationAutoRefundDelay' => [
        'request' => [
            'content' => [
                'auto_refund_delay' => '3 weeeks',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Auto refund delay should be in mins, hours or days',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditMerchantAutoRefundDelay' => [
        'request' => [
            'content' => [
                'auto_refund_delay' => '3 hours',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'auto_refund_delay' => 10800
            ],
            'status_code' => 200,
        ]
    ],

    'testEditMerchantDefaultRefundSpeed' => [
        'request' => [
            'content' => [
                'default_refund_speed' => 'optimum',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'default_refund_speed' => 'optimum'
            ],
            'status_code' => 200,
        ]
    ],

    'testGetBillingLabelSuggestions' => [
        'request'  => [
            'url'     => '/merchants/billing_label/suggestions',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                    'Test Name Private Limited Ltd Ltd. Liability Partnership',
                    'TEST NAME PRIVATE LIMITED LTD LTD. LIABILITY PARTNERSHIP',
                    'Test Name',
                    'https://shopify.secondleveldomain.edu.in',
                    'secondleveldomain',
                    'SECONDLEVELDOMAIN',
                    'Secondleveldomain',
                    'secondleveldomain.edu.in',
            ]
        ]
    ],

    'testGetBillingLabelSuggestionsWithoutWebsite' => [
        'request'  => [
            'url'     => '/merchants/billing_label/suggestions',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                    'Test Name Liability Company Pvt Pvt. Llp Llp. Llc Llc.',
                    'TEST NAME LIABILITY COMPANY PVT PVT. LLP LLP. LLC LLC.',
                    'Test Name',
            ]
        ]
    ],

    'testGetBillingLabelSuggestionsWebsiteUrlWithSubdomain' => [
        'request'  => [
            'url'     => '/merchants/billing_label/suggestions',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'http://a.amazon.mywebsite.com',
                'mywebsite',
                'MYWEBSITE',
                'Mywebsite',
                'mywebsite.com',
            ]
        ]
    ],

    'testGetBillingLabelSuggestionsWebsiteNotInFormat' => [
        'request'  => [
            'url'     => '/merchants/billing_label/suggestions',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                    'Test Private Limited Name',
                    'TEST PRIVATE LIMITED NAME',
                    'Test Name',
            ]
        ]
    ],

    'testBillingLabelUpdateInSuggestions' => [
        'request'  => [
            'content' => [
                'billing_label' => 'test.org',
            ],
            'url'     => '/merchants/billing_label/update',
            'method'  => 'patch',
        ],
        'response' => [
            'content' => [
                'id' => '10000000000000',
                'billing_label' => 'test.org',
                'business_dba' => 'test.org',
            ]
        ]
    ],

    'testBillingLabelUpdateMatchesWithWebsiteNotHavingPath' => [
        'request'  => [
            'content' => [
                'billing_label' => 'Tests',
            ],
            'url'     => '/merchants/billing_label/update',
            'method'  => 'patch',
        ],
        'response' => [
            'content' => [
                'id' => '10000000000000',
                'billing_label' => 'Tests',
                'business_dba' => 'Tests',
            ]
        ]
    ],

    'testBillingLabelUpdateMatchesWithWebsiteHavingPath' => [
        'request'  => [
            'content' => [
                'billing_label' => 'Tests',
            ],
            'url'     => '/merchants/billing_label/update',
            'method'  => 'patch',
        ],
        'response' => [
            'content' => [
                'id'            => '10000000000000',
                'billing_label' => 'Tests',
                'business_dba'  => 'Tests',
            ]
        ]
    ],

    'testBillingLabelUpdateMatchesWithWebsiteHavePlayStoreLinkFail' => [
        'request' => [
            'content' => [
                'billing_label' => 'google',
            ],
            'url'    => '/merchants/billing_label/update',
            'method' => 'patch',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid value, the brand name must be similar to business name or website name. website: https://play.google.com/store/apps/details?id=com.beseller.apps, business name: abc test pvt ltd'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testBillingLabelUpdateMatchesWithBusinessName' => [
        'request'  => [
            'content' => [
                'billing_label'         => 'liability company make trip my',
            ],
            'url'     => '/merchants/billing_label/update',
            'method'  => 'patch',
        ],
        'response' => [
            'content' => [
                'id' => '10000000000000',
                'billing_label' => 'liability company make trip my',
                'business_dba'  => 'liability company make trip my',
            ]
        ]
    ],

    'testBillingLabelUpdateInvalidValue' => [
        'request' => [
            'content' => [
                'billing_label' => 'Random Value',
            ],
            'url' => '/merchants/billing_label/update',
            'method' => 'patch',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid value, the brand name must be similar to business name or website name. website: https://www.test.com, business name: Test Name Private Limited'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testStoreImageAndGetLogoUrl' => [
        'request' => [
            'content' => [],
            'url' => '/account/config/logo',
            'method' => 'post',
            'files' => [

            ],
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '10000000000000',
            ]
        ]
    ],

    'testGetGstin' => [
        'request'  => [
            'url'    => '/merchant/gst',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'gstin'   => '29AAGCR4375J1ZU',
                'p_gstin' => null
            ],
        ],
    ],

    'testEditGstin' => [
        'request'  => [
            'url'     => '/merchant/gst',
            'method'  => 'PATCH',
            'content' => [
                'gstin'   => '29AAGCR4375J1ZP',
                'p_gstin' => '29AAGCR4375J1ZU'
            ],
        ],
        'response' => [
            'content' => [
                'gstin'   => '29AAGCR4375J1ZP',
                'p_gstin' => '29AAGCR4375J1ZU'
            ],
        ],
    ],

    'testGetMerchantDataForSegment' => [
        'request'  => [
            'url'     => '/merchant/data_for_segment',
            'method'  => 'get',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'user_business_category'          => 'ecommerce',
                'activation_status'               => 'activated',
                'mcc'                             => '5399',
                'activated_at'                    => 1614921159,
                'user_role'                       => 'owner',
                'first_transaction_timestamp'     => null,
                'user_days_till_last_transaction' => 30,
                'merchant_lifetime_gmv'           => 100,
                'average_monthly_gmv'             => 10,
                'primary_product_used'            => 'payment_links',
                'ppc'                             => 1,
                'mtu'                             => true,
                'average_monthly_transactions'    => 3,
                'pg_only'                         => false,
                'pl_only'                         => true,
                'pp_only'                         => false
            ]
        ]
    ],

    'testEditGstinInvalidRole' => [
        'request'  => [
            'url'     => '/merchant/gst',
            'method'  => 'PATCH',
            'content' => [
                'gstin'   => '29AAGCR4375J1ZP',
                'p_gstin' => '29AAGCR4375J1ZU'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testDeleteLogoUrl' => [
        'request' => [
            'content' => [],
            'url' => '/account/config/logo',
            'method' => 'delete',
            'files' => [

            ],
        ],
        'response' => [
            'content' => [
                'id' => '10000000000000',
            ]
        ]
    ],

    'testEditMerchantConfigWithEmail' => [
        'request' => [
            'content' => [
                'transaction_report_email' => [
                    'nemo@razorpay.com',
                    'hello@razorpay.com'
                ]
            ],
            'url' => '/account/config',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '10000000000000',
                'transaction_report_email'  => [
                    'nemo@razorpay.com',
                    'hello@razorpay.com'
                ]
            ]
        ]
    ],

    'testEditMerchantConfigWithDefaultRefundSpeed' => [
        'request' => [
            'content' => [
                'default_refund_speed' => 'optimum',
            ],
            'url' => '/account/config',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '10000000000000',
                'default_refund_speed' =>'optimum',
            ]
        ]
    ],

    'testAttemptPaymentOnNonLiveMerchant' => [
        'request' => [
            'content' => [
                'amount' => '500',
                'currency' => 'INR',
                'email' => 'a@b.com',
                'contact' => '8383883838',
                'method' => 'netbanking',
                'bank' => 'HDFC',
            ],
            'url' => '/payments',
            'method' => 'post',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED,
        ],
    ],

    'testMerchantUpdateKeyAccess' => [
        'request' => [
            'content' => [
                'has_key_access' => true,
            ],
            'url'     => '/merchants/%s/update_key_access',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'has_key_access' => true,
            ],
        ],
    ],

    'testMerchantEnableLive' => [
        'request' => [
            'content' => [],
            'url' => '/merchants/1cXSLlUU8V9sXl/live/enable',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity' => 'merchant',
                'activated' => true,
                'live' => true,
            ]
        ]
    ],

    'testMerchantDisableLive' => [
        'request' => [
            'content' => [],
            'url' => '/merchants/1cXSLlUU8V9sXl/live/disable',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity' => 'merchant',
                'activated' => true,
                'live' => false,
            ]
        ]
    ],

    'testAddBankAccountWithInvalidBeneficiaryNameInjection' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => '=HYPERLINK("http://maps.google.com/maps?q="&B3,"View on Google Map")',
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
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The beneficiary name format is invalid.',
                    'field'         => 'beneficiary_name',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddBankAccountWithInvalidBeneficiaryName' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => '*#Test Merchant',
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
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The beneficiary name format is invalid.',
                    'field'         => 'beneficiary_name',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddBankAccount' => [
        'request' => [
            'content' => [
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
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'ICIC0001206',
                'account_number' => '0002020000304030434',
                'beneficiary_name' => 'Test R4zorpay:',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_city' => 'Kolkata',
                'beneficiary_state' => 'WB',
                'beneficiary_country' => 'IN',
                'beneficiary_pin' => '123456',
                'beneficiary_email' => 'random@email.com',
                'beneficiary_mobile' => '9988776655',
            ]
        ]
    ],

    'testUpdateBankAccountWithAddressProof' => [
        'request'  => [
            'content' => [
                'ifsc_code'        => 'ICIC0001206',
                'account_number'   => '0002020000304030434',
                'beneficiary_name' => 'Test R4zorpay:',
            ],
            'url'     => '/merchants/bank_account',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id'      => '10000000000000',
                'ifsc_code'        => 'ICIC0001206',
                'account_number'   => '0002020000304030434',
                'beneficiary_name' => 'Test R4zorpay:',
            ]
        ]
    ],

    'testUpdateBankAccountLavbShouldFail' => [
        'request'  => [
            'content' => [
                'ifsc_code'        => 'LAVB0000499',
                'account_number'   => '0000009999999999999',
                'beneficiary_name' => 'Test R4zorpay:',
            ],
            'url'     => '/merchants/bank_account/update',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => "We are unable to complete this operation due to the restrictions on Laxmi Vilas Bank's operations by RBI (Gazette notification (S.O. 4127(E)) dated 17th November 2020",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'internal_error_code' => 'BAD_REQUEST_MERCHANT_BANK_ACCOUNT_UPDATE_LAXMI_VILAS_BANK_PROHIBITED',
            'class'               => BadRequestException::class,
        ]
    ],

    'testUpdateBankAccountViaPennyTesting' => [
        'request'  => [
            'content' => [
                'ifsc_code'        => 'ICIC0001206',
                'account_number'   => '0000009999999999999',
                'beneficiary_name' => 'Test R4zorpay:',
            ],
            'url'     => '/merchants/bank_account/update',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'ifsc_code'        => 'ICIC0001206',
                'account_number'   => '0000009999999999999',
                'beneficiary_name' => 'Test R4zorpay:',
            ]
        ]
    ],

    'testUpdateBankAccountViaPennyTestingWithoutExistingBankAccountFail' => [
        'request'  => [
            'content' => [
                'ifsc_code'        => 'ICIC0001206',
                'account_number'   => '0000009999999999999',
                'beneficiary_name' => 'Test R4zorpay:',
            ],
            'url'     => '/merchants/bank_account/update',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => 'The merchant has not yet provided his bank account details',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'internal_error_code' => 'BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND',
            'class'               => BadRequestException::class,
        ]
    ],

    'testUpdateBankAccountViaPennyTestingFundsOnHoldFail' => [
        'request'  => [
            'content' => [
                'ifsc_code'        => 'ICIC0001206',
                'account_number'   => '0000009999999999999',
                'beneficiary_name' => 'Test R4zorpay:',
            ],
            'url'     => '/merchants/bank_account/update',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => 'Bank account can not be updated due to funds are on hold',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'internal_error_code' => 'BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD',
            'class'               => BadRequestException::class,
        ]
    ],

    'testUpdateBankAccountViaPennyTestingAlreadyInProgressFail' => [
        'request'  => [
            'content' => [
                'ifsc_code'        => 'ICIC0001206',
                'account_number'   => '0000009999999999999',
                'beneficiary_name' => 'Test R4zorpay:',
            ],
            'url'     => '/merchants/bank_account/update',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => 'A previous request to update your bank account is in progress. Please try after some time',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'internal_error_code' => 'BAD_REQUEST_MERCHANT_BANK_ACCOUNT_UPDATE_IN_PROGRESS',
            'class'               => BadRequestException::class,
        ]
    ],


    'testAddBankAccountWithMerchantIdInURL' => [
        'request' => [
            'content' => [
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
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'ICIC0001206',
                'account_number' => '0002020000304030434',
                'beneficiary_name' => 'Test R4zorpay:',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_city' => 'Kolkata',
                'beneficiary_state' => 'WB',
                'beneficiary_country' => 'IN',
                'beneficiary_pin' => '123456',
                'beneficiary_email' => 'random@email.com',
                'beneficiary_mobile' => '9988776655',
            ]
        ]
    ],

    'testAddBankAccountWithAccountType' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'account_type'          => 'savings',
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
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id'           => '10000000000000',
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'account_type'          => 'savings',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
            ]
        ]
    ],

    'testAddBankAccountWithInvalidAccountType' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'account_type'          => 'special',
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
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'Invalid Account type',
                    'field'         => 'account_type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddBankAccountWithInvalidAccountNumber' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '31260200000646',
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
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_BANK_ACCOUNT,
            'description'         => 'The bank account entered is invalid',
        ],
    ],


    'testAddBankAccountWithMerchantDetail' => [
        'request' => [
            'content' => [
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
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'ICIC0001206',
                'account_number' => '0002020000304030434',
                'beneficiary_name' => 'Test R4zorpay:',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_city' => 'Kolkata',
                'beneficiary_state' => 'WB',
                'beneficiary_country' => 'IN',
                'beneficiary_pin' => '123456',
                'beneficiary_email' => 'random@email.com',
                'beneficiary_mobile' => '9988776655',
            ]
        ]
    ],

    'testChangeBankAccountWithZeroes' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '2020000304030434',
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
            ],
            'url' => '/merchants/10000000000000/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'ICIC0001206',
                'account_number' => '2020000304030434',
                'beneficiary_name' => 'Test R4zorpay:',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_city' => 'Kolkata',
                'beneficiary_state' => 'WB',
                'beneficiary_country' => 'IN',
                'beneficiary_pin' => '123456',
                'beneficiary_email' => 'random@email.com',
                'beneficiary_mobile' => '9988776655',
            ]
        ]
    ],

    'testAddBankAccountWithInvalidIfsc' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'IIC0001206',
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
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
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
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetBankAccount' => [
        'request' => [
            'url' => '/merchants/10000000000000/bank_account',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'ICIC0001206',
                'account_number' => '0002020000304030434',
                'beneficiary_name' => 'Test R4zorpay:',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_address4' => 'address 4',
                'beneficiary_email' => 'random@email.com',
                'beneficiary_mobile' => '9988776655',
            ]
        ]
    ],

    'testChangeBankAccount' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020005304612497',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => '4ddr3ss 1',
                'beneficiary_address2'  => '4ddr3ss 2',
                'beneficiary_address3'  => '4ddr3ss 3',
                'beneficiary_address4'  => '4ddr3ss 4',
                'beneficiary_email'     => 'r4nd0m@email.com',
                'beneficiary_mobile'    => '9876543210',
                'beneficiary_city'      => 'Mumbai',
                'beneficiary_state'     => 'MH',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '567890',
            ],
            'url' => '/merchants/10000000000000/bank_account',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'ICIC0001206',
                'account_number' => '0002020005304612497',
                'beneficiary_name' => 'Test R4zorpay:',
                'beneficiary_address1' => '4ddr3ss 1',
                'beneficiary_address2' => '4ddr3ss 2',
                'beneficiary_address3' => '4ddr3ss 3',
                'beneficiary_city' => 'Mumbai',
                'beneficiary_state' => 'MH',
                'beneficiary_country' => 'IN',
                'beneficiary_pin' => '567890',
                'beneficiary_email' => 'r4nd0m@email.com',
                'beneficiary_mobile' => '9876543210',
            ]
        ]
    ],

    'testSetBanks' => [
        'request' => [
            'url' => '/merchants/10000000000000/banks',
            'method' => 'POST',
            'content' => [
                'banks' => [
                    'HDFC',
                    'ICIC'
                ]
            ]
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'HDFC' => 'HDFC Bank',
                    'ICIC' => 'ICICI Bank',
                ],
                'disabled' => [],
            ],
        ]
    ],

    'testSetEmptyBanks' => [
        'request' => [
            'url' => '/merchants/10000000000000/banks',
            'method' => 'POST',
            'content' => [
                'banks' => []
            ]
        ],
        'response' => [
            'content' => [
                'enabled' => [],
                'disabled' => [
                    'HDFC' => 'HDFC Bank',
                    'ICIC' => 'ICICI Bank',
                ],
            ],
        ]
    ],

    'testGetBanksByMerchantAuth' => [
        'request' => [
            'url' => '/banks',
            'method' => 'GET',
            'content' => [
                'callback' => 'abcdef',
                '_' => 'abcdef',
            ]
        ],
        'response' => [
            'content' => [
                'HDFC' => 'HDFC Bank',
                'ICIC' => 'ICICI Bank',
            ],
        ],
        'jsonp' => true
    ],

    'testGetBanksByAdminAuth' => [
        'request' => [
            'url' => '/merchants/10000000000000/banks',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'HDFC' => 'HDFC Bank',
                    'ICIC' => 'ICICI Bank',
                ],
                'disabled' => [
                    'YESB' => 'Yes Bank',
                ]
            ],
        ],
    ],

    'testGetPaymentMethodsRoute' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'netbanking' => [
                    'UTIB' => 'Axis Bank',
//                    'BARB' => 'Bank of Baroda',
                    'YESB' => 'Yes Bank',
                ],
                'wallet' => [
                    'paytm' => true,
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithNetbankingDisabled' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutPreferencesAmexRecurring' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
            ]
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'recurring' => [
                        'card' => [
                            'credit' => [
                                'MasterCard',
                                'Visa',
                                'American Express',
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],

    'testGetCheckoutPreferencesWithPartnerLogo' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
            ]
        ],
        'response' => [
            'content' => [
                'options' => [
                    'partnership_logo' => 'https://cdn.razorpay.com/logos/lalalala.png'
                ]
            ],
        ],
    ],

    'testGetCheckoutPreferencesForMerchantDisabledBanks' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutPreferencesForTpvEnabledMerchant' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutPreferencesForCredSubtext' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutPreferencesForMagicEnabledMerchant' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
            ]
        ],
        'response' => [
            'content' => [
                'magic' => true,
            ],
        ],
    ],

    'testGetCheckoutPreferencesForSaveVpaEnabledMerchant' => [
        'request'   => [
            'url'    => '/preferences',
            'method' => 'get',
            'content'   => [
                'currency' => 'INR',
            ]
        ],
        'response' => [
            'content' => [
                'features' => [
                    'save_vpa' => true
                ]
            ]
        ]
    ],

    'testGetCheckoutPreferencesForMagicDisabledMerchant' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'mode'  => 'test',
                'magic' => false,
            ],
        ],
    ],

    'testGetCheckoutPreferencesAfterFilterForMinimumAmount' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
                'amount'   => '20000'
            ]
        ],
        'response' => [
            'content' => [
                'mode'  => 'test',
                'methods' =>[
                    'paylater' => [
                        'icic' => true,
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesAfterFilterForMinimumAmountOnCardlessEmi' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
                'amount'   => '9899'
            ]
        ],
        'response' => [
            'content' => [
                'mode'  => 'test'
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithAmountGreaterForCardlessEmi' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
                'amount'   => '90001'
            ]
        ],
        'response' => [
            'content' => [
                'mode'  => 'test',
                'methods' =>[
                    'cardless_emi' => [
                        'zestmoney' => true,
                        'walnut369' => true,
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesAfterFilterForMinimumAmountOnHomeCreditCardlessEmi' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
                'amount'   => '49900'
            ]
        ],
        'response' => [
            'content' => [
                'mode'  => 'test'
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithAmountGreaterForHomeCreditCardlessEmi' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
                'amount'   => '50000'
            ]
        ],
        'response' => [
            'content' => [
                'mode'  => 'test',
                'methods' =>[
                    'cardless_emi' => [
                        'zestmoney' => true,
                        'hcin' => true,
                    ],
                ],
            ],
        ],
    ],

    'testPreferencesAfterFilterForMinimumAmountWithOrderAmountLess' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
                'amount'   => '200000'
            ]
        ],
        'response' => [
            'content' => [
                'mode'  => 'test',
                'methods' =>[
                    'paylater' => [
                        'icic' => true,
                    ],
                ],
            ],
        ],
    ],

    'testPreferencesAfterFilterForMinimumAmountWithOrderAmountGreater' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
                'amount'   => '100'
            ]
        ],
        'response' => [
            'content' => [
                'mode'  => 'test',
                'methods' =>[
                    'paylater' => [
                        'icic' => true,
                        'hdfc' => true,

                    ],
                ],
            ],
        ],
    ],

    'testPreferencesAfterFilterForMinimumAmountWithoutOrderOrAmount' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
            ]
        ],
        'response' => [
            'content' => [
                'mode'  => 'test',
                'methods' =>[
                    'paylater' => [
                        'icic' => true,
                        'hdfc' => true,

                    ],
                ],
            ],
        ],
    ],

    'testPreferenceforTpvMerchantWithOrder' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
                'amount'   => '100'
            ]
        ],
        'response' => [
            'content' => [
                'mode'  => 'test',
                'methods' =>[
                    'netbanking' => [
                    'ICIC' => "ICICI Bank"
        ],
        'upi' => false
                ],
            ],
        ],
    ],

    'testPreferenceforForcedOfferWithMethod' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
                'amount'   => '100'
            ]
        ],
        'response' => [
            'content' => [
                'mode'  => 'test',
                'methods' =>[
                    'entity' => "methods",
                    'wallet' =>  [
                        'phonepe' => true,
                    ],
                    'emi_options' => [],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithAmountGreater' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
                'amount'   => '200001'
            ]
        ],
        'response' => [
            'content' => [
                'mode'  => 'test',
                'methods' =>[
                    'paylater' => [
                        'icic' => true,
                        'hdfc' => true,
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithNonOrderRelatedOffer' => [
        'request' => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
            ]
        ],
        'response' => [
            'content' => [
                'offers' => [
                    [
                        'name'            => 'Test Offer',
                        'payment_method'  => 'wallet',
                        'issuer'          => 'olamoney',
                        'display_text'    => 'Some display text',
                    ]
                ]
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithContactDetailsWhereContactDoesNotExist' => [
        'request' => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'contact_id' => 'cont_AAAAAAAAAAAAAA'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],


    'testGetCheckoutPreferencesForPaidOrder' => [
        'request' => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
            ]
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID
        ],
    ],

    'testGetCheckoutPreferencesForCancelledInvoice' => [
        'request' => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'invoice_id' => 'inv_1000000invoice',
                'currency' => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment Link is not payable in cancelled status.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetCheckoutPreferencesForExpiredInvoice' => [
        'request' => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'invoice_id' => 'inv_1000000invoice',
                'currency' => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment Link is not payable in expired status.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetCheckoutPreferencesWithSharedMerchantOffer' => [
        'request' => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'offers' => [
                    [
                        'name'            => 'Test Offer',
                        'payment_method'  => 'wallet',
                        'issuer'          => 'olamoney',
                        'display_text'    => 'Merchant specific offer',
                    ]
                ]
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithFreechargeOfferOnMerchantWithDirectFreechargeTerminal' => [
        'request' => [
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'offers' => [
                    [
                        'name'            => 'Test Offer',
                        'payment_method'  => 'wallet',
                        'issuer'          => 'olamoney',
                        'display_text'    => 'Shared olamoney offer',
                    ]
                ]
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithMultipleOrderOffers' => [
        'request' => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'order_id' => null,
                'currency' => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'offers' => [
                    [
                        'name' => 'Test Offer',
                        'payment_method' => 'card',
                        'payment_network' => 'VISA',
                        'issuer' => 'HDFC',
                    ],
                    [
                        'name' => 'Test Offer',
                        'payment_method' => 'card',
                        'payment_network' => 'VISA',
                        'issuer' => 'HDFC',
                    ]
                ]
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithOrderRelatedUndiscountedOffer' => [
        'request' => [
            'url'    => null,
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'tests' => [
            [
                'offer' => [
                    'payment_method'      => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network'     => 'VISA',
                    'issuer'              => 'HDFC',
                    'iins'                => ['123456'],
                    'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                    'display_text'        => 'Some display text',
                    'terms'               => 'Some terms',
                ],
                'response' => [
                    'content' => [
                        'methods' => [
                            'entity' => 'methods',
                            'card'   => true
                        ],
                        'offers' => [
                            [
                                'name'            => 'Test Offer',
                                'payment_method'  => 'card',
                                'payment_network' => 'VISA',
                                'display_text'    => 'Some display text',
                            ]
                        ]
                    ]
                ]
            ],
            [
                'offer' => [
                    'name'                => 'Amex offer',
                    'payment_method'      => 'card',
                    'payment_network'     => 'AMEX',
                    'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                    'display_text'        => 'Some display text',
                    'terms'               => 'Some terms',
                ],
                'response' => [
                    'content' => [
                        'methods' => [
                            'entity' => 'methods',
                            'card'   => true,
                            'amex'   => true,
                        ],
                        'offers' => [
                            [
                                'name'            => 'Amex offer',
                                'payment_method'  => 'card',
                                'payment_network' => 'AMEX',
                                'display_text'    => 'Some display text',
                            ]
                        ]
                    ]
                ]
            ],
            [
                'offer' => [
                    'payment_method'      => 'netbanking',
                    'payment_network'     => 'HDFC',
                    'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                    'display_text'        => 'Some display text',
                    'terms'               => 'Some terms',
                ],
                'response' => [
                    'content' => [
                        'methods' => [
                            'entity'     => 'methods',
                            'netbanking' => [
                                'HDFC' => 'HDFC Bank',
                            ]
                        ],
                        'offers' => [
                            [
                                'name'            => 'Test Offer',
                                'payment_method'  => 'netbanking',
                                'payment_network' => 'HDFC',
                                'display_text'    => 'Some display text',
                            ]
                        ]
                    ]
                ]
            ],
            [
                'offer' => [
                    'issuer'              => 'HDFC',
                    'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                    'display_text'        => 'Some display text',
                    'terms'               => 'Some terms',
                ],
                'response' => [
                    'content' => [
                        'offers' => [
                            [
                                'name'            => 'Test Offer',
                                'issuer'          => 'HDFC',
                                'display_text'    => 'Some display text',
                            ]
                        ]
                    ]
                ]
            ],
            [
                'offer' => [
                    'payment_method'      => 'wallet',
                    'issuer'              => 'airtelmoney',
                    'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                    'display_text'        => 'Some display text',
                    'terms'               => 'Some terms',
                ],
                'response' => [
                    'content' => [
                        'methods' => [
                            'entity'     => 'methods',
                            'wallet' => [
                                'airtelmoney' => true,
                            ]
                        ],
                        'offers' => [
                            [
                                'name'            => 'Test Offer',
                                'payment_method'  => 'wallet',
                                'issuer'          => 'airtelmoney',
                                'display_text'    => 'Some display text',
                            ]
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithOrderRelatedOffer' => [
        'request' => [
            'url'    => null,
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'tests' => [
            [
                'offer' => [
                    'payment_method'      => 'card',
                    'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                    'display_text'        => 'Some display text',
                    'percent_rate'        => 1000,
                    'terms'               => 'Some terms',
                ],
                'response' => [
                    'content' => [
                        'methods' => [
                            'entity' => 'methods',
                            'card'   => true
                        ],
                        'offers' => [
                            [
                                'name'            => 'Test Offer',
                                'payment_method'  => 'card',
                                'original_amount' => 100000,
                                'amount'          => 90000,
                            ]
                        ],
                    ]
                ]
            ],
            [
                'offer' => [
                    'payment_method'      => 'card',
                    'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                    'display_text'        => 'Some display text',
                    'flat_cashback'       => 100,
                    'terms'               => 'Some terms',
                ],
                'response' => [
                    'content' => [
                        'methods' => [
                            'entity' => 'methods',
                            'card'   => true
                        ],
                        'offers' => [
                            [
                                'name'            => 'Test Offer',
                                'payment_method'  => 'card',
                                'original_amount' => 100000,
                                'amount'          => 99900,
                            ]
                        ],
                    ]
                ]
            ],
            [
                'offer' => [
                    'payment_method'      => 'card',
                    'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                    'display_text'        => 'Some display text',
                    'percent_rate'        => 5000,
                    'max_cashback'        => 2000,
                    'terms'               => 'Some terms',
                ],
                'response' => [
                    'content' => [
                        'methods' => [
                            'entity' => 'methods',
                            'card'   => true
                        ],
                        'offers' => [
                            [
                                'name'            => 'Test Offer',
                                'payment_method'  => 'card',
                                'original_amount' => 100000,
                                'amount'          => 98000,
                            ]
                        ],
                    ]
                ]
            ],
            [
                'offer' => [
                    'payment_method'      => 'card',
                    'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                    'display_text'        => 'Some display text',
                    'percent_rate'        => 5000,
                    'min_amount'          => 2000,
                    'terms'               => 'Some terms',
                ],
                'response' => [
                    'content' => [
                        'methods' => [
                            'entity' => 'methods',
                            'card'   => true
                        ],
                        'offers' => [
                            [
                                'name'            => 'Test Offer',
                                'payment_method'  => 'card',
                                'original_amount' => 100000,
                                'amount'          => 50000,
                            ]
                        ],
                    ]
                ]
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithEmiOffer' => [
        'request' => [
            'url'    => null,
            'method' => 'GET',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity'         => 'methods',
                    'card'           => true,
                    'credit_card'    => true,
                    'debit_card'     => true,
                    'emi'            => true,
                    'emi_plans'      => [],
                    'emi_options'    => [],
                    'emi_subvention' => 'customer'
                    ],
                'offers' => [
                    [
                        'name'            => 'Test Offer',
                        'payment_method'  => 'emi',
                        'display_text'    => 'Some display text',
                        'original_amount' => 300000,
                        'amount'          => 150000,
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithAllCardGeatewayDowntime' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'card' => [
                        [
                            'issuer'    => ['ALL'],
                            'scheduled' => true,
                            'severity'  => 'low',
                            'card_type' => 'credit',
                            'network'   => ['VISA'],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithDebitCardDisabled' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'debit_card' => false,
                    'credit_card' => true,
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithCreditCardDisabled' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
            ]
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'entity' => 'methods',
                    'card' => true,
                    'debit_card' => true,
                    'credit_card' => false,
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoForDirectNetbankingGateway' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer'    => 'HDFC'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoWithSharedNetbankingGateway' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 27,
                'items' => [
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BACB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BBKM',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'COSB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'ESAF',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'HSBC',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'JSBP',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'KCCB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'KJSB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'MSNU',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'NESF',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'NKGS',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'ORBC',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SURY',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'TBSB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'TJSB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'TNSC',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'VARA',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'ZCBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'ANDB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BARB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'DLXB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'IBKL_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'LAVB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'PUNB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'RATN_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SVCB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'YESB_C',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoWithBothSharedAndDirectGateway' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 28,
                'items' => [
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'HDFC',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BACB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BBKM',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'COSB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'ESAF',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'HSBC',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'JSBP',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'KCCB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'KJSB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'MSNU',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'NESF',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'NKGS',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'ORBC',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SURY',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'TBSB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'TJSB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'TNSC',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'VARA',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'ZCBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'ANDB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BARB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'DLXB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'IBKL_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'LAVB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'PUNB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'RATN_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SVCB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'YESB_C',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeWithNoBanksExclusiveToGateway' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 0,
                'items' => [],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoWithIssuerExclusiveToGateway' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 0,
                'items' => [

                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoWithIssuerNa' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 0,
                'items' => [
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoWithGatewayAll' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer'    => 'HDFC'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoWithMultipleDowntimes' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'method' => 'netbanking',
                        'severity' => 'high',
                        'instrument' => [
                            'issuer' => 'HDFC'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetWalletDowntime' => [
        'request' => [
            'url' => '/payments/downtimes',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    'entity'     => 'payment.downtime',
                    'method'     => 'wallet',
                    'end'        => null,
                    'instrument' => [
                        'issuer' => 'airtelmoney'
                    ]
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithCardDowntimeWithIssuerOrNetworkUnknown' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithCardDowntimeWithSpecificGatewayDown' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'card' => [
                        [
                            'issuer'    => ['ALL'],
                            'scheduled' => true,
                            'severity'  => 'low',
                            'card_type' => 'credit',
                            'network'   => ['DICL'],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithCardDowntimeWithGatewayExclusiveNetworkDown' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'card' => [
                        [
                            'issuer'    => ['ALL'],
                            'scheduled' => true,
                            'severity'  => 'low',
                            'card_type' => 'credit',
                            'network'   => ['DICL'],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithNetbankingDowntimeWithAllGateway' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'netbanking' => [
                        [
                            'issuer'    => ['HDFC'],
                            'scheduled' => true,
                            'severity'  => 'low',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithNetbankingDowntimeWithSharedNetbankingGateway' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'netbanking' => [
                        [
                            'issuer'      => [
                                'BACB',
                                'BBKM',
                                'COSB',
                                'ESAF',
                                'HSBC',
                                'JSBP',
                                'KCCB',
                                'KJSB',
                                'MSNU',
                                'NESF',
                                'NKGS',
                                'ORBC',
                                'SURY',
                                'TBSB',
                                'TJSB',
                                'TNSC',
                                'VARA',
                                'ZCBL',
                                'ANDB_C',
                                'BARB_C',
                                'DLXB_C',
                                'IBKL_C',
                                'LAVB_C',
                                'PUNB_C',
                                'RATN_C',
                                'SVCB_C',
                                'YESB_C'
                            ],
                            'scheduled'   => true,
                            'severity'    => 'low',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithNetbankingWithIssuerExclusiveTogateway' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                ],
        ],
    ],

    'testGetCheckoutPreferencesWithDirectNetbankingDowntime' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'netbanking' => [
                        [
                            'issuer'    => ['HDFC'],
                            'scheduled' => true,
                            'severity'  => 'low',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithWalletDowntime' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'wallet' => [
                        [
                            'issuer'    => ['olamoney'],
                            'scheduled' => true,
                            'severity'  => 'low',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithNetbankingDowntimeWithDirectTerminal' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'netbanking' => [
                        [
                            'issuer' => [
                                'ALLA',
                                'BBKM',
                                'BKDN',
                                'COSB',
                                'DCBL',
                                'DCBL',
                                'DEUT',
                                'DBSS',
                                'IDFB',
                                'IBKL',
                                'JSBP',
                                'KVBL',
                                'NKGS',
                                'PMCB',
                                'RATN',
                                'SBBJ',
                                'SBHY',
                                'SBIN',
                                'SBMY',
                                'STBP',
                                'SBTR',
                                'SCBL',
                                'SIBL',
                                'SVCB',
                                'SYNB',
                                'TMBL',
                                'TNSC',
                                'BARBC',
                                'BARBR',
                                'PUNBC',
                                'LAVBC'
                            ],
                            'scheduled'   => true,
                            'severity'    => 'low',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testPutPaytmMethod' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content' => [
                'paytm' => true
            ],
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                //''
            ]
        ]
    ],

    'testFetchEsScheduledPricing' => [
        'request' => [
            'url' => '/es/scheduled_pricing',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'percent_rate' => 240,
                'fixed_rate' => 0,
                'fee_bearer' => 'platform',
            ]
        ]
    ],

    'testFetchEsScheduledPricingInternationalPricing' => [
        'request' => [
            'url' => '/es/scheduled_pricing',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'percent_rate' => 12,
                'fixed_rate'   => 0,
                'fee_bearer' => 'platform',
            ]
        ]
    ],

    'testEnableEsScheduledSuccess' => [
        'request' => [
            'url' => '/es/scheduled',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ]
    ],

    'testEnableEsScheduledSuccessUpdatesOnDemandPricing' => [
        'request' => [
            'url' => '/es/scheduled',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ]
    ],

    'testEnableEsScheduledSuccessUpdatesOnDemandPricingReplicatesPlan' => [
        'request' => [
            'url' => '/es/scheduled',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ]
    ],

    'testEnableEsScheduledSuccessWithKAMMail' => [
        'request' => [
            'url' => '/es/scheduled',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ]
    ],

    'testEnableEsScheduledMailExpectedRoleTypesOnly' => [
        'request' => [
            'url' => '/es/scheduled',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ]
    ],

    'testEnableEsScheduledUnauthorizedUserAccess' => [
        'request' => [
            'url' => '/es/scheduled',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testEnableEsScheduledUnknownScheduleFailure' => [
        'request' => [
            'url' => '/es/scheduled',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Schedule not found in database.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_UNKNOWN_SCHEDULE,
        ],
    ],

    'testEnableEsScheduledUneditableFeature' => [
        'request' => [
            'url' => '/es/scheduled',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testEnableEsScheduledEsautomaticPricingUnavailable' => [
        'request' => [
            'url' => '/es/scheduled',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ]
    ],

    'testEnableEsScheduledEsautomaticPricingUnavailableForSharedPlan' => [
        'request' => [
            'url' => '/es/scheduled',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ]
    ],

    'testPutEmiMethod' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content' => [
                'emi' => [
                    'credit' => 1,
                ],
            ],
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetMerchantPaymentFailureAnalysis'=> [
        'request' => [
            'url' => '/merchants/payments/failure_analysis?from=1632361752&to=1632361757',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'failure_details' => [
                    'other_failure'      => 1,
                    'bank_failure'       => 2,
                    'customer_dropp_off' => 3,
                    'business_failure'   => 4,
                ],
                'summary'         => [
                    'number_of_successful_payments' => 18,
                    'number_of_total_payments'      => 28,
                ],
            ]
        ]
    ],

    'testGetMerchantPaymentFailureAnalysisInvalidRangeFail'=> [
        'request' => [
            'url' => '/merchants/payments/failure_analysis?from=1632361752&to=1640310552',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The date range is invalid',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPutPaytmCardNetworkAndEMIMethodWithUpdateObserverData'=> [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content' => [
                'paytm' => true,
                'emi'   =>  ['credit'=> '0', 'debit' => "1"],
                'card_networks' =>  ['AMEX'=>'1']
            ],
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'    =>  "10000000000000",
                'entity_name'  =>  "methods"
            ]
        ]
    ],

    'testPutEMIMethodWithUpdateObserverData'=> [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content' => [
                'emi'   =>  ['credit'=> '0', 'debit' => "1"]
            ],
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'    =>  "10000000000000",
                'entity_name'  =>  "methods"
            ]
        ]
    ],

    'testPutCardNetworkMethodWithUpdateObserverData'=> [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content' => [
                'card_networks' =>  ['AMEX'=>'1']
            ],
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'    =>  "10000000000000",
                'entity_name'  =>  "methods"
            ]
        ]
    ],

    'testPutPaytmMethodWithUpdateObserverData'=> [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content' => [
                'paytm' => true,
            ],
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'    =>  "10000000000000",
                'entity_name'  =>  "methods"
            ]
        ]
    ],

    'testGetKeySecret' => [
        'request' => [
            'url' => '/keys/rzp_test_TheTestAuthKey/secret',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'secret'      => 'TheKeySecretForTests',
                'merchant_id' => '10000000000000',
            ]
        ]
    ],

    'testEditCreditsWrongFormat' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetCheckoutRouteWithEmi' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutRouteWithMerchantSubEmi' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'emi_options' => [
                        'AMEX' => [
                            [
                                'duration'   => 9,
                                'interest'   => 0,
                                'subvention' => 'merchant',
                                'min_amount' => 316389
                            ],

                            [
                                'duration'   => 6,
                                'interest'   => 12,
                                'subvention' => 'customer',
                                'min_amount' => 300000
                            ],

                        ],
                        'HDFC' => [
                            [
                                'duration'   => 9,
                                'interest'   => 12,
                                'subvention' => 'customer',
                                'min_amount' => 300000
                            ],
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testGetCheckoutWithMultipleSubEmiOffers' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'emi_options' => [
                        'AMEX' => [
                            [
                                'duration'   => 9,
                                'interest'   => 0,
                                'subvention' => 'merchant',
                                'min_amount' => 316389
                            ],

                            [
                                'duration'   => 6,
                                'interest'   => 0,
                                'subvention' => 'merchant',
                                'min_amount' => 319149
                            ],

                        ],
                        'HDFC' => [
                            [
                                'duration'   => 9,
                                'interest'   => 12,
                                'subvention' => 'customer',
                                'min_amount' => 300000
                            ],
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testGetCheckoutRouteWithSavedGlobal' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'app_token' => 'capp_1000000custapp',
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],


    'testGetCheckoutRouteWithSavedGlobalVault' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'app_token' => 'capp_1000000custapp',
                'currency'  => 'INR'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutRouteWithoutCardTokenNames' => [
        'request'  => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'currency'    => 'INR'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutRouteWithCheckoutFeatures' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],


    'testGetCheckoutRouteWithSavedLocal' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutRouteCustomerContact' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'contact' => '9988776655',
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutRouteWithDeviceToken' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'contact' => '9988776655',
                'device_token' => '1000custdevice',
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'customer' => [
                    'saved' => true,
                    'contact' => '9988776655',
                    'email' => 'test@razorpay.com',
                ]
            ],
        ],
    ],

    'testGetCheckoutRouteWithAndroidMetadata' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'contact' => '9988776655',
                'device_token' => '1000custdevice',
                'currency' => 'INR',
                '_' => [
                    'library' => 'checkoutjs',
                    'platform' => 'android',
                    'version' => '1.0.0',
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutRouteWithCustomerTokenNoBillingAddress' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'currency' => 'INR',
                'amount'   => 100,
                '_' => [
                    'library' => 'checkoutjs',
                    'platform' => 'browser',
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutRouteWithAndroidMetadataNoSession' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'contact' => '9988776655',
                'device_token' => '1000custdevice',
                'currency' => 'INR',
                '_' => [
                    'library' => 'checkoutjs',
                    'platform' => 'android',
                    'version' => '1.0.0',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'customer' => [
                    'saved' => true,
                    'contact' => '9988776655',
                    'email' => 'test@razorpay.com',
                ]

            ],
        ],
    ],

    'testAddCategory2' => [
        'request' => [
            'content' => [
                'category2' => 'govt_education'
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testAddInvalidCategory2' => [
        'request' => [
            'content' => [
                'category2' => 'education2'
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Category : education2 invalid for merchant',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateImage' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_LOGO_NOT_IMAGE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_LOGO_NOT_IMAGE,
        ],
    ],

    'testValidateLogoImageSmall' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_LOGO_TOO_SMALL,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_LOGO_TOO_SMALL,
        ],
    ],

    'testValidateLogoImageNotSquare' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_LOGO_NOT_SQUARE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_LOGO_NOT_SQUARE,
        ],
    ],

    'testValidateLogoImageTooBig' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_LOGO_TOO_BIG,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_LOGO_TOO_BIG,
        ],
    ],

    'testEditMerchantEditGroups' => [
        'request' => [
            'url'       => '/merchants/%s',
            'method'    => 'put',
            'content'   => []
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testMerchantArchive' => [
        'request' => [
            'content' => [
                'action' => 'archive'
            ],
            'url' => '/merchants/%s/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity' => 'merchant',
                'activated' => false,
            ]
        ]
    ],

    'testMerchantForceActivate' => [
        'request' => [
            'content' => [
                'action' => 'force_activate'
            ],
            'url' => '/merchants/%s/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'    => 'merchant',
                'activated' => true,
                'live'      => true,
            ],
        ]
    ],

    'testMerchantEditReceiptEmailEventCapture' => [
        'request' => [
            'content' => [
                'action' => 'set_receipt_email_event_captured'
            ],
            'url' => '/merchants/%s/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'    => 'merchant',
                'receipt_email_trigger_event' => 'captured',
            ],
        ]
    ],

    'testMerchantEditReceiptEmailEventAuthorized' => [
        'request' => [
            'content' => [
                'action' => 'set_receipt_email_event_authorized'
            ],
            'url' => '/merchants/%s/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'    => 'merchant',
                'receipt_email_trigger_event' => 'authorized',
            ],
        ]
    ],

    'testMerchantArchiveWithNoMerchantDetails' => [
        'request' => [
            'content' => [
                'action' => 'archive'
            ],
            'url' => '/merchants/%s/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_DETAIL_DOES_NOT_EXISTS
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_DOES_NOT_EXISTS,
        ],
    ],

    'testMerchantArchiveForAlreadyArchivedMerchant' => [
        'request' => [
            'content' => [
                'action' => 'archive'
            ],
            'url' => '/merchants/%s/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ALREADY_ARCHIVED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ARCHIVED,
        ],
    ],

    'testMerchantUnarchive' => [
        'request' => [
            'content' => [
                'action' => 'unarchive'
            ],
            'url' => '/merchants/%s/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity' => 'merchant',
                'activated' => false,
            ]
        ]
    ],

    'testMerchantUnarchiveForNonArchived' => [
        'request' => [
            'content' => [
                'action' => 'unarchive'
            ],
            'url' => '/merchants/1cXSLlUU8V9sXl/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ARCHIVED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_ARCHIVED,
        ],
    ],

    'testMerchantSuspend' => [
        'request' => [
            'content' => [
                'action' => 'suspend'
            ],
            'url' => '/merchants/%s/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity' => 'merchant',
                'activated' => false,
                'live' => false,
                'hold_funds' => true,
            ]
        ]
    ],

    'testMerchantSuspendForAlreadySuspendedMerchant' => [
        'request' => [
            'content' => [
                'action' => 'suspend'
            ],
            'url' => '/merchants/%s/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ALREADY_SUSPENDED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_SUSPENDED,
        ],
    ],

    'testMerchantUnSuspend' => [
        'request' => [
            'content' => [
                'action' => 'unsuspend'
            ],
            'url' => '/merchants/%s/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity' => 'merchant',
                'activated' => false,
                'live' => true,
                'hold_funds' => false,
            ]
        ]
    ],

    'testMerchantUnSuspendForAlreadyUnSuspendedMerchant' => [
        'request' => [
            'content' => [
                'action' => 'unsuspend'
            ],
            'url' => '/merchants/1cXSLlUU8V9sXl/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_SUSPENDED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_SUSPENDED,
        ],
    ],

    'testMerchantUndefinedAction' => [
        'request' => [
            'content' => [
                'action' => 'hello123'
            ],
            'url' => '/merchants/1cXSLlUU8V9sXl/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED,
        ],
    ],

    'testScheduleTaskMigration' => [
        'request' => [
            'url' => '/merchants/schedules/migrate',
            'method' => 'POST',
            'content' => [
                'merchant_ids' => [
                    '1X4hRFHFx4UiXt'
                ]
            ]
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testUpdateSubmerchantEmail' => [
        'request' => [
            'url'       => '/merchants/10000000000044/email',
            'method'    => 'PUT',
            'content'   => [
                'email' => 'differentemail@razorpay.com'
            ],
            'server' => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000044',
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUB_MERCHANT_EMAIL_SAME_AS_PARENT_EMAIL,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUB_MERCHANT_EMAIL_SAME_AS_PARENT_EMAIL,
        ],
    ],

    'testCreateSubmerchantLogin' => [
        'request'  => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testCreateSubmerchantLoginByAdmin' => [
        'request'  => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => [],
            'server' => [
                'HTTP_X-Dashboard-User-Role' => 'manager',
            ]
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testCreateSubmerchantLoginPartnerAppMissing' => [
        'request'   => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => []
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FORBIDDEN,
                ],
            ],
            'status_code' => 403,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN,
        ],
    ],

    'testCreateSubmerchantLoginDuplicate' => [
        'request'   => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => []
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_WITH_ROLE_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_WITH_ROLE_ALREADY_EXISTS,
        ],
    ],

    'testCreateSubmerchantLoginUserExists' => [
        'request'  => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testCreateLinkedAccountLogin' => [
        'request'   => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => []
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FORBIDDEN,
                ],
            ],
            'status_code' => 403,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN,
        ],
    ],

    'testCreateSubmerchantLoginPartnerWithMarketplace' => [
        'request'  => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testAggregatorInviteSubMerchantToManageDash' => [
        'request'  => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => ['email' => 'invite.owner@razorpay.com']
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testFullyManagedInviteSubMerchantToManageDash' => [
        'request'   => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => ['email' => 'invite.owner@razorpay.com']
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testAggregatorInviteSubMerchantToManageDash2Owners' => [
        'request'   => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => ['email' => 'invite.owner@razorpay.com']
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CANNOT_ADD_MERCHANT_USER,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAggregatorInviteSubMerchantToManageDashAlreadyOwner' => [
        'request'   => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => ['email' => 'invite.owner@razorpay.com']
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_WITH_ROLE_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_WITH_ROLE_ALREADY_EXISTS,
        ],
    ],

    'testAggregatorInviteSubMerchantToManageDashEmailDifferent' => [
        'request'  => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => ['email' => 'testnew@razorpay.com']
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testAggregatorInviteEmailDifferentSubLoginPartnerEmail' => [
        'request'  => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => ['email' => 'test@razorpay.com']
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CANNOT_ADD_MERCHANT_USER,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOldAggregatorInviteSubMerchantUserWithEmail' => [
        'request'   => [
            'url'     => '/submerchant/user/10000000000040',
            'method'  => 'POST',
            'content' => ['email' => 'invite.owner@razorpay.com']
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CANNOT_ADD_MERCHANT_USER,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOfferCheckoutPreferences' => [
        'request' => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'order_id' => null
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAssignScheduleBulk' => [
        'request'  => [
            'url'     => '/merchants/schedules/bulk',
            'method'  => 'post',
            'content' => [
                'schedule' => [
                    'schedule_id' => '100001schedule',
                    'type' => 'settlement',
                ],
                'merchant_ids' => ['10000000000000', '1000000000test', '1000000000000x'],
            ],
        ],
        'response' => [
            'content' => [
                'total_count'  => 3,
                'failed_count' => 1,
                'failed_ids'   => ['1000000000000x']
            ],
        ],
    ],

    'testFetchingLinkedAcountsForMerchant' => [
        'request'  => [
            'url'     => '/merchant/parentaccount1/associated_accounts',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'associated_accounts'  => ['linkdaccount01']
            ]
        ]
    ],

    'testSubmitSupportCallRequest' => [
        'request'  => [
            'url'     => '/merchants/support_call',
            'method'  => 'post',
            'content' => [
                'contact' => '9988998899',
            ],
        ],
        'response' => [
            'content' => [
                'status'       => 'success',
                'code'         => '200',
                'details'      => 'Request accepted successfully',
                'unique_id'    => '39f45ff0-e3ab-11eb-9257-46dda8eef2ac',
            ],
        ],
    ],

    'testSubmitSupportCallRequestForBanking' => [
        'request'  => [
            'url'     => '/merchants/support_call',
            'method'  => 'post',
            'content' => [
                'contact' => '9988998899',
            ],
        ],
        'response' => [
            'content' => [
                'status'       => 'success',
                'code'         => '200',
                'details'      => 'Request accepted successfully',
                'unique_id'    => '39f45ff0-e3ab-11eb-9257-46dda8eef2ac',
            ],
        ],
    ],

    'testPartnerAcountsForMerchant' => [
        'request'  => [
            'url'     => '/merchant/parentaccount1/associated_accounts',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'associated_accounts'  => ['submerchant001']
            ],
        ],
    ],

    'testReferredAccountForMerchant' => [
        'request'  => [
            'url'     => '/merchant/parentaccount1/associated_accounts',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'associated_accounts'  => ['refaccount0001']
            ],
        ],
    ],

    'testSubmitSupportCallRequestWithInvalidContact' => [
        'request'  => [
            'url'     => '/merchants/support_call',
            'method'  => 'post',
            'content' => [
                'contact' => '9989988998899',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid contact number - 9989988998899',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSubmitSupportCallRequestOnNonWorkingHours' => [
        'request'  => [
            'url'     => '/merchants/support_call',
            'method'  => 'post',
            'content' => [
                'contact' => '9988998899',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Now is not a working hour. Please try this request on Mon-Fri between 9 AM - 6 PM.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSearchWithDateFilter' => [
        'request'  => [
            'url'     => '/admins/merchants',
            'method'  => 'get',
            'content' => [
                'count' => 20,
                'skip'  => 0,
                'from'  => 1514745000,
                'to'    => 1543861800,
            ],
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'id'     => '10000000000016',
                        'org_id' => '100000razorpay',
                        'name'   => 'laboriosam',
                        'email'  => 'emely97@kling.info',
                    ]
                ],
            ]
        ],
    ],

    'testQueueEntriesAfterBalanceSync' => [
        'request'  => [
            'url'    => '/merchant/sync_es/bulk',
            'method' => 'post',
        ],
        'response' => [
            'content'     => [
                'records_processed' => 2,
                'interval'          => 15,

            ],
            'status_code' => 200,
        ],
    ],

    'testESQueryAfterSync' => [
        'request'  => [
            'url'    => '/merchant/sync_es/bulk',
            'method' => 'post',
        ],
        'response' => [
            'content'     => [
                'records_processed' => 2,
                'interval'          => 15,

            ],
            'status_code' => 200,
        ],
    ],

    // ----------------------------------------------------------------------
    // Expectations for ES

    'testSearchWithDateFilterExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX') . 'merchant_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX') . 'merchant_test',
        'body'  => [
            '_source' => true,
            'from'    => '0',
            'size'    => '20',
            'query'   => [
                'bool' => [
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'range' => [
                                        'merchant_detail.submitted_at' => [
                                            'gte' => 1514745000,
                                            'lte' => 1543861800,
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'org_id' => [
                                            'value' => '100000razorpay',
                                        ],
                                    ],
                                ],
                                [
                                    'bool' => [
                                        'should' => [
                                            [
                                                'terms' => [
                                                    'admins' => [
                                                        'RzrpySprAdmnId',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ]
                            ],
                        ],
                    ],
                ],
            ],
            'sort'    => [
                '_score'     => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testSearchWithDateFilterExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id'     => '10000000000016',
                    '_source' => [
                        'id'              => '10000000000016',
                        'org_id'          => '100000razorpay',
                        'name'            => 'laboriosam',
                        'email'           => 'emely97@kling.info',
                        'parent_id'       => null,
                        'activated'       => false,
                        'activated_at'    => 1543922927,
                        'archived_at'     => null,
                        'suspended_at'    => null,
                        'website'         => 'http://www.green.com/quisquam-velit-ipsum-quae.html',
                        'billing_label'   => 'rerum',
                        'created_at'      => 1543922934,
                        'updated_at'      => 1543922934,
                        'merchant_detail' => [
                            'merchant_id'         => '10000000000016',
                            'steps_finished'      => '[]',
                            'activation_progress' => 0,
                            'activation_status'   => null,
                            'archived_at'         => null,
                            'submitted_at'        => null,
                            'updated_at'          => 1543922934,
                            'reviewer_id'         => null,
                            'activation_flow'     => null,
                        ],
                        'is_marketplace'  => false,
                        'referrer'        => null,
                        'balance'         => 0,
                    ],
                ],
            ]
        ]
    ],

    'testMerchantSwitchProduct' => [
        'request'  => [
            'url'     => '/merchants/product-switch',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testMerchantSwitchProductWithLedgerExperimentOn' => [
        'request'  => [
            'url'     => '/merchants/product-switch',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testMerchantSwitchProductActivationSMS' => [
        'request'  => [
            'url'     => '/merchants/product-switch',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testMerchantSwitchProductWithInvalidBeneficiaryNameThrowsProperException' => [
        'request'  => [
            'url'     => '/merchants/product-switch',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The beneficiary name field is invalid.',
                    'field'         => 'beneficiary_name',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantSwitchProductWithoutPreSignupSkipsOnboarding' => [
        'request'  => [
            'url'     => '/merchants/product-switch',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testMerchantSwitchProductWhenL1Incomplete' => [
        'request'  => [
            'url'     => '/merchants/product-switch',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testMerchantSwitchProductWhenMerchantNotActivated' => [
        'request'  => [
            'url'     => '/merchants/product-switch',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testMerchantSwitchProductWhenMerchantNotActivatedAndL1Incomplete' => [
        'request'  => [
            'url'     => '/merchants/product-switch',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testGetCheckoutPreferencesForCardlessEmi' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutPreferencesForPayLater' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testBulkAssignPricing' => [
        'request'  => [
            'url'     => '/merchants/pricing/bulk',
            'method'  => 'post',
            'content' => [
                'pricing_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
                'merchant_ids'    => [
                    '10000000000000',
                    '10000000000018',
                    '10000000000017',
                    '10000000000016',
                    '10000000000015',
                    '10000000000014',
                    '10000000000013',
                    '10000000000012',
                    '10000000000011'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'total_count'  => 9,
                'failed_count' => 4,
                'failed_ids'   => [
                    '10000000000018',
                    '10000000000017',
                    '10000000000016',
                    '10000000000015'
                ],
            ],
        ],
    ],

    'testBulkAssignPricingMissingInput' => [
        'request'  => [
            'url'     => '/merchants/pricing/bulk',
            'method'  => 'post',
            'content' => [
                'merchant_ids'    => [
                    '10000000000000',
                    '10000000000018',
                    '10000000000017',
                    '10000000000016',
                    '10000000000015',
                    '10000000000014',
                    '10000000000013',
                    '10000000000012',
                    '10000000000011'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The pricing plan id field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetMerchantPartnerStatus' => [
        'request'  => [
            'url'     => '/merchant/partner_status',
            'method'  => 'get',
            'content' => [
                'email' => 'testdum@razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'merchant' => false,
                'partner'  => false,
            ],
            'status_code' => 200,
        ],
    ],

    'testGetMerchantPartnerStatusExtraInput' => [
        'request'  => [
            'url'     => '/merchant/partner_status',
            'method'  => 'get',
            'content' => [
                'email' => 'testdum@razorpay.com',
                'name' => 'testdum',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'name is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testInternationalEnableWhenAlreadyActive' => [
        'request'  => [
            'url'     => '/merchant/international',
            'method'  => 'patch',
            'content' => [
                'international' => true
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ALREADY_INTERNATIONAL,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_INTERNATIONAL,
        ],
    ],

    'testInternationalEnableWhenInternationalActivationFlowIsAlreadySet' => [
        'request'  => [
            'url'     => '/merchant/international',
            'method'  => 'patch',
            'content' => [
                'international' => true
            ],
        ],
        'response' => [
            'content'     => [
                'international'    => true,
                'convert_currency' => false,
            ],
            'status_code' => 200,
        ],
    ],

    'testInternationalEnableWhenWebsiteNotSet' => [
        'request'  => [
            'url'     => '/merchant/international',
            'method'  => 'patch',
            'content' => [
                'international' => true
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_WEBSITE_NOT_SET,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_NOT_SET,
        ],
    ],

    'testInternationalEnable' => [
        'request'  => [
            'url'     => '/merchant/international',
            'method'  => 'patch',
            'content' => [
                'international' => true
            ],
        ],
        'response' => [
            'content'     => [
                'international'    => true,
                'convert_currency' => false,
            ],
            'status_code' => 200,
        ],
    ],

    'testInternationalEnableForBlackList' => [
        'request'   => [
            'url'     => '/merchant/international',
            'method'  => 'patch',
            'content' => [
                'international' => true
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_INTERNATIONAL_STATUS_CHANGE_REQUEST,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_INTERNATIONAL_STATUS_CHANGE_REQUEST,
        ],
    ],

    'testInternationalDisableWhenAlreadyInActive' => [
        'request'  => [
            'url'     => '/merchant/international',
            'method'  => 'patch',
            'content' => [
                'international' => 0 // send false
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_INTERNATIONAL_NOT_ENABLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_INTERNATIONAL_NOT_ENABLED,
        ],
    ],

    'testInternationalDisable' => [
        'request'  => [
            'url'     => '/merchant/international',
            'method'  => 'patch',
            'content' => [
                'international' => 0 // send false
            ],
        ],
        'response' => [
            'content' => [
                'international'     => false,
                'convert_currency'  => false,
            ],
            'status_code' => 200,
        ],
    ],

    'testInternationalToggleWithInvalidValue' => [
        'request'  => [
            'url'     => '/merchant/international',
            'method'  => 'patch',
            'content' => [
                'international' => 'abc'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The international field must be true or false.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantCacheSyncInBothMode' => [
        'request'  => [
            'url'    => '/merchant/activation',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testGetOrgDetails' => [
        'request'  => [
            'url'     => '/merchants/10000000000000/org',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'id'                => 'org_100000razorpay',
                'primary_host_name' => 'dashboard.razorpay.in',
            ],
        ],
    ],

    'testSendBankingAccountsViaWebhook' => [
        'request'  => [
            'url'     => '/merchant/10000000000000/banking_accounts/',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testSendBankingAccountsViaWebhook1' => [
        'request'  => [
            'url'     => '/merchant/10000000000000/banking_accounts/',
            'method'  => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testMerchantApplyRestrictionSettingsSuccess' => [
        'request'  => [
            'url'     => '/merchant/restrict',
            'method'  => 'PATCH',
            'content' => [
                'merchant_id' => '',
                'action'      => 'add'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '',
                'restricted'  => true
            ]
        ],
    ],

    'testMerchantApplyRestrictionSettingFailure' => [
        'request'  => [
            'url'     => '/merchant/restrict',
            'method'  => 'PATCH',
            'content' => [
                'merchant_id' => '',
                'action'      => 'add'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Merchant Restricted Settings failed to apply because users of merchant are associated with multiple merchants',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_RESTRICTED_SETTINGS_NOT_APPLIED,
        ],
    ],

    'testMerchantRemoveRestrictionSettings' => [
        'request'  => [
            'url'     => '/merchant/restrict',
            'method'  => 'PATCH',
            'content' => [
                'merchant_id' => '',
                'action'      => 'remove'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '',
                'restricted'  => false
            ]
        ],
    ],

    'testEditDashboardWhitelistedIpsLive' => [
        'request'  => [
            'content' => [
                'dashboard_whitelisted_ips_live' => [
                    '1.1.1.1',
                    '2.2.2.2'
                ],
            ],
            'url'     => '/merchants/1X4hRFHFx4UiXt',
            'method'  => 'put',
            'server'  => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'id'     => '1X4hRFHFx4UiXt',
                'entity' => 'merchant',
            ]
        ]
    ],
    'testEditDashboardInvalidWhitelistedIpsLive' => [
        'request'   => [
            'content' => [
                'dashboard_whitelisted_ips_live' => [
                    'abc.def.ghi.ekl',
                    '1.1.1.1'
                ],
            ],
            'url'     => '/merchants/10000000000000',
            'method'  => 'put',
            'server'  => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'One or more IPs in the input are invalid',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testEditDashboardWhitelistedIpsTest' => [
        'request'  => [
            'content' => [
                'dashboard_whitelisted_ips_test' => [
                    '1.1.1.1',
                    '2.2.2.2'
                ],
            ],
            'url'     => '/merchants/1X4hRFHFx4UiXt',
            'method'  => 'put',
            'server'  => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'id'     => '1X4hRFHFx4UiXt',
                'entity' => 'merchant',
            ]
        ]
    ],
    'testEditDashboardInvalidWhitelistedIpsTest' => [
        'request'   => [
            'content' => [
                'dashboard_whitelisted_ips_test' => [
                    'abc.def.ghi.ekl',
                    '1.1.1.1'
                ],
            ],
            'url'     => '/merchants/10000000000000',
            'method'  => 'put',
            'server'  => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'One or more IPs in the input are invalid',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testEditDashboardRedundantWhitelistedIpsTest' => [
        'request'   => [
            'content' => [
                'dashboard_whitelisted_ips_test' => [
                    '1.1.1.1',
                    '1.1.1.1'
                ],
            ],
            'url'     => '/merchants/10000000000000',
            'method'  => 'put',
            'server'  => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The dashboard_whitelisted_ips_test.0 field has a duplicate value.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testEditDashboardRedundantWhitelistedIpsLive' => [
        'request'   => [
            'content' => [
                'dashboard_whitelisted_ips_live' => [
                    '1.1.1.1',
                    '1.1.1.1'
                ],
            ],
            'url'     => '/merchants/10000000000000',
            'method'  => 'put',
            'server'  => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The dashboard_whitelisted_ips_live.0 field has a duplicate value.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateContactMobileOfUser' => [
        'request'  => [
            'url'     => '/users/contact',
            'method'  => 'patch',
            'content' => [
                'user_id'        => '',
                'contact_mobile' => '999999999'
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testUpdateContactMobileOfUserByAdmin' => [
        'request'  => [
            'url'     => '/users-admin/contact',
            'method'  => 'patch',
            'content' => [
                'user_id'        => '',
                'contact_mobile' => '999999999'
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testUpdateContactMobileOfUserByAdminAndVerifyByFeatureFlag' => [
        'request'  => [
            'url'     => '/users-admin/contact',
            'method'  => 'patch',
            'content' => [
                'user_id'        => '',
                'contact_mobile' => '999999999'
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testUpdateContactMobileOfSelfUser' => [
        'request'   => [
            'url'     => '/users/contact',
            'method'  => 'patch',
            'content' => [
                'user_id'        => '',
                'contact_mobile' => '999999999'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Action not allowed for self user',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACTION_NOT_ALLOWED_FOR_SELF_USER,
        ],
    ],

    'testUserAccountUnlock' => [
        'request'  => [
            'url'     => '/users/account/{id}/unlock',
            'method'  => 'put',
            'content' => [
            ],
        ],
        'response' => [
            'content'     => [
                'account_locked' => false,
                'user_id'        => '',
            ],
            'status_code' => 200,
        ],
    ],

    'testMerchantRazorxBulkExperimentFetch' => [
        'request'  => [
            'url'     => '/razorx/bulkevaluate',
            'method'  => 'get',
            'content' => [
                'features' => 'feature1,feature2'
            ],
        ],
        'response' => [
            'content'     => [
                'feature1' => ['result' => 'on'],
                'feature2' => ['result' => 'off'],
            ],
            'status_code' => 200,
        ],
    ],

    'testFetchPartnerIntent'  => [
        'request'   => [
            'method'    => 'GET',
            'url'       => '/merchant/partner-intent',
        ],
        'response'  => [
            'content'   => [
                'partner_intent'    => true,
            ],
            'status_code'           => 200,
        ],
    ],

    'testFetchPartnerIntentForMerchantWithoutPartnerIntent' => [
        'request'   => [
            'method'    => 'GET',
            'url'       => '/merchant/partner-intent',
        ],
        'response'  => [
            'content'   => [
                'partner_intent'    => null,
            ],
            'status_code'           => 200,
        ],
    ],

    'testFetchPartnerIntentWithPartnerIntentFalse'  => [
        'request'   => [
            'method'    => 'GET',
            'url'       => '/merchant/partner-intent',
        ],
        'response'  => [
            'content'   => [
                'partner_intent'    => false,
            ],
            'status_code'           => 200,
        ],
    ],

    'testUpdatePartnerIntentWithPartnerIntentTrue'  => [
        'request'   => [
            'method'    => 'PATCH',
            'url'       => '/merchant/partner-intent',
            'content'   => [
                'partner_intent'    => 0 //sends false,
            ],
        ],
        'response'  => [
            'content'   => [
                'partner_intent'    => '0',
            ],
            'status_code'           => 200,
        ],
    ],

    'testUpdatePartnerIntentWithPartnerIntentFalse' => [
        'request'   => [
            'method'    => 'PATCH',
            'url'       => '/merchant/partner-intent',
            'content'   => [
                'partner_intent'    => 1 //sends true,
            ],
        ],
        'response'  => [
            'content'   => [
                'partner_intent'    => '1',
            ],
            'status_code'           => 200,
        ],
    ],

    'testUpdatePartnerIntentWithPartnerIntentNull'  => [
        'request'   => [
            'method'    => 'PATCH',
            'url'       => '/merchant/partner-intent',
            'content'   => [
                'partner_intent'    => 1 //sends true,
            ],
        ],
        'response'  => [
            'content'   => [
                'partner_intent'    => '1',
            ],
            'status_code'           => 200,
        ],
    ],

    'testSetInheritanceParent'     =>  [
        'request'   => [
            'method'    => 'POST',
            'url'       => '/merchants/{id}/inheritance_parent',
            'content'   =>  [
                'id'    =>  'parents_id'
            ]
        ],
        'response'  => [
            'content'   => [
            ],
            'status_code'           => 200,
        ],
    ],


    'testSetInheritanceParentBatch'     =>  [
        'request'   => [
            'method'    => 'POST',
            'url'       => '/merchants/inheritance_parent/bulk',
            'content'   =>
                [
                    [
                        'idempotency_key'    =>  '12345',
                        'merchant_id'        =>  'submerchant_id',
                        'parent_merchant_id' =>  'parent_id'
                    ],
                    [
                        'idempotency_key'    => '12346',
                        'merchant_id'        =>  'submerchant2_id',
                        'parent_merchant_id' =>  'parent_id'
                    ]
                ]
        ],
        'response'  => [
            'content'   => [
            ],
            'status_code'           => 200,
        ],
    ],

    'testSetNonPartnerInheritanceParent'    =>  [
        'request'   => [
            'method'    => 'POST',
            'url'       => '/merchants/{id}/inheritance_parent',
            'content'   =>  [
                'id'    =>  'parents_id'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Inheritance parent should be aggregator or fully-managed partner of the submerchant',
            ],
            'status_code'   => 400,
            ],
            'exception' => [
                'class'               => RZP\Exception\BadRequestException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_INHERITANCE_PARENT_SHOULD_BE_PARTNER_PARENT_OF_SUBMERCHANT,
            ],
        ],
    ],

    'testGetInheritanceParent'     =>  [
        'request'   => [
            'method'    => 'GET',
            'url'       => '/merchants/{id}/inheritance_parent',
        ],
        'response'  => [
            'content'   => [
            ],
            'status_code'           => 200,
        ],
    ],

    'testGetInheritanceParentIfNotPresent'     =>  [
        'request'   => [
            'method'    => 'GET',
            'url'       => '/merchants/{id}/inheritance_parent',
        ],
        'response'  => [
            'content'   => [
            ],
            'status_code'           => 400,
        ],
    ],

    'testDeleteInheritanceParent'     =>  [
        'request'   => [
            'method'    => 'DELETE',
            'url'       => '/merchants/{id}/inheritance_parent',
        ],
        'response'  => [
            'content'   => [
            ],
            'status_code'           => 200,
        ],
    ],

    'testDeleteInheritanceParentIfNotPresent'     =>  [
        'request'   => [
            'method'    => 'DELETE',
            'url'       => '/merchants/{id}/inheritance_parent',
        ],
        'response'  => [
            'content'   => [
            ],
            'status_code'           => 400,
        ],
    ],

    'testInternalGetMerchant'    =>  [
        'request'       =>  [
            'method'    =>  'GET',
            'url'       =>  '/internal/merchants/{id}'
        ],
        'response'      =>  [
            'content'   => [
                'merchant' => [
                    'id'        => '100ghi000ghi00',
                    'entity'    =>  'merchant',
                    'feature'   => ['upi_otm', 'override_hitachi_blacklst']
                ],
                'merchant_detail' => [
                    'contact_email' => 'test@gmail.com'
                ],
                'website_details' => [
                    'about' => null,
                    'pricing' => null,
                    'privacy' => null,
                ]
            ],
            'status_code'   =>  200
        ]
    ],

    'testInternalGetMerchantBulk'    =>  [
        'request'       =>  [
            'method'    =>  'GET',
            'url'       =>  '/internal/merchants/{id}'
        ],
        'response'      =>  [
            'content'   => [
                'count' => 2,
                'items' => [
                    [
                        'id'        => '100ghi000ghi00',
                        'entity'    =>  'merchant',
                        'name'      => 'test0',
                    ],
                    [
                        'id'        => '100ghi000ghi01',
                        'entity'    =>  'merchant',
                        'name'      => 'test1',
                    ],
                ],
            ],
            'status_code'   =>  200
        ],
    ],

    'testInternalMerchantSendEmail'    =>  [
        'request'       =>  [
            'method'    =>  'POST',
            'url'       =>  '/internal/merchants/10000000000000/send_email',
            'content' => [
                'type' => 'merchant_instrument_status_update',
                'data' => ['current_status'=> 'activated', 'old_status'=>'requested', "instrument_name"=>'visa']

            ],
        ],
        'response'      =>  [
            'content'   => [
                "success" => true
            ],
            'mail_content' => [
                'current_status' => "activated",
                'old_status'     =>"requested",
                'instrument_name'=> "visa",
                'contact_email'  =>"test@razorpay.com",
            ],
            'status_code'   =>  200
        ]
    ],

    'testInternalMerchantSendEmailInvalidType'    =>  [
        'request'       =>  [
            'method'    =>  'POST',
            'url'       =>  '/internal/merchants/10000000000000/send_email',
            'content' => [
                'type' => 'test',
                'data' => ['current_status'=> 'activated', 'old_status'=>'requested']

            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid email type.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_EMAIL_TYPE,
        ],
    ],

    'testInternalMerchantSendEmailInstrumentNameMissing'    =>  [
        'request'       =>  [
            'method'    =>  'POST',
            'url'       =>  '/internal/merchants/10000000000000/send_email',
            'content' => [
                'type' => 'merchant_instrument_status_update',
                'data' => ['current_status'=> 'activated', 'old_status'=>'requested']

            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The instrument name field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetBalances' => [
        'request' => [
            'url' => '/balances',
            'method' => 'GET',
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'count' => 2,
                'items' => [
                    '0' => [
                        'id'                => '100def000def00',
                        'type'              => 'primary',
                        'currency'          => null,
                        'name'              => null,
                        'balance'           => 100000,
                    ],
                    '1' => [
                        'id'                => '100abc000abc00',
                        'type'              => 'banking',
                        'currency'          => 'INR',
                        'name'              => null,
                        'balance'           => 0,
                    ]
                ]
            ],
        ],
    ],

    'testGetBalancesWhenNoBalanceExists' => [
        'request'  => [
            'url'    => '/balances',
            'method' => 'GET',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'count' => 0,
                'items' => [
                ]
            ],
        ],
    ],

    'testGetBalancesByType' => [
        'request' => [
            'url' => '/balances?type=primary',
            'method' => 'GET',
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    '0' => [
                        'id'                => '100def000def00',
                        'type'              => 'primary',
                        'currency'          => null,
                        'name'              => null,
                        'balance'           => 100000,
                    ],
                ],
            ],
        ],
    ],

    'testMerchantInternationalDisableAction' => [
        'request'  => [
            'content' => [
                'action' => 'disable_international'
            ],
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'                => 'merchant',
                'international'         => false,
                'product_international' => '0000000000',
                'merchant_detail'       => [
                    'international_activation_flow' => 'blacklist',
                ]
            ]
        ]
    ],

    'testMerchantInternationalDisableActionNewRoute' => [
        'requestWorkflowActionCreation'  => [
            'content' => [
                'action'          => 'disable_international',
                'merchant_id'     => '10000000000000',
                'risk_attributes' => [
                    'trigger_communication' => '1',
                    'risk_tag'              => 'risk_international_disablement',
                    'risk_source'           => 'high_fts',
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                ],
            ],
            'url'     => '/risk-actions/create',
            'method'  => 'POST',
        ],
        'responseWorkflowActionCreation' => [
            'status_code' => 200,
            'content'     => [
                'entity_name'   => 'merchant',
                'entity_id'     => "10000000000000",
                'state'         => "open",
                'maker_type'    => "admin",
                'org_id'        => "org_100000razorpay",
                'approved'      => false,
                'current_level' => 1,
                'maker'         => [
                    'id' => "admin_RzrpySprAdmnId",
                ],
                'permission'    => [
                    'name' => "edit_merchant_disable_international",
                ],
            ],
        ],
        'responseWorkflowActionApproval' => [
            'content' => [
                'maker_id'      => "admin_RzrpySprAdmnId",
                'maker_type'    => "admin",
                'maker'         => [
                    'id' => "admin_RzrpySprAdmnId",
                ],
                'permission'    => [
                    'name' => "edit_merchant_disable_international",
                ],
                'state'         => "executed",
                'state_changer' => [
                    'id' => "admin_RzrpySprAdmnId",
                ],
                'org_id'        => "org_100000razorpay",
                'approved'      => true,
            ]
        ],
    ],

    'testMerchantInternationalDisableActionNewRouteForAlreadyDisabled' => [
        'request'   => [
            'content' => [
                'action'          => 'disable_international',
                'merchant_id'     => '10000000000000',
                'risk_attributes' => [
                    'trigger_communication' => '1',
                    'risk_tag'              => 'risk_international_disablement',
                    'risk_source'           => 'high_fts',
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                ],
            ],
            'url'     => '/risk-actions/create',
            'method'  => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNATIONAL_ALREADY_DISABLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ALREADY_DISABLED,
        ],
    ],

    'testMerchantInternationalDisableActionNewRouteValidationFailureRiskSource' => [
        'request'   => [
            'content' => [
                'action'          => 'disable_international',
                'merchant_id'     => '10000000000000',
                'risk_attributes' => [
                    'trigger_communication' => '2',
                    'risk_tag'              => 'risk_international_disablement',
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                ],
            ],
            'url'     => '/risk-actions/create',
            'method'  => 'POST',
        ],
        'response'  => [
            "status_code" => 400,
            'content'     => ['error' => [
                'code'        => "BAD_REQUEST_ERROR",
                'description' => "The risk source field is required.",
                'reason'      => "input_validation_failed",
                'field'       => "risk_source",
            ],]
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantInternationalDisableActionNewRouteValidationFailureTriggerCommunication' => [
        'request'   => [
            'content' => [
                'action'          => 'disable_international',
                'merchant_id'     => '10000000000000',
                'risk_attributes' => [
                    'trigger_communication' => '3',
                    'risk_tag'              => 'risk_international_disablement',
                    'risk_source'           => 'high_fts',
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                ],
            ],
            'url'     => '/risk-actions/create',
            'method'  => 'POST',
        ],
        'response'  => [
            "status_code" => 400,
            'content'     => ['error' => [
                'code'        => "BAD_REQUEST_ERROR",
                'description' => "The selected trigger communication is invalid.",
                'reason'      => "input_validation_failed",
                'field'       => "trigger_communication",
            ],]
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantInternationalDisableActionNewRouteValidationFailureInvalidRiskTag' => [
        'request'   => [
            'content' => [
                'action'          => 'disable_international',
                'merchant_id'     => '10000000000000',
                'risk_attributes' => [
                    'trigger_communication' => '1',
                    'risk_tag'              => 'risk_review_suspend',
                    'risk_source'           => 'high_fts',
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                ],
            ],
            'url'     => '/risk-actions/create',
            'method'  => 'POST',
        ],
        'response'  => [
            "status_code" => 400,
            'content'     => ['error' => [
                'code'        => "BAD_REQUEST_ERROR",
                'description' => "The selected risk tag is invalid.",
                'reason'      => "input_validation_failed",
                'field'       => "risk_tag",
            ],]
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantInternationalDisableActionFailure' => [
        'request'   => [
            'content' => [
                'action' => 'disable_international'
            ],
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNATIONAL_ALREADY_DISABLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ALREADY_DISABLED,
        ],

    ],

    'testMerchantInternationalPGEnableActionNewRoute' => [
        'requestWorkflowActionCreation'  => [
            'content' => [
                'action'          => 'enable_international',
                'merchant_id'     => '10000000000000',
                'risk_attributes' => [
                    'international_products' => ['payment_gateway'],
                ],
            ],
            'url'     => '/risk-actions/create',
            'method'  => 'POST',
        ],
        'responseWorkflowActionCreation' => [
            'status_code' => 200,
            'content'     => [
                'entity_name'   => 'merchant',
                'entity_id'     => "10000000000000",
                'state'         => "open",
                'maker_type'    => "admin",
                'org_id'        => "org_100000razorpay",
                'approved'      => false,
                'current_level' => 1,
                'maker'         => [
                    'id' => "admin_RzrpySprAdmnId",
                ],
                'permission'    => [
                    'name' => "edit_merchant_enable_international",
                ],
            ],
        ],
        'responseWorkflowActionApproval' => [
            'content' => [
                'maker_id'      => "admin_RzrpySprAdmnId",
                'maker_type'    => "admin",
                'maker'         => [
                    'id' => "admin_RzrpySprAdmnId",
                ],
                'permission'    => [
                    'name' => "edit_merchant_enable_international",
                ],
                'state'         => "executed",
                'state_changer' => [
                    'id' => "admin_RzrpySprAdmnId",
                ],
                'org_id'        => "org_100000razorpay",
                'approved'      => true,
            ]
        ],
    ],

    'testMerchantInternationalProdV2EnableActionNewRoute' => [
        'requestWorkflowActionCreation'  => [
            'content' => [
                'action'          => 'enable_international',
                'merchant_id'     => '10000000000000',
                'risk_attributes' => [
                    'international_products' => ['invoices'],
                ],
            ],
            'url'     => '/risk-actions/create',
            'method'  => 'POST',
        ],
        'responseWorkflowActionCreation' => [
            'status_code' => 200,
            'content'     => [
                'entity_name'   => 'merchant',
                'entity_id'     => "10000000000000",
                'state'         => "open",
                'maker_type'    => "admin",
                'org_id'        => "org_100000razorpay",
                'approved'      => false,
                'current_level' => 1,
                'maker'         => [
                    'id' => "admin_RzrpySprAdmnId",
                ],
                'permission'    => [
                    'name' => "edit_merchant_enable_international",
                ],
            ],
        ],
        'responseWorkflowActionApproval' => [
            'content' => [
                'maker_id'      => "admin_RzrpySprAdmnId",
                'maker_type'    => "admin",
                'maker'         => [
                    'id' => "admin_RzrpySprAdmnId",
                ],
                'permission'    => [
                    'name' => "edit_merchant_enable_international",
                ],
                'state'         => "executed",
                'state_changer' => [
                    'id' => "admin_RzrpySprAdmnId",
                ],
                'org_id'        => "org_100000razorpay",
                'approved'      => true,
            ]
        ],
    ],

    'testMerchantInternationalPGEnableAction' => [
        'request'  => [
            'content' => [
                'action'                 => 'enable_international',
                'international_products' => ['payment_gateway']
            ],
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'                => 'merchant',
                'international'         => true,
                'product_international' => '1000000000',
                'merchant_detail'       => [
                    'international_activation_flow' => 'greylist',
                ]
            ]
        ]
    ],

    'testOutOfOrgMerchantInternationalEnableAction' => [
        'request'  => [
            'content' => [
                'action'                 => 'enable_international',
                'international_products' => ['payment_gateway']
            ],
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'                => 'merchant',
                'international'         => true,
                'product_international' => '1111000000',
                'merchant_detail'       => [
                    'international_activation_flow' => null,
                ]
            ]
        ]
    ],

    'testMerchantInternationalProdV2EnableAction' => [
        'request'  => [
            'content' => [
                'action'                 => 'enable_international',
                'international_products' => ['invoices']
            ],
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'                => 'merchant',
                'international'         => true,
                'product_international' => '0111000000',
                'merchant_detail'       => [
                    'international_activation_flow' => 'whitelist',
                ]
            ]
        ]
    ],

    'testOutOfOrgBlacklistedMerchantInternationalEnableAction' => [
        'request'  => [
            'content' => [
                'action'                 => 'enable_international',
                'international_products' => ['invoices']
            ],
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'                => 'merchant',
                'international'         => true,
                'product_international' => '1111000000',
                'merchant_detail'       => [
                    'international_activation_flow' => null,
                ]
            ]
        ]
    ],

    'testOutOfOrgBlacklistedMerchantInternationalEnableActionNewRoute' => [
        'request'   => [
            'content' => [
                'action'          => 'enable_international',
                'merchant_id'     => '10000000000000',
                'risk_attributes' => [
                    'international_products' => ['payment_gateway'],
                ],
            ],
            'url'     => '/risk-actions/create',
            'method'  => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ],

    ],

    'testInternationalEnableActionforAlreadyEnabledNewRoute' => [
        'request'   => [
            'content' => [
                'action'          => 'enable_international',
                'merchant_id'     => '10000000000000',
                'risk_attributes' => [
                    'international_products' => ['payment_gateway'],
                ],
            ],
            'url'     => '/risk-actions/create',
            'method'  => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNATIONAL_ALREADY_ENABLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ALREADY_ENABLED,
        ],

    ],

    'testMerchantInternationalEnableCategoryOneGreylist' => [
        'request'  => [
            'content' => [
                'action'                 => 'enable_international',
                'international_products' => ['invoices']
            ],
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity'                => 'merchant',
                'international'         => true,
                'product_international' => '0111000000',
                'merchant_detail'       => [
                    'international_activation_flow' => 'greylist',
                ]
            ]
        ]
    ],

    'testMerchantInternationalDisableBulkEdit' => [
        'request'  => [
            'content' => [
                'merchant_ids' => [
                    '10000000000000',
                ],
                'action'       => 'disable_international',
            ],
            'url'     => '/merchants/bulk',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'total'   => 1,
                'success' => 1,
                'failed'  => 0,
            ]
        ]
    ],

    'testMerchantInternationalEnableBulkEdit' => [
        'request'  => [
            'content' => [
                'merchant_ids'           => [
                    '10000000000000',
                ],
                'action'                 => 'enable_international',
                'international_products' => ['payment_gateway', 'payment_gateway', 'payment_pages', 'invoices'],
            ],
            'url'     => '/merchants/bulk',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'total'   => 1,
                'success' => 1,
                'failed'  => 0,
            ]
        ]
    ],

    'testMerchantProductInternationalEnableBulkEdit' => [
        'request'  => [
            'content' => [
                'merchant_ids'           => [
                    '10000000000000',
                ],
                'action'                 => 'enable_international',
                'international_products' => ['payment_gateway', 'payment_gateway', 'payment_pages', 'invoices']
            ],
            'url'     => '/merchants/bulk',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'total'   => 1,
                'success' => 1,
                'failed'  => 0,
            ]
        ]
    ],

    'testMerchantProductInternationalEnableBulkEditFailure' => [
        'request'  => [
            'content' => [
                'merchant_ids'           => [
                    '10000000000000',
                ],
                'action'                 => 'enable_international',
                'international_products' => ['payment_gateway', 'payment_gateway', 'payment_pages', 'invoices']
            ],
            'url'     => '/merchants/bulk',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'total'     => 1,
                'success'   => 0,
                'failed'    => 1,
                'failedIds' => ["10000000000000"]
            ],
        ],
    ],

    'testMerchantInternationalEnableBulkEditBlacklistFailure' => [
        'request'  => [
            'content' => [
                'merchant_ids'           => [
                    '10000000000000',
                ],
                'action'                 => 'enable_international',
                'international_products' => ['payment_gateway', 'payment_gateway', 'payment_pages', 'invoices']
            ],
            'url'     => '/merchants/bulk',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'total'     => 1,
                'success'   => 0,
                'failed'    => 1,
                'failedIds' => ["10000000000000"]
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithOrderMethodForNonTPVEnabledMerchant' => [
        'request'  => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
                'order' => [
                    'method' => 'upi'
                ]
            ],
        ],
    ],

    'testMerchantBankingVAMigration' => [
        'request'  => [
            'url'     => '/merchants/banking-va-migration',
            'method'  => 'post',
            'content' => [
                'merchant_ids' => ['10000000000000']
            ],
        ],
        'response' => [
            'content' => [
                'total'     => 1,
                'processed' => 1,
                'illegal'   => [],
                'failed'    => []
            ]
        ]
    ],

    'testRequestMerchantProductInternational' => [
        'request'  => [
            'url'     => '/merchant/international/product',
            'method'  => 'PATCH',
            'content' => [
                'products' => [
                    'payment_gateway',
                    'payment_pages',
                    'invoices',
                ]
            ],
        ],
        'response' => [
            'content'     => [
                'id'                    => '10000000000000',
                'entity'                => 'merchant',
                'product_international' => '2022000000',
            ],
            'status_code' => 200,
        ],
    ],

    'testRequestMerchantProductInternationalFailure' => [
        'request'   => [
            'url'     => '/merchant/international/product',
            'method'  => 'PATCH',
            'content' => [
                'products' => [],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The products field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],

    ],

    'testGetProductInternationalStatus' => [
        'request'  => [
            'url'    => '/merchants/product_international/workflow/status/all',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'data' => [
                    'payment_gateway' => 'rejected',
                    'payment_links'   => 'approved',
                    'payment_pages'   => 'approved',
                    'invoices'        => 'approved',
                ],
            ],
        ],
    ],

    'testGetProductInternationalStatusOldWorkflow' => [
        'request'  => [
            'url'    => '/merchants/product_international/workflow/status/all',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'data' => [
                    'payment_gateway' => 'rejected',
                    'payment_links'   => 'rejected',
                    'payment_pages'   => 'rejected',
                    'invoices'        => 'rejected',
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithConfigIdInOrder' => [
        'request'  => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency' => 'INR',
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithDefaultConfig' => [
        'request'  => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency' => 'INR',
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutPreferencesForInvoiceWithOffer' => [
        'request'  => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'invoice_id' => null,
                'currency'   => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'offers' => [
                    [
                        'name'            => 'Test Offer',
                        'payment_method'  => 'card',
                        'payment_network' => 'VISA',
                        'issuer'          => 'HDFC',
                    ],
                    [
                        'name'            => 'Test Offer',
                        'payment_method'  => 'card',
                        'payment_network' => 'VISA',
                        'issuer'          => 'HDFC',
                    ]
                ]
            ],
        ],
    ],

    'testGetPreferencesInternal' => [
        'request'  => [
            'url'    => '/internal/preferences/10000000000000',
            'method' => 'get',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'methods' => [

                ],
            ],
        ],
    ],

    'testGetAutoDisabledMethodsForMerchant' => [
        'request'  => [
            'url'    => '/internal/auto_disabled_methods/10000000000000',
            'method' => 'get',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'auto_disabled_methods' => ['emi']
            ],
        ],
    ],

    'testGetAutoDisabledMethodsForMerchantWithIgnoreBlacklistedForInstrument' => [
        'request'  => [
            'url'    => '/internal/auto_disabled_methods/10000000000000',
            'method' => 'get',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'auto_disabled_methods' =>
                    ["credit_card",
                     "emi",
                     "cardless_emi",
                     "prepaid_card",
                     "paylater",
                    ]
            ],
        ],
    ],

    'testGetAutoDisabledMethodsForBlacklistedCategoryMerchant' => [
        'request'  => [
            'url'    => '/internal/auto_disabled_methods/10000000000000',
            'method' => 'get',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'auto_disabled_methods' => [
                    'credit_card',
                    'debit_card',
                    'amex',
                    'netbanking',
                    'upi',
                    'emi',
                    'cardless_emi',
                    'prepaid_card',
                    'paylater',
                    'airtelmoney',
                    'freecharge',
                    'jiomoney',
                    'mobikwik',
                    'mpesa',
                    'olamoney',
                    'payumoney',
                    'payzapp',
                    'sbibuddy',
                    'phonepe',
                    'paytm',
                    'paypal',
                ],
                'kyc_enabled'           => false,
            ],
        ],
    ],

    'testGetAutoDisabledMethodsForMerchantWithRandomCategory' => [
        'request'  => [
            'url'    => '/internal/auto_disabled_methods/10000000000000',
            'method' => 'get',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'auto_disabled_methods' => []
            ],
        ],
    ],

    'testGetAutoDisabledMethodsFor5399EcommerceMerchant' => [
        'request'  => [
            'url'    => '/internal/auto_disabled_methods/10000000000000',
            'method' => 'get',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'auto_disabled_methods' => []
            ],
        ],
    ],

    'testGetAutoDisabledMethodsFor5399OthersMerchant' => [
        'request'  => [
            'url'    => '/internal/auto_disabled_methods/10000000000000',
            'method' => 'get',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'auto_disabled_methods' => ['emi']
            ],
        ],
    ],

    'testCreateSubmerchantWithCode' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'post',
            'content' => [
                'name'    => 'Linked Account 1',
                'code'    => 'linked_account-1',
                'account' => true,
                'email'   => 'linked1@account.com',
            ],
        ],
        'response' => [
            'content' => [
                'name'      => 'Linked Account 1',
                'email'     => 'linked1@account.com',
                'entity'    => 'merchant',
                'activated' => false,
                'code'      => 'linked_account-1',
            ],
        ],
    ],

    'testCreateSubmerchantWithInvalidCode' => [
        'request'   => [
            'url'     => '/submerchants',
            'method'  => 'post',
            'content' => [
                'name'    => 'Linked Account 1',
                'code'    => 'la',
                'account' => true,
                'email'   => 'linked1@account.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The code must be at least 3 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOrgLevelFeatureAccess' => [
        'request'  => [],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testDisable2dot0AppsWithOrgMerchantFlags' => [
        'request'  => [],
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => "BAD_REQUEST_ERROR",
                    'description'   =>"You do not have permission to access this feature.",
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testEditMerchantCategoryShouldResetMethods' => [
        'request'  => [
            'raw'    => json_encode([
                                        'category'      => '6211',
                                        'category2'     => 'mutual_funds',
                                        'reset_methods' => true,
                                    ]),
            'url'    => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'CONTENT_TYPE'     => 'application/json',
                'HTTP_X-Dashboard' => 'true',
            ]
        ],
        'response' => [
            'content' => [
                'id'        => '1X4hRFHFx4UiXt',
                'entity'    => 'merchant',
                'category'  => '6211',
                'category2' => 'mutual_funds',
            ]
        ]
    ],

    'testEditMerchantCategoryShouldNotResetMethodsIfResetMethodsInInputIsFalse' => [
        'request'  => [
            'raw'    => json_encode([
                                        'category'      => '6211',
                                        'category2'     => 'mutual_funds',
                                        'reset_methods' => false,
                                    ]),
            'url'    => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'CONTENT_TYPE'     => 'application/json',
                'HTTP_X-Dashboard' => 'true',
            ]
        ],
        'response' => [
            'content' => [
                'id'        => '1X4hRFHFx4UiXt',
                'entity'    => 'merchant',
                'category'  => '6211',
                'category2' => 'mutual_funds',
            ]
        ]
    ],

    'testEditMerchantCategoryShouldResetMethodsValidationFailure2' => [
        'request'  => [
            'raw'    => json_encode([
                                        'category'      => '6211',
                                        'category2'     => 'mutual_funds',
                                        'reset_methods' => 'yes'
                                    ]),
            'url'    => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'CONTENT_TYPE'     => 'application/json',
                'HTTP_X-Dashboard' => 'true',
            ]
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testGetCheckoutPreferencesIINDetails'           => [
        'request'  => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'customer_id'     => 'cust_1000ggcustomer',
                'personalisation' => '1',
                'currency'        => 'INR',
                'amount'          => '10000'
            ],
        ],
        'response' => [
            'content' => [
                'customer'          => [
                    'tokens' => [
                        'items' => [
                            [
                                'card' => [
                                    'type'    => 'credit',
                                    'issuer'  => 'SBIN',
                                    'network' => 'Mastercard',
                                ]
                            ]
                        ]
                    ]
                ],
                'preferred_methods' => [
                    '+919955555555' => [
                        'instruments' => [
                            [],
                            [],
                            [
                                'method'  => 'card',
                                'issuer'  => null,
                                'type'    => 'credit',
                                'network' => 'Mastercard',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'testGetCheckoutPreferencesWithPreferredMethods' => [
        'request'  => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency'        => 'INR',
                'personalisation' => true,
                'order_id'        => 'null',
                'customer_id'     => 'cust_100000customer'
            ],
        ],
        'response' => [
            'content' => [
                'preferred_methods' => [
                    '1234567890' => [
                        'instruments'               => [
                            [
                                'instrument' => 'paytm',
                                'method'     => 'wallet',
                            ],
                            [
                                'instrument' => 'SBIN',
                                'method'     => 'netbanking',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'card',
                                'issuer'     => null,
                                'type'       => 'debit',
                                'network'    => 'Visa',
                            ],
                        ],
                        "is_customer_identified"    => true,
                        "user_aggregates_available" => false,
                        'versionID'                 => 'v2',
                    ],
                ]
            ],
        ],
    ],
    'testGetCheckoutPersonalisation'                 => [
        'request'  => [
            'url'     => '/personalisation',
            'method'  => 'get',
            'content' => [
                'order_id'    => 'null',
                'customer_id' => 'cust_100000customer'
            ],
        ],
        'response' => [
            'content' => [
                'preferred_methods' => [
                    '1234567890' => [
                        'instruments'               => [
                            [
                                'instrument' => 'paytm',
                                'method'     => 'wallet',
                            ],
                            [
                                'instrument' => 'SBIN',
                                'method'     => 'netbanking',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'card',
                                'issuer'     => null,
                                'type'       => 'debit',
                                'network'    => 'Visa',
                            ],
                        ],
                        'is_customer_identified'    => true,
                        "user_aggregates_available" => false,
                        'versionID'                 => 'v2',
                    ],
                ]
            ],
        ],
    ],

    'testGetCheckoutPersonalisationForNonLoggedInUser' => [
        'request'  => [
            'url'     => '/personalisation',
            'method'  => 'get',
            'content' => [
                'order_id' => 'null',
            ],
        ],
        'response' => [
            'content' => [
                "preferred_methods" => [
                    'default' => [
                        'instruments' => [
                            [
                                'instrument' => 'paytm',
                                'method'     => 'wallet',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'netbanking',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'card',
                                'issuer'     => null,
                                'type'       => 'debit',
                                'network'    => 'Visa',
                            ],
                        ],
                    ],
                ]
            ],
        ],

    ],

    'testGetCheckoutPersonalisationForNonLoggedInUserUpiIntent' => [
        'request'  => [
            'url'     => '/personalisation',
            'method'  => 'get',
            'content' => [
                'order_id'   => 'null',
                'upi_intent' => true,
            ],
        ],
        'response' => [
            'content' => [
                "preferred_methods" => [
                    'default' => [
                        'instruments' => [
                            [
                                'instrument' => 'paytm',
                                'method'     => 'wallet',
                            ],
                            [
                                'instrument' => '@ybl',
                                'method'     => 'upi',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'netbanking',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'card',
                                'issuer'     => null,
                                'type'       => 'debit',
                                'network'    => 'Visa',
                            ],
                        ],
                    ],
                ]
            ],
        ],

    ],

    'testGetCheckoutPersonalisationForNonLoggedInUserWithContact' => [
        'request' => [
            'url'     => '/personalisation',
            'method'  => 'get',
            'content' => [
                'order_id' => 'null',
                'contact'  => '+918888888888',
            ],
        ],
        'response' => [
            'content' => [
                'preferred_methods' => [
                    '+918888888888' => [
                        'instruments' => [
                            [
                                'instrument' => 'paytm',
                                'method'     => 'wallet',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'netbanking',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'card',
                                'issuer'     => null,
                                'type'       => 'debit',
                                'network'    => 'Visa',
                            ],
                        ],
                    ],
                ]
            ],
        ],

    ],

    'testGetCheckoutPersonalisationForNonLoggedInUserWithInternationalContact' => [
        'request'  => [
            'url'     => '/personalisation',
            'method'  => 'get',
            'content' => [
                'order_id' => 'null',
                'contact'  => '+118888888888',
            ],
        ],
        'response' => [
            'content' => [
                'preferred_methods' => [
                    '+118888888888' => [
                        'instruments' => [
                            [
                                'instrument' => 'paypal',
                                'method'     => 'wallet',
                            ],
                            [
                                'instrument' => 'paytm',
                                'method'     => 'wallet',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'netbanking',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'card',
                                'issuer'     => null,
                                'type'       => 'debit',
                                'network'    => 'Visa',
                            ],
                        ],
                    ],
                ]
            ],
        ],

    ],

    'testGetCheckoutPersonalisationForContactDifferentFromLogInContact' => [
        'request' => [
            'url'     => '/personalisation',
            'method'  => 'get',
            'content' => [
                'order_id'  => 'null',
                'contact' => '+918888888888'
            ],
        ],
        'response' => [
            'content' => [
                'preferred_methods' => [
                    '+918888888888' => [
                        'instruments' => [
                            [
                                'instrument' => 'paytm',
                                'method'     => 'wallet',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'netbanking',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'card',
                                'issuer'     => null,
                                'type'       => 'debit',
                                'network'    => 'Visa',
                            ],
                        ],
                        'is_customer_identified' => false,
                        'user_aggregates_available' => false,
                        'versionID' => 'v2'
                    ],
                ]
            ],
        ],
    ],

    'testGetCheckoutPersonalisationForContactSameWithLogInContact' => [
        'request' => [
            'url'     => '/personalisation',
            'method'  => 'get',
            'content' => [
                'order_id'  => 'null',
                'contact' => '+919988776655'
            ],
        ],
        'response' => [
            'content' => [
                'preferred_methods' => [
                    '+919988776655' => [
                        'instruments' =>[
                            [
                                'instrument' => 'paytm',
                                'method'     => 'wallet',
                            ],
                            [
                                'instrument' => 'SBIN',
                                'method'     => 'netbanking',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'card',
                                'issuer'     => null,
                                'type'       => 'debit',
                                'network'    => 'Visa',
                            ],
                        ],
                        'is_customer_identified' => true,
                        'user_aggregates_available' => false,
                        'versionID' => 'v2'
                    ],
                ]
            ],
        ],
    ],

    'testGetCheckoutPersonalisationWithCustomerIdAndLogInContact' => [
        'request' => [
            'url'     => '/personalisation',
            'method'  => 'get',
            'content' => [
                'order_id'  => 'null',
                'customer_id' => 'cust_100000customer'
            ],
        ],
        'response' => [
            'content' => [
                'preferred_methods' => [
                    '1234567890' => [
                        'instruments' =>[
                            [
                                'instrument' => 'paytm',
                                'method'     => 'wallet',
                            ],
                            [
                                'instrument' => 'SBIN',
                                'method'     => 'netbanking',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'card',
                                'issuer'     => null,
                                'type'       => 'debit',
                                'network'    => 'Visa',
                            ],
                        ],
                        'is_customer_identified' => true,
                        'user_aggregates_available' => false,
                        'versionID' => 'v2'
                    ],
                ]
            ],
        ],
    ],

    'testGetCheckoutPersonalisationForContact'                    => [
        'request'  => [
            'url'     => '/personalisation',
            'method'  => 'get',
            'content' => [
                'order_id' => 'null',
                'contact'  => '1234567890'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ]
    ],
    'testGetCheckoutPersonalisationForCustomerId'                 => [
        'request'   => [
            'url'     => '/personalisation',
            'method'  => 'get',
            'content' => [
                'order_id'    => 'null',
                'customer_id' => 'cust_100005customer'
            ],
        ],
        'response'  => [
            'content'     => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testGetCheckoutPersonalisationWithCustomerIdAndInputContact' => [
        'request'  => [
            'url'     => '/personalisation',
            'method'  => 'get',
            'content' => [
                'order_id'    => 'null',
                'customer_id' => 'cust_100000customer',
                'contact'     => 1234123412,
            ],
        ],
        'response' => [
            'content' => [
                'preferred_methods' => [
                    '1234567890' => [
                        'instruments' => [
                            [
                                'instrument' => 'paytm',
                                'method'     => 'wallet',
                            ],
                            [
                                'instrument' => 'SBIN',
                                'method'     => 'netbanking',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'card',
                                'issuer'     => null,
                                'type'       => 'debit',
                                'network'    => 'Visa',
                            ],

                        ],
                        "is_customer_identified"    => true,
                        "user_aggregates_available" => false,
                        'versionID'                 => 'v2',
                    ],
                ]
            ],
        ],
    ],

    'testGetBadgeDetailsForRTBNotEnabled' => [
        'request'  => [
            'url'    => '/badge_details',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'rtb_details' => false,
            ],
        ]
    ],

    'testGetBadgeDetailsForRTBEnabled' => [
        'request'  => [
            'url'    => '/badge_details',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ]
    ],

    'testGetBadgeDetailsForRTBEnabledCustomRedis' => [
        'request'  => [
            'url'    => '/badge_details',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ]
    ],

    'testGetCheckoutPreferencesWithRTB' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'rtb' => true,
            ],
        ],
    ],

    'testPreferencesRTBWithOptoutStatus' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'rtb' => false,
            ],
        ],
    ],

    'testPreferencesRTBWithIneligibleStatus' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'rtb' => false,
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithoutRTB' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'rtb' => false,
            ],
        ],
    ],

    'testGetCheckoutPersonalisationWithNullPreferences' => [
        'request'  => [
            'url'     => '/personalisation',
            'method'  => 'get',
            'content' => [
                'order_id' => 'null',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testHoldFundsWithUpdateObserverData' => [
        'request'  => [
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
            'content' => [
                'action' => 'hold_funds',
            ]
        ],
        'response' => [
            'content' => [
                'workflow' => [
                    'name' => "Hold Funds",
                ],
            ],
        ],
    ],

    'testGetCheckoutPersonalisationWithNullPreferencesFalse' => [
        'request'  => [
            'url'     => '/personalisation',
            'method'  => 'get',
            'content' => [
                'order_id' => 'null',
                'contact'  => 1234123412,
            ],
        ],
        'response' => [
            'content' => [
                'preferred_methods' => [
                    '1234123412' => [
                        'instruments' => [
                            [
                                'instrument' => 'paytm',
                                'method'     => 'wallet',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'netbanking',
                            ],
                            [
                                'instrument' => null,
                                'method'     => 'card',
                                'issuer'     => null,
                                'type'       => 'debit',
                                'network'    => 'Visa',
                            ],
                        ],
                        "is_customer_identified"    => false,
                        "user_aggregates_available" => false,
                        'versionID'                 => 'v2',
                    ],
                ]
            ],
        ],
    ],

    'testReleaseFundsWithUpdateObserverData' => [
        'request'  => [
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
            'content' => [
                'action' => 'release_funds',
            ]
        ],
        'response' => [
            'content' => [
                'workflow' => [
                    'name' => "Release Funds",
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithCovidReliefBothEnable' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'show_donation' => true
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithoutCovidReliefBothDisable' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'show_donation' => false
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithoutCovidReliefRazorXOff' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'show_donation' => false
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithoutCovidReliefFeatureOff' => [
        'request'  => [
            'url'    => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'show_donation' => false
            ],
        ],
    ],

    'testMerchantSupportOptionDedupeMerchant' => [

        'request' => [
            'url'    => '/merchants/support/option/flags',
            'method' => \Requests::GET
        ],

        'response' => [
            'content' => [
                "show_chat"                => false,
                "show_create_ticket_popup" => false,
                "message_body"             => "",
                "cta_list"                 => [],
            ],
        ],
    ],

    'testMerchantSupportOptionOldFlow' => [

        'request' => [
            'url'    => '/merchants/support/option/flags',
            'method' => \Requests::GET
        ],

        'response' => [
            'content' => [
                "show_create_ticket_popup" => false,
            ],
        ],
    ],

    'testGetRiskData' => [
        'request'        => [
            'url'    => '/merchants/10000000000000/risk/data',
            'method' => 'GET',
        ],
        'response'       => [
            'content' => [
                'transaction_dedupe_merchant_risk_score'         => null,
                'global_merchant_risk_score'                     => null,
                'merchant_vintage'                               => '6 month+',
                'first_transaction_date_attempted'               => '2020-12-16',
                'last_transaction_date_attempted'                => '2021-06-16',
                'number_of_transactions_captured'                => [0 => ['lifetime' => 18131,], 1 => ['1_month' => 3238,],],
                'total_GMV_captured'                             => [0 => ['lifetime' => '₹7,894,323',], 1 => ['1_month' => '₹1,560,118',],],
                'success_rate_(%)'                               => [0 => ['lifetime' => '21.04',], 1 => ['1_month' => '20.26',],],
                'domestic_merchant_chargeback_to_sale_ratio_(%)' => [0 => ['lifetime' => 0.04,], 1 => ['3_months' => '0.07',],],
                'domestic_merchant_fraud_to_sale_ratio_(%)'      => [0 => ['lifetime' => '0.04',], 1 => ['3_months' => '0.03',],],
                'total_dispute_count'                            => [0 => ['lifetime' => 4,], 1 => ['1_month' => 2,],],
                'international_details'                          => [
                    'number_of_transactions_captured'       => [0 => ['lifetime' => 12,], 1 => ['1_month' => 4,],],
                    'total_GMV_captured'                    => [0 => ['lifetime' => '₹7,550',], 1 => ['1_month' => '₹2,295',],],
                    'international_order_approval_rate_(%)' => '37.14',
                    'success_rate_(%)'                      => [0 => ['lifetime' => '5.17',], 1 => ['1_month' => '5.97',],],
                    'merchant_CTS_(%)'                      => [0 => ['lifetime' => null,], 1 => ['3_months' => null,],],
                    'merchant_FTS_(%)'                      => [0 => ['lifetime' => '4.65',], 1 => ['3_months' => '4.65',],],
                ],
                'risk_alerts'                                    => [
                    'PL_PP_dedupe'          => 0,
                    'customer_flagging'     => 0,
                    'blacklisted_ip_alerts' => null,
                ],
                'risk_workflow_count'                            => [
                    'FOH'          => 0,
                    'suspend'      => 0,
                    'disable_live' => 0,
                ],
            ],
        ],
        'druid_response' => [
            'Blacklist_IP_blacklist_ip_entities'                                              => null,
            'Blacklist_IP_merchant_id'                                                        => null,
            'Customer_Flagging_customer_flagged'                                              => 0,
            'Customer_Flagging_merchant_id'                                                   => 'GDcB2RalYmdJNz',
            'Dispute_1month_merchant_id'                                                      => 'GDcB2RalYmdJNz',
            'Dispute_1month_past_1_month_disputes'                                            => 2,
            'Dispute_ltd_lifetime_disputes'                                                   => 4,
            'Dispute_ltd_merchant_id'                                                         => 'GDcB2RalYmdJNz',
            'Domestic_FTS_lifetime_domestic_FTS'                                              => '0.04',
            'Domestic_FTS_merchant_id'                                                        => 'GDcB2RalYmdJNz',
            'Domestic_FTS_past_3_month_domestic_FTS'                                          => '0.03',
            'Domestic_FTS_past_3_month_domestic_adjusted_FTS'                                 => '0.02',
            'Domestic_cts_3months_last_3_months_cts'                                          => '0.07',
            'Domestic_cts_3months_merchant_id'                                                => 'GDcB2RalYmdJNz',
            'Domestic_cts_overall_lifetime_cts'                                               => 0.04,
            'Domestic_cts_overall_merchant_id'                                                => 'GDcB2RalYmdJNz',
            'Global_Merchant_Risk_Scoring_Global_Merchant_Risk_Score'                         => null,
            'Global_Merchant_Risk_Scoring_merchant_id'                                        => null,
            'International_FTS_lifetime_international_FTS'                                    => '4.65',
            'International_FTS_merchant_id'                                                   => 'GDcB2RalYmdJNz',
            'International_FTS_past_3_month_international_FTS'                                => '4.65',
            'International_FTS_past_3_month_international_adjusted_FTS'                       => '4.65',
            'International_OAR_Order_Approval_rate'                                           => '37.14',
            'International_OAR_merchant_id'                                                   => 'GDcB2RalYmdJNz',
            'International_Payment_Details_lifetime_captured_gmv'                             => '7549.830000',
            'International_Payment_Details_lifetime_captured_payments'                        => 12,
            'International_Payment_Details_lifetime_success_rate'                             => '5.17',
            'International_Payment_Details_merchant_id'                                       => 'GDcB2RalYmdJNz',
            'International_Payment_Details_past_one_month_captured_gmv'                       => '2295.000000',
            'International_Payment_Details_past_one_month_captured_payments'                  => 4,
            'International_Payment_Details_past_one_month_success_rate'                       => '5.97',
            'International_cts_3months_last_3_months_cts'                                     => null,
            'International_cts_3months_merchant_id'                                           => 'GDcB2RalYmdJNz',
            'International_cts_overall_lifetime_cts'                                          => null,
            'International_cts_overall_merchant_id'                                           => 'GDcB2RalYmdJNz',
            'PL_PP_Dedupe_merchant_id'                                                        => 'GDcB2RalYmdJNz',
            'PL_PP_Dedupe_pl_pp_deduped'                                                      => 0,
            'Payment_Details_first_transaction_date'                                          => '2020-12-16',
            'Payment_Details_last_transaction_date'                                           => '2021-06-16',
            'Payment_Details_lifetime_captured_gmv'                                           => '7894322.640000',
            'Payment_Details_lifetime_captured_payments'                                      => 18131,
            'Payment_Details_lifetime_success_rate'                                           => '21.04',
            'Payment_Details_merchant_id'                                                     => 'GDcB2RalYmdJNz',
            'Payment_Details_past_one_month_captured_gmv'                                     => '1560118.000000',
            'Payment_Details_past_one_month_captured_payments'                                => 3238,
            'Payment_Details_past_one_month_success_rate'                                     => '20.26',
            'Transacting_Dedupe_Merchant_Risk_Scoring_Transacting_Dedupe_Merchant_Risk_Score' => null,
            'Transacting_Dedupe_Merchant_Risk_Scoring_merchant_id'                            => null,
            '__time'                                                                          => '2020-12-16T00:00:00.000Z',
            'merchant_vintage_merchant_id'                                                    => 'GDcB2RalYmdJNz',
            'merchant_vintage_merchant_vintage'                                               => '6 month+',
            'merchants_created_at'                                                            => 1608100654,
            'merchants_id'                                                                    => 'GDcB2RalYmdJNz',
            'workflows_data_Disable_live_workflows'                                           => 0,
            'workflows_data_FOH_workflows'                                                    => 0,
            'workflows_data_Suspend_workflows'                                                => 0,
            'workflows_data_merchant_id'                                                      => 'GDcB2RalYmdJNz',
        ],
    ],

    'testGetRiskDataNotFoundCase' => [
        'request'  => [
            'url'    => '/merchants/10000000000000/risk/data',
            'method' => 'GET',
        ],
        'response' => [
            'content'     => [],
            'status_code' => 404,
        ],
    ],

    'testGetRiskDataDruidFailedCase' => [
        'request'  => [
            'url'    => '/merchants/10000000000000/risk/data',
            'method' => 'GET',
        ],
        'response' => [
            'content'     => [
                'error' => 'dummy druid error'
            ],
            'status_code' => 503,
        ],
    ],

    'testGetRiskDataColumnNotPresent' => [
        'request'        => [
            'url'    => '/merchants/10000000000000/risk/data',
            'method' => 'GET',
        ],
        'response'       => [
            'content' => [
                'transaction_dedupe_merchant_risk_score'         => null,
                'global_merchant_risk_score'                     => null,
                'merchant_vintage'                               => '6 month+',
                'first_transaction_date_attempted'               => '2020-12-16',
                'last_transaction_date_attempted'                => '2021-06-16',
                'number_of_transactions_captured'                => [0 => ['lifetime' => 18131,], 1 => ['1_month' => 3238,],],
                'total_GMV_captured'                             => [0 => ['lifetime' => '₹7,894,323',], 1 => ['1_month' => '₹1,560,118',],],
                'success_rate_(%)'                               => [0 => ['lifetime' => '21.04',], 1 => ['1_month' => '20.26',],],
                'domestic_merchant_chargeback_to_sale_ratio_(%)' => [0 => ['lifetime' => 0.04,], 1 => ['3_months' => '0.07',],],
                'domestic_merchant_fraud_to_sale_ratio_(%)'      => [0 => ['lifetime' => 'Data not present in druid',], 1 => ['3_months' => '0.03',],],
                'total_dispute_count'                            => [0 => ['lifetime' => 4,], 1 => ['1_month' => 2,],],
                'international_details'                          => [
                    'number_of_transactions_captured'       => [0 => ['lifetime' => 12,], 1 => ['1_month' => 4,],],
                    'total_GMV_captured'                    => [0 => ['lifetime' => '₹7,550',], 1 => ['1_month' => '₹2,295',],],
                    'international_order_approval_rate_(%)' => '37.14',
                    'success_rate_(%)'                      => [0 => ['lifetime' => '5.17',], 1 => ['1_month' => '5.97',],],
                    'merchant_CTS_(%)'                      => [0 => ['lifetime' => null,], 1 => ['3_months' => null,],],
                    'merchant_FTS_(%)'                      => [0 => ['lifetime' => '4.65',], 1 => ['3_months' => '4.65',],],
                ],
                'risk_alerts'                                    => [
                    'PL_PP_dedupe'          => 0,
                    'customer_flagging'     => 0,
                    'blacklisted_ip_alerts' => null,
                ],
                'risk_workflow_count'                            => [
                    'FOH'          => 0,
                    'suspend'      => 0,
                    'disable_live' => 0,
                ],
            ],
        ],
        'druid_response' => [
            'Blacklist_IP_blacklist_ip_entities'                                              => null,
            'Blacklist_IP_merchant_id'                                                        => null,
            'Customer_Flagging_customer_flagged'                                              => 0,
            'Customer_Flagging_merchant_id'                                                   => 'GDcB2RalYmdJNz',
            'Dispute_1month_merchant_id'                                                      => 'GDcB2RalYmdJNz',
            'Dispute_1month_past_1_month_disputes'                                            => 2,
            'Dispute_ltd_lifetime_disputes'                                                   => 4,
            'Dispute_ltd_merchant_id'                                                         => 'GDcB2RalYmdJNz',
            'Domestic_FTS_merchant_id'                                                        => 'GDcB2RalYmdJNz',
            'Domestic_FTS_past_3_month_domestic_FTS'                                          => '0.03',
            'Domestic_FTS_past_3_month_domestic_adjusted_FTS'                                 => '0.02',
            'Domestic_cts_3months_last_3_months_cts'                                          => '0.07',
            'Domestic_cts_3months_merchant_id'                                                => 'GDcB2RalYmdJNz',
            'Domestic_cts_overall_lifetime_cts'                                               => 0.04,
            'Domestic_cts_overall_merchant_id'                                                => 'GDcB2RalYmdJNz',
            'Global_Merchant_Risk_Scoring_Global_Merchant_Risk_Score'                         => null,
            'Global_Merchant_Risk_Scoring_merchant_id'                                        => null,
            'International_FTS_lifetime_international_FTS'                                    => '4.65',
            'International_FTS_merchant_id'                                                   => 'GDcB2RalYmdJNz',
            'International_FTS_past_3_month_international_FTS'                                => '4.65',
            'International_FTS_past_3_month_international_adjusted_FTS'                       => '4.65',
            'International_OAR_Order_Approval_rate'                                           => '37.14',
            'International_OAR_merchant_id'                                                   => 'GDcB2RalYmdJNz',
            'International_Payment_Details_lifetime_captured_gmv'                             => '7549.830000',
            'International_Payment_Details_lifetime_captured_payments'                        => 12,
            'International_Payment_Details_lifetime_success_rate'                             => '5.17',
            'International_Payment_Details_merchant_id'                                       => 'GDcB2RalYmdJNz',
            'International_Payment_Details_past_one_month_captured_gmv'                       => '2295.000000',
            'International_Payment_Details_past_one_month_captured_payments'                  => 4,
            'International_Payment_Details_past_one_month_success_rate'                       => '5.97',
            'International_cts_3months_last_3_months_cts'                                     => null,
            'International_cts_3months_merchant_id'                                           => 'GDcB2RalYmdJNz',
            'International_cts_overall_lifetime_cts'                                          => null,
            'International_cts_overall_merchant_id'                                           => 'GDcB2RalYmdJNz',
            'PL_PP_Dedupe_merchant_id'                                                        => 'GDcB2RalYmdJNz',
            'PL_PP_Dedupe_pl_pp_deduped'                                                      => 0,
            'Payment_Details_first_transaction_date'                                          => '2020-12-16',
            'Payment_Details_last_transaction_date'                                           => '2021-06-16',
            'Payment_Details_lifetime_captured_gmv'                                           => '7894322.640000',
            'Payment_Details_lifetime_captured_payments'                                      => 18131,
            'Payment_Details_lifetime_success_rate'                                           => '21.04',
            'Payment_Details_merchant_id'                                                     => 'GDcB2RalYmdJNz',
            'Payment_Details_past_one_month_captured_gmv'                                     => '1560118.000000',
            'Payment_Details_past_one_month_captured_payments'                                => 3238,
            'Payment_Details_past_one_month_success_rate'                                     => '20.26',
            'Transacting_Dedupe_Merchant_Risk_Scoring_Transacting_Dedupe_Merchant_Risk_Score' => null,
            'Transacting_Dedupe_Merchant_Risk_Scoring_merchant_id'                            => null,
            '__time'                                                                          => '2020-12-16T00:00:00.000Z',
            'merchant_vintage_merchant_id'                                                    => 'GDcB2RalYmdJNz',
            'merchant_vintage_merchant_vintage'                                               => '6 month+',
            'merchants_created_at'                                                            => 1608100654,
            'merchants_id'                                                                    => 'GDcB2RalYmdJNz',
            'workflows_data_Disable_live_workflows'                                           => 0,
            'workflows_data_FOH_workflows'                                                    => 0,
            'workflows_data_Suspend_workflows'                                                => 0,
            'workflows_data_merchant_id'                                                      => 'GDcB2RalYmdJNz',
        ],
    ],

    'testFireHubspotEventFromDashboard' => [
        'request' => [
            'url'     => '/merchants/fire_hubspot_event',
            'method'  => \Requests::POST,
            'content' => [
                "merchant_email"       => 'test@razorpay.com',
                "ca_neostone_eligible" => 'TRUE',
            ],
        ],

        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testFireHubspotEventFromDashboardForWrongEmailId' => [
        'request' => [
            'url'     => '/merchants/fire_hubspot_event',
            'method'  => \Requests::POST,
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                "merchant_email"       => 'testing@abc.com',
                "ca_neostone_eligible" => 'TRUE',
            ],
        ],

        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The provided Email Id does not belongs to the merchant',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testNeostoneSendFlagToSalesforce' => [
        'request' => [
            'url'     => '/merchants/lead_to_salesforce',
            'method'  => \Requests::POST,
            'content' => [
                'merchant_id'           => '10000000000000',
                'x_onboarding_category' => 'self_serve'
            ],
        ],

        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testNeostoneSendFlagToSalesforceWrongMid' => [
        'request' => [
            'url'     => '/merchants/lead_to_salesforce',
            'method'  => \Requests::POST,
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'merchant_id'           => '10000000000001',
                'x_onboarding_category' => 'self_serve'
            ],
        ],

        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testMerchantActionNotificationCronFOH' => [
        'request'  => [
            'url'     => '/merchants/action/notification',
            'method'  => 'POST',
            'content' => [],
            'server'  => [
                'Content-Type' => ' application/json'
            ],
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testMerchantActionNotificationCronSuspend' => [
        'request'  => [
            'url'     => '/merchants/action/notification',
            'method'  => 'POST',
            'content' => [],
            'server'  => [
                'Content-Type' => ' application/json'
            ],
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testEditBulkMerchantActionCronFOH' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'hold_funds',
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 1,
                'failed'    => 0,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testEditBulkMerchantActionCronSuspend' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'suspend',
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 1,
                'failed'    => 0,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testEditBulkMerchantActionDisableLive' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'live_disable',
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 1,
                'failed'    => 0,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testEditBulkDisableLiveMerchantNotLive' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'live_disable',
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 0,
                'failed'    => 1,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBulkDisableLiveWithoutPermissionFail' => [
        'request'   => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044', '10000000000055'],
                'action'       => "live_disable",
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
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

    'testEditBulkMerchantActionEnableLive' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'live_enable',
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 1,
                'failed'    => 0,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testEditBulkEnableLiveMerchantAlreadyLive' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'live_enable',
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 0,
                'failed'    => 1,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ]
    ],

    'testEditBulkEnableLiveMerchantNotActivated' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'live_enable',
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 0,
                'failed'    => 1,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ]
    ],

    'testEditBulkEnableLiveMerchantSuspended' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'live_enable',
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 0,
                'failed'    => 1,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ]
    ],

    'testBulkEnableLiveWithoutPermissionFail' => [
        'request'   => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044', '10000000000055'],
                'action'       => "live_enable",
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
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

    'testPreferencesToCheckDisabledSbibuddyWallet' => [
        'request'  => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCheck2FAException' => [
        'request'   => [
            'url'     => '/account/config/email',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id'    => '20000000000000',
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
            'content' => [
                'transaction_report_email' => [
                    'test@email.com'
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
            'description'         => PublicErrorDescription::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
        ],
    ],

    'testCheck2FACorrectOTP' => [
        'request'  => [
            'url'     => '/account/config/email',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id'    => '20000000000000',
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
            'content' => [
                'transaction_report_email' => [
                    'test@email.com',
                ],
                'otp'                      => '0007',
                'token'                    => 'HXB27fsBvwvyyw'
            ],
        ],
        'response' => [
            'content' => [
                'transaction_report_email' => [
                    'test@email.com',
                ],
            ],
        ],
    ],

    'testCheck2FAIncorrectOTP'      => [
        'request'   => [
            'url'     => '/account/config/email',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id'    => '20000000000000',
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
            'content' => [
                'transaction_report_email' => [
                    'test@email.com',
                ],
                'otp'                      => '0008',
                'token'                    => 'HXB27fsBvwvyyw'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'internal_error_code' => ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
            'description'         => PublicErrorDescription::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
        ],
    ],
    'testToggleFeeBearerToCustomer' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/toggle_fee_bearer',
            'content' => [
                'fee_bearer' => 'customer',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ]
    ],

    'testToggleFeeBearerToPlatform' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/toggle_fee_bearer',
            'content' => [
                'fee_bearer' => 'platform',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ]
    ],

    'testToggleFeeBearerFailForCustomer' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/merchant/toggle_fee_bearer',
            'content' => [
                'fee_bearer' => 'customer',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The new fee bearer is same as the previous fee bearer'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testToggleFeeBearerToDynamicFail' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/merchant/toggle_fee_bearer',
            'content' => [
                'fee_bearer' => 'dynamic',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected fee bearer is invalid.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditBulkEnableLiveRiskTaggedMerchant' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'live_enable',
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 1,
                'failed'    => 0,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ]
    ],

    'testEditBulkEnableLiveRiskTaggedMerchantWithoutPermission' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'live_enable',
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 0,
                'failed'    => 1,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ]
    ],

    'testEditBulkUnsuspendRiskTaggedMerchant' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'unsuspend',
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 1,
                'failed'    => 0,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ]
    ],

    'testEditBulkUnsuspendRiskTaggedMerchantWithoutPermission' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'unsuspend',
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
                'total'     => 1,
                'success'   => 0,
                'failed'    => 1,
                'failedIds' => [],
            ],
            'status_code' => 200,
        ]
    ],

    'testRiskTaggedMerchantUnsuspendRiskTagged' => [
        'request'  => [
            'content' => [
                'action' => 'unsuspend'
            ],
            'url'     => '/merchants/%s/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => ['suspended_at' => null],
            'status_code' => 200
        ]
    ],

    'testRiskTaggedMerchantUnsuspendRiskTaggedWithoutPermission' => [
        'request'  => [
            'content' => [
                'action' => 'unsuspend'
            ],
            'url'     => '/merchants/%s/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => ['suspended_at' => null],
            'status_code' => 200
        ]
    ],

    'testRiskTaggedMerchantReleaseFundsWithWorkflow' => [
        'request' => [
            'content' => [
                'action' => 'release_funds'
            ],
            'url'     => '/merchants/%s/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'workflow' => [
                    'name' => "Release Funds",
                ],
            ],
        ],
    ],

    'testRiskTaggedMerchantReleaseFundsWithWorkflowWithoutPermission' => [
        'request' => [
            'content' => [
                'action' => 'release_funds'
            ],
            'url'     => '/merchants/%s/action',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'workflow' => [
                    'name' => "Release Funds",
                ],
            ],
        ],
    ],

    'testBulkAssignTag' => [
        'request'  => [
            'content' => [
                'name'         => 'test',
                'action'       => 'insert',
                'merchant_ids' => [
                    '10000000000000',
                    '10000000000000',
                    'randInvalid_Id',
                ],
            ],
            'url'     => '/merchants/tags/bulk',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'total_count'  => 3,
                'failed_count' => 1,
                'failed_ids'   => [
                    'randInvalid_Id',
                ],
            ],
        ],
    ],

    'testBulkAssignRiskTagAndSetFraudType' => [
        'request'  => [
            'content' => [
                'name'         => 'risk_review_suspend',
                'action'       => 'insert',
                'merchant_ids' => [
                    '10000000000000',
                    'randInvalid_Id',
                ],
            ],
            'url'     => '/merchants/tags/bulk',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'total_count'  => 2,
                'failed_count' => 1,
                'failed_ids'   => [
                    'randInvalid_Id',
                ],
            ],
        ],
    ],

    'testEditBulkEnableLiveNewFlow' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids'    => ['10000000000044'],
                'action'          => 'live_enable',
                'risk_attributes' => [
                    'clear_risk_tags' => '1',
                ],
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ]
    ],

    'testEditBulkEnableLiveNewFlowWithoutPermission' => [
        'request'   => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids'    => ['10000000000044'],
                'action'          => 'live_enable',
                'risk_attributes' => [
                    'clear_risk_tags'       => '1',
                    'trigger_communication' => '1',
                ]
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
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

    'testEditBulkSuspendMerchantNewFlow' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids'    => ['10000000000044'],
                'action'          => 'suspend',
                'risk_attributes' => [
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                    'risk_source'           => 'high_fts',
                    'risk_tag'              => 'risk_review_watchlist',
                    'trigger_communication' => '1'
                ]
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ]
    ],

    'testEditBulkHoldFundsNewFlow' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids'    => ['10000000000044'],
                'action'          => 'hold_funds',
                'risk_attributes' => [
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                    'risk_source'           => 'high_fts',
                    'risk_tag'              => 'risk_review_watchlist',
                    'trigger_communication' => '1'
                ]
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ]
    ],

    'testEditBulkReleaseFundsNewFlow' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids'    => ['10000000000044'],
                'action'          => 'release_funds',
                'risk_attributes' => [
                    'clear_risk_tags' => '1',
                ]
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ]
    ],

    'testEditBulkDisableLiveNewFlowWithCorrectAttributes' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids'    => ['10000000000044', '10000000000004'],
                'action'          => 'live_disable',
                'risk_attributes' => [
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                    'risk_source'           => 'high_fts',
                    'risk_tag'              => 'risk_review_watchlist',
                    'trigger_communication' => '1'
                ]
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ]
    ],

    'testEditBulkDisableLiveNewFlowWithoutRiskAttributes' => [
        'request'   => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'live_disable',
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Risk Attributes are not provided',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testEditBulkDisableLiveNewFlowIncorrectRiskAttributes' => [
        'request'   => [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids'    => ['10000000000044'],
                'action'          => 'live_disable',
                'risk_attributes' => [
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'wrong_sub_reason',
                    'risk_source'           => 'high_fts',
                    'risk_tag'              => 'risk_review_watchlist',
                    'trigger_communication' => '1',
                ]
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'wrong_sub_reason is not a valid risk sub-reason for the following risk reason: chargeback_and_disputes',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testUnregisteredIncreaseTransactionLimitWorkflowApprove' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/transaction_limit',
            'content' => [
                'new_transaction_limit_by_merchant' => 1000000,
                'transaction_limit_increase_reason' => 'comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason'
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ]
    ],

    'testIncreaseTransactionLimitRoleFailure' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/transaction_limit',
            'content' => [
                'new_transaction_limit_by_merchant' => 1000000,
                'transaction_limit_increase_reason' => 'comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason'
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
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
    ],

    'testIncreaseTransactionMerchantActivationFailure' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/merchant/transaction_limit',
            'content' => [
                'new_transaction_limit_by_merchant' => 1000000,
                'transaction_limit_increase_reason' => 'comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason'
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED,
        ],
    ],

    'testMerchantWorkflowDetailForMerchantWorkflowType' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/merchant/{workflowType}/details',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content'     => [
                'workflow_exists'          => true,
                'workflow_status'          => 'rejected',
                'rejection_reason_message' => 'Test body',
                'needs_clarification'      => null,
            ],
            'status_code' => 200,
        ],
    ],

    'testGetCheckoutPreferencesWithFeeConfigNull' => [
        'request'  => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => null

            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR'
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithFeeConfigEmpty' => [
        'request'  => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => [

                ]
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR'
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithFeeConfigEmptyRules' => [
        'request'  => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => [
                    'rules' => []
                ]
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => [

                ]
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithFeeConfigWithPayeeCustomerForUPI' => [
        'request'  => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => [
                    'rules' => [
                        [
                            'method' => 'upi',
                            'fee'    => [
                                'payee'      => 'customer',
                                'flat_value' => 200
                            ]
                        ],
                        [
                            'method' => 'netbanking',
                            'fee'    => [
                                'payee'            => 'customer',
                                'percentage_value' => "20.98"
                            ]
                        ],
                        [
                            'method' => 'wallet',
                            'fee'    => [
                                'payee'      => 'business',
                                'flat_value' => 100
                            ]
                        ],
                        [
                            'method' => 'card',
                            'fee'    => [
                                'payee'            => 'business',
                                'percentage_value' => "12.00"
                            ]
                        ]
                    ]
                ]
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => [
                    'rules' => [
                        [
                            'method' => 'upi',
                            'fee'    => [
                                'payee'      => 'customer',
                                'flat_value' => 200
                            ]
                        ],
                        [
                            'method' => 'netbanking',
                            'fee'    => [
                                'payee'            => 'customer',
                                'percentage_value' => "20.98"
                            ]
                        ],
                        [
                            'method' => 'wallet',
                            'fee'    => [
                                'payee'      => 'business',
                                'flat_value' => 100
                            ]
                        ],
                        [
                            'method' => 'card',
                            'fee'    => [
                                'payee'            => 'business',
                                'percentage_value' => "12.00"
                            ]
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithFeeConfigPayeeCustomerForCardTypes' => [
        'request'  => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => [
                    'rules' => [
                        [
                            'method'    => 'card',
                            'card.type' => ['debit', 'prepaid'],
                            'fee'       => [
                                'payee'      => 'customer',
                                'flat_value' => 200
                            ]
                        ],
                        [
                            'method'    => 'card',
                            'card.type' => ['credit'],
                            'fee'       => [
                                'payee'      => 'customer',
                                'flat_value' => 100
                            ]
                        ]
                    ]
                ]
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => [
                    'rules' => [
                        [
                            'method'    => 'card',
                            'card.type' => ['debit', 'prepaid'],
                            'fee'       => [
                                'payee'      => 'customer',
                                'flat_value' => 200
                            ]
                        ],
                        [
                            'method'    => 'card',
                            'card.type' => ['credit'],
                            'fee'       => [
                                'payee'      => 'customer',
                                'flat_value' => 100
                            ]
                        ]
                    ]
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithFeeConfigPayeeCustomerForCardAndDebitType' => [
        'request'  => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => [
                    'rules' => [
                        [
                            'method' => 'card',
                            'fee'    => [
                                'payee'      => 'customer',
                                'flat_value' => 300
                            ]
                        ],
                        [
                            'method'    => 'card',
                            'card.type' => ['credit'],
                            'fee'       => [
                                'payee'      => 'business',
                                'flat_value' => 100
                            ]
                        ],
                        [
                            'method'    => 'card',
                            'card.type' => ['debit'],
                            'fee'       => [
                                'payee'      => 'customer',
                                'flat_value' => 200
                            ]
                        ]
                    ]
                ]
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => [
                    'rules' => [
                        [
                            'method' => 'card',
                            'fee'    => [
                                'payee'      => 'customer',
                                'flat_value' => 300
                            ]
                        ],
                        [
                            'method'    => 'card',
                            'card.type' => ['credit'],
                            'fee'       => [
                                'payee'      => 'business',
                                'flat_value' => 100
                            ]
                        ],
                        [
                            'method'    => 'card',
                            'card.type' => ['debit'],
                            'fee'       => [
                                'payee'      => 'customer',
                                'flat_value' => 200
                            ]
                        ]
                    ]
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithFeeConfigWithoutPrecalculatedCustomerFee' => [
        'request'  => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => [
                    'rules' => [
                        [
                            'method' => 'netbanking',
                            'fee'    => [
                                'payee'            => 'customer',
                                'percentage_value' => "20.98"
                            ]
                        ],
                        [
                            'method' => 'wallet',
                            'fee'    => [
                                'payee'      => 'business',
                                'flat_value' => 100
                            ]
                        ],
                        [
                            'method' => 'card',
                            'fee'    => [
                                'payee'            => 'business',
                                'percentage_value' => "12.00"
                            ]
                        ]
                    ]
                ]
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => [
                    'rules' => [
                        [
                            'method' => 'netbanking',
                            'fee'    => [
                                'payee'            => 'customer',
                                'percentage_value' => "20.98"
                            ]
                        ],
                        [
                            'method' => 'wallet',
                            'fee'    => [
                                'payee'      => 'business',
                                'flat_value' => 100
                            ]
                        ],
                        [
                            'method' => 'card',
                            'fee'    => [
                                'payee'            => 'business',
                                'percentage_value' => "12.00"
                            ]
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithFeeConfigCardTypesWithoutPrecalculatedCustomerFee' => [
        'request'  => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => [
                    'rules' => [
                        [
                            'method'    => 'card',
                            'card.type' => ['credit'],
                            'fee'       => [
                                'payee'            => 'customer',
                                'percentage_value' => "20.98"
                            ]
                        ],
                        [
                            'method'    => 'card',
                            'card.type' => ['debit'],
                            'fee'       => [
                                'payee'      => 'business',
                                'flat_value' => 100
                            ]
                        ],
                        [
                            'method'    => 'card',
                            'card.type' => ['prepaid'],
                            'fee'       => [
                                'payee'            => 'business',
                                'percentage_value' => "12.00"
                            ]
                        ]
                    ]
                ]
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'                 => 1000,
                'currency'               => 'INR',
                'convenience_fee_config' => [
                    'rules' => [
                        [
                            'method'    => 'card',
                            'card.type' => ['credit'],
                            'fee'       => [
                                'payee'            => 'customer',
                                'percentage_value' => "20.98"
                            ]
                        ],
                        [
                            'method'    => 'card',
                            'card.type' => ['debit'],
                            'fee'       => [
                                'payee'      => 'business',
                                'flat_value' => 100
                            ]
                        ],
                        [
                            'method'    => 'card',
                            'card.type' => ['prepaid'],
                            'fee'       => [
                                'payee'            => 'business',
                                'percentage_value' => "12.00"
                            ]
                        ]
                    ]
                ],
            ],
        ],
    ],

    'testBulkWfActionExecution' => [
        'request'  => [
            'url'     => '/bulk-actions/execute_bulk_action',
            'method'  => 'post',
            'content' => [
                'merchant_ids'    => ['10000000000044', '10000000000004'],
                'action_id'       => '{action_id}',
                'action'          => 'live_disable',
                'risk_attributes' => [
                    'risk_reason'           => 'chargeback_and_disputes',
                    'risk_sub_reason'       => 'high_fts',
                    'risk_source'           => 'high_fts',
                    'risk_tag'              => 'risk_review_watchlist',
                    'trigger_communication' => '1'
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testUpdateFetchCouponsURL' => [
        'request'  => [
            'url'     => '/merchant/coupons/url',
            'method'  => 'post',
            'content' => [
                'url' => 'https://www.example.com',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 201
        ],
    ],

    'testUpdateShippingInfoURL' => [
        'request'  => [
            'url'     => '/merchant/shipping_info/url',
            'method'  => 'post',
            'content' => [
                'url' => 'http://fake.url',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 201
        ],
    ],

    'testUpdateCouponValidityURL' => [
        'request'  => [
            'url'     => '/merchant/coupon/apply/url',
            'method'  => 'post',
            'content' => [
                'url' => 'https://www.example.com',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 201
        ],
    ],

    'testUpdateMerchantPlatform' => [
        'request'  => [
            'url'     => '/merchant/1cc_platform',
            'method'  => 'post',
            'content' => [
                'platform' => 'native',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 201
        ],
    ],

    'testUpdateMerchantPlatformInvalidBody' => [
        'request'  => [
            'url'     => '/merchant/1cc_platform',
            'method'  => 'post',
            'content' => [
                'platform' => 'INVALID_TYPE',
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testFetchCoupons' => [
        'request'  => [
            'url'     => '/merchant/coupons',
            'method'  => 'post',
            'content' => [
                'contact'       => '+92153524643',
                'email'         => 'nin@osaga.com',
                'mock_response' => [
                    'body' => [
                        'promotions' => [
                            [
                                'code'        => 'rqrqw',
                                'summary'     => 'short summary',
                                'description' => 'long description- One time ',
                                'tnc'         => [
                                    'dagdasga',
                                    'sahhqw'
                                ],
                            ],
                            [
                                'code'        => 'adgaga',
                                'summary'     => 'short summary',
                                'description' => 'long description- TWO time ',
                                'tnc'         => [
                                    'dagdasga',
                                    'sahhqw'
                                ],
                            ],
                        ],
                    ]],
            ],
        ],
        'response' => [
            'content' => [
                'promotions' => [
                    [
                        'code'        => 'rqrqw',
                        'summary'     => 'short summary',
                        'description' => 'long description- One time ',
                        'tnc'         => [
                            'dagdasga',
                            'sahhqw'
                        ],
                    ],
                    [
                        'code'        => 'adgaga',
                        'summary'     => 'short summary',
                        'description' => 'long description- TWO time ',
                        'tnc'         => [
                            'dagdasga',
                            'sahhqw'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchCouponsNoCouponsAvailable' => [
        'request'  => [
            'url'     => '/merchant/coupons',
            'method'  => 'post',
            'content' => [
                'contact'       => '+92153524643',
                'email'         => 'nin@osaga.com',
                'mock_response' => [
                    'body' => ['promotions' => [],]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'promotions' => [],
            ],
        ],
    ],

    'testFetchCouponsURLNotConfigured' => [
        'request'   => [
            'url'     => '/merchant/coupons',
            'method'  => 'post',
            'content' => [
                'contact' => '+92153524643',
                'email'   => 'nin@osaga.com',
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FETCH_COUPONS_URL_NOT_CONFIGURED,
        ],
    ],

    'testApplyCouponValidCoupon' => [
        'request'  => [
            'url'     => '/merchant/coupon/apply',
            'method'  => 'post',
            'content' => [
                'contact'       => '1234567890',
                'email'         => 'email@gmail.com',
                'code'          => 'etwqyr',
                'mock_response' => [
                    'body'        => [
                        'promotion' => [
                            'reference_id' => 'ref1',
                            'code'         => 'etwqyr',
                            'value'        => 200,
                        ],
                    ],
                    'status_code' => 200
                ],
            ],
        ],
        'response' => [
            'content' => [
                'promotions' => [
                    [
                        'reference_id' => 'ref1',
                        'code'         => 'etwqyr',
                        'value'        => 200,
                    ]
                ]
            ],
        ],
    ],

    'testApplyCouponInvalidCoupon' => [
        'request'  => [
            'url'     => '/merchant/coupon/apply',
            'method'  => 'post',
            'content' => [
                'contact'       => '+92153524643',
                'email'         => 'nin@osaga.com',
                'code'          => 'etwqyr',
                'mock_response' => [
                    'body'        => [
                        'failure_code'   => 'INVALID_COUPON',
                        'failure_reason' => 'Coupon Code has expired',
                    ],
                    'status_code' => 400,
                ]
            ],
        ],
        'response' => [
            'content'     => [
                'failure_code'   => 'INVALID_COUPON',
                'failure_reason' => 'Coupon Code has expired',
            ],
            'status_code' => 400,
        ],
    ],

    'testCouponValidityURLNotConfigured' => [
        'request'   => [
            'url'     => '/merchant/coupon/apply',
            'method'  => 'post',
            'content' => [
                'contact' => '+92153524643',
                'email'   => 'nin@osaga.com',
                'code'    => 'etwqyr'
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
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_COUPON_VALIDITY_URL_NOT_CONFIGURED,

        ],
    ],

    'testOneClickCheckoutStatus'    => [
        'request'  => [
            'url'     => '/merchant/checkout_details',
            'method'  => 'POST',
            'content' => [
                'status_1cc' => 'waitlisted'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'status_1cc'  => 'waitlisted'
            ],
        ],
    ],
    'testGetOneClickCheckoutStatus' => [
        'request'  => [
            'url'    => '/merchant/checkout_details',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'status_1cc'  => 'live'
            ],
        ],
    ],

    'testOneClickCheckoutStatusFeatureNotPresent' => [
        'request'   => [
            'url'     => '/merchant/checkout_details',
            'method'  => 'POST',
            'content' => [
                'status_1cc' => 'waitlisted'
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
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_ELIGIBLE_FOR_1CC,
        ],
    ],

    'testGetCheckoutPreferenceWithOutUserConsentTokenisation' => [
        'request' => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'customer' => [
                    'tokens' => [
                        'items' => [
                            [
                                'consent_taken' => false,
                                'card' => [
                                    'type'      => 'credit',
                                    'issuer'    => 'HDFC',
                                    'network'   => 'Visa',
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferenceWithUserConsentTokenisation' => [
        'request' => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'customer' => [
                    'tokens' => [
                        'items' => [
                            [
                                'consent_taken' => true,
                                'card' => [
                                    'type'      => 'credit',
                                    'issuer'    => 'HDFC',
                                    'network'   => 'Visa',
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferenceWithUserConsentTokenisationWithCustomerId' => [
        'request' => [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'customer' => [
                    'tokens' => [
                        'items' => [
                            [
                                'consent_taken' => true,
                                'card' => [
                                    'type'      => 'credit',
                                    'issuer'    => 'HDFC',
                                    'network'   => 'Visa',
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ],
    ],
    'testUpdateMerchant1ccShopifyConfig' => [
        'request' => [
            'url' => '/merchant/1cc/shopify/config',
            'method' => 'post',
            'content' => [
              "merchant_id" => "10000000000000",
              "shop_id" => "hias",
              "api_key" => "abasc",
              "api_secret" => "def",
              "oauth_token" => 'secret',
              "storefront_access_token" => "ghi"
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200
        ],
    ],

    'testUpdateMerchant1ccShopifyConfigInvalidBody' => [
        'request' => [
            'url' => '/merchant/1cc/shopify/config',
            'method'  => 'post',
            'content' => [
                "merchant_id" => "10000000000000",
                "api_key" => "abasc",
                "api_secret" => "def",
                "storefront_access_token" => "ghi"
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
];
