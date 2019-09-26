<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
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
                ]
            ],
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
                'international' => '1',
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
                'international' => true,
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
                'international' => '1',
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
                'international' => '1',
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
                'international' => true,
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
            'content' => [],
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

    'testMerchant2faDisable' => [
        'request' => [
            'url'     => '/merchants/2fa',
            'method'  => 'PATCH',
            'content' => [],
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
            ]
        ]
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
                    'VIJB' => 'Vijaya Bank',
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

    'testGetCheckoutPreferencesWithMerchantSpecificAndSharedOffers' => [
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
                'count' => 31,
                'items' => [
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'ABPB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'AUBL',
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
                            'issuer' => 'BDBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BKDN',
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
                            'issuer' => 'SURY',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SVCB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SYNB',
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
                'count' => 32,
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
                            'issuer' => 'ABPB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'AUBL',
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
                            'issuer' => 'BDBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BKDN',
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
                            'issuer' => 'SURY',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SVCB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SYNB',
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
                                'ABPB',
                                'AUBL',
                                'BACB',
                                'BBKM',
                                'BDBL',
                                'BKDN',
                                'COSB',
                                'ESAF',
                                'JSBP',
                                'KCCB',
                                'KJSB',
                                'MSNU',
                                'NESF',
                                'NKGS',
                                'SURY',
                                'SVCB',
                                'SYNB',
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

    'testPutEmiMethod' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content' => [
                'emi' => true,
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

    'testGetKeySecret' => [
        'request' => [
            'url' => '/keys/rzp_test_TheTestAuthKey/secret',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'secret' => 'TheKeySecretForTests'
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
                    'description' => 'DB Query Failed',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'   => Razorpay\OAuth\Exception\DBQueryException::class,
            'message' => 'DB Query Failed',
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
                'message'      => 'Call queued successfully',
                'reference_id' => '1000000000000000',
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
            'content' => [
                'international'     => true,
                'convert_currency'  => false,
            ],
            'status_code' => 200,
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
                'convert_currency'  => null,
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
];
