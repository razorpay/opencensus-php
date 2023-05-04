<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testSwitchProductScenario' => [
        'request'  => [
            'url'     => '/merchants/product-switch',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testSignupScenario' => [
        'request'  => [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
                'name'  => 'Tester',
                'email' => 'test@localhost.com',
            ],
            'url'     => '/merchants',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'id'                       => '1X4hRFHFx4UiXt',
                'name'                     => 'Tester',
                'email'                    => 'test@localhost.com',
                'pricing_plan_id'          => '1In3Yh5Mluj605',
                'live'                     => false,
                'activated'                => false,
                'hold_funds'               => false,
                'brand_color'              => null,
                'activated_at'             => null,
                'receipt_email_enabled'    => true,
                'transaction_report_email' => [
                    'test@localhost.com'
                ]
            ],
        ],
    ],

    'testMerchantOnboardingCategoryCron' => [
        'request' => [
            'content' => [
                'days' => 0
            ],
            'url' => '/merchants/onboarding_category/normal',
            'method' => 'POST'
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testMerchantAddingNewPreferences' => [
        'request' => [
            'content' => [
                [
                    'type' => 'business_category',
                    'value' => 'Education'
                ],
                [
                    'type' => 'team_size',
                    'value' => '200+'
                ],
                [
                    'type'  => 'nft_project',
                    'value' => 'true'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_preferences',
            'method' => 'POST'
        ],
        'response' => [
            // response is sorted in alphabetic order
            'content' => [
                [
                    'type' => 'business_category',
                    'value' => 'Education'
                ],
                [
                    'type'  => 'nft_project',
                    'value' => 'true'
                ],
                [
                    'type' => 'team_size',
                    'value' => '200+'
                ]
            ]
        ],
    ],

    'testMerchantPreferencesViaAdminAuth' => [
        'request'  => [
            'content' => [
                'preferences' => [
                    [
                        'type'  => 'ca_proceeded_bank',
                        'value' => 'ICICI'
                    ],
                    [
                        'type'  => 'ca_allocated_bank',
                        'value' => 'ICICI'
                    ]
                ],
                'merchant_id' => '10000000000000',
                'product'     => 'banking',
            ],
            'url'     => '/admin/merchant/preferences/x_merchant_current_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'type'  => 'ca_allocated_bank',
                    'value' => 'ICICI'
                ],
                [
                    'type'  => 'ca_proceeded_bank',
                    'value' => 'ICICI'
                ]
            ]
        ],
    ],

    'testMerchantPreferencesBulkViaAdminAuth' => [
        'request'  => [
            'content' => [
                'preferences' => [
                    [
                        'type'  => 'ca_onboarding_flow',
                        'value' => 'sales_lead'
                    ]
                ],
                'merchant_ids' => ['10000000000000', '1cXSLlUU8V9sXl'],
                'product'     => 'banking',
            ],
            'url'     => '/admin/merchant/preferences/bulk/x_merchant_current_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ],
    ],

    'testMerchantAddingNewPreferencesForIntent' => [
        'request' => [
            'content' => [
                [
                    'type'  => 'current_account',
                    'value' => 'true'
                ],
                [
                    'type'  => 'vendor_payments',
                    'value' => 'true'
                ],
                [
                    'type'  => 'instant_settlements',
                    'value' => 'true'
                ],
                [
                    'type'  => 'demo_onboarding',
                    'value' => 'true'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_intent',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                [
                    'type'  => 'current_account',
                    'value' => 'true'
                ],
                [
                    'type'  => 'demo_onboarding',
                    'value' => 'true'
                ],
                [
                    'type'  => 'instant_settlements',
                    'value' => 'true'
                ],
                [
                    'type'  => 'vendor_payments',
                    'value' => 'true'
                ]
            ]
        ],
    ],

    'testMerchantAddingNewPreferencesForMob' => [
        'request' => [
            'content' => [
                [
                    'type'  => 'corporate_cards',
                    'value' => 'true'
                ],
            ],
            'url' => '/merchant/preferences/x_merchant_intent',
            'method' => 'POST',
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testMerchantAddingNewPreferencesForIntentLos' => [
        'request' => [
            'content' => [
                [
                    'type'  => 'corporate_cards',
                    'value' => 'true'
                ],
                [
                    'type'  => 'marketplace_is',
                    'value' => 'true'
                ],
                [
                    'type'  => 'vendor_payments',
                    'value' => 'true'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_intent',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                [
                    'type'  => 'corporate_cards',
                    'value' => 'true'
                ],
                [
                    'type'  => 'marketplace_is',
                    'value' => 'true'
                ],
                [
                    'type'  => 'vendor_payments',
                    'value' => 'true'
                ]
            ]
        ],
    ],

    'testMerchantRemovingPreferencesForIntentLosExisting' => [
        'request' => [
            'content' => [
                [
                    'type'  => 'corporate_cards',
                    'value' => 'false'
                ],
                [
                    'type'  => 'marketplace_is',
                    'value' => 'false'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_intent',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                [
                    'type'  => 'corporate_cards',
                    'value' => 'false'
                ],
                [
                    'type'  => 'marketplace_is',
                    'value' => 'false'
                ]
            ]
        ],
    ],

    'testMerchantRemovingPreferencesForIntentLosNonExisting' => [
        'request' => [
            'content' => [
                [
                    'type'  => 'corporate_cards',
                    'value' => 'false'
                ],
                [
                    'type'  => 'marketplace_is',
                    'value' => 'false'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_intent',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                [
                    'type'  => 'corporate_cards',
                    'value' => 'false'
                ],
                [
                    'type'  => 'marketplace_is',
                    'value' => 'false'
                ]
            ]
        ],
    ],

    'testMerchantAddingNewPreferencesForSource' => [
        'request' => [
            'content' => [
                [
                    'type' => 'pg',
                    'value' => 'true'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_source',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'pg',
                    'value' => 'true'
                ]
            ]
        ],
    ],

    'testMerchantUpsertingPreferences' => [
        'request' => [
            'content' => [
                [
                    'type' => 'business_category',
                    'value' => 'School'
                ],
                [
                    'type' => 'monthly_payout_count',
                    'value' => '1000'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_preferences',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'business_category',
                    'value' => 'School'
                ],
                [
                    'type' => 'monthly_payout_count',
                    'value' => '1000'
                ]
            ]
        ],
    ],

    'testMerchantUpsertingForXTransactions' => [
        'request' => [
            'content' => [
                    [
                        'type' => 'admin',
                        'value' => 'true'
                    ],
                    [
                        'type' => 'operations',
                        'value' => 'false'
                    ],
                    [
                        'type' => 'finance_l1',
                        'value' => 'false'
                    ]
            ],
            'url' => '/merchant/preferences/x_transaction_view',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'admin',
                    'value' => 'true'
                ],
                [
                    'type' => 'finance_l1',
                    'value' => 'false'
                ],
                [
                    'type' => 'operations',
                    'value' => 'false'
                ],
            ],
        ],
    ],

    'testMerchantUpsertingPreferencesForCa' => [
        'request' => [
            'content' => [
                [
                    'type' => 'ca_allocated_bank',
                    'value' => 'true'
                ],
                [
                    'type' => 'ca_proceeded_bank',
                    'value' => 'true'
                ],
                [
                    'type' => 'ca_onboarding_survey_count',
                    'value' => '3'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_current_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'ca_allocated_bank',
                    'value' => 'true'
                ],
                [
                    'type' => 'ca_onboarding_survey_count',
                    'value' => '3'
                ],
                [
                    'type' => 'ca_proceeded_bank',
                    'value' => 'true'
                ],
            ]
        ],
    ],

    'testMerchantUpsertingPreferencesForIntent' => [
        'request' => [
            'content' => [
                [
                    'type' => 'current_account',
                    'value' => 'false'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_intent',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'current_account',
                    'value' => 'false'
                ]
            ]
        ],
    ],

    'testMerchantUpsertingPreferencesForSource' => [
        'request' => [
            'content' => [
                [
                    'type' => 'pg',
                    'value' => 'false'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_source',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'pg',
                    'value' => 'false'
                ]
            ]
        ],
    ],

    'testMerchantPreferencesWithWrongGroup' => [
        'request' => [
            'content' => [
                [
                    'type' => 'business_category',
                    'value' => 'School'
                ],
                [
                    'type' => 'monthly_payout_count',
                    'value' => '1000'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_preferences_wrong',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid Group: x_merchant_preferences_wrong AND/OR Invalid Type: business_category',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantPreferencesWithWrongType' => [
        'request' => [
            'content' => [
                [
                    'type' => 'business_category',
                    'value' => 'School'
                ],
                [
                    'type' => 'monthly_payout_count_wrong',
                    'value' => '1000'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_preferences',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid Group: x_merchant_preferences AND/OR Invalid Type: monthly_payout_count_wrong',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantPreferencesWithWrongTypeForIntent' => [
        'request' => [
            'content' => [
                [
                    'type' => 'current_account_wrong',
                    'value' => 'true'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_intent',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid Group: x_merchant_intent AND/OR Invalid Type: current_account_wrong',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantPreferencesWithWrongTypeForSource' => [
        'request' => [
            'content' => [
                [
                    'type' => 'pg_wrong',
                    'value' => 'true'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_source',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid Group: x_merchant_source AND/OR Invalid Type: pg_wrong',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantPreferencesMissingType' => [
        'request' => [
            'content' => [
                [
                    'value' => 'School'
                ],
                [
                    'type' => 'monthly_payout_count',
                    'value' => '1000'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_preferences',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The type field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],

    ],

    'testMerchantPreferencesMissingValue' => [
        'request' => [
            'content' => [
                [
                    'type' => 'business_category',
                ],
                [
                    'type' => 'monthly_payout_count',
                    'value' => '1000'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_preferences',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The value field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

   'testMerchantGetPreferencesByGroup' => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'url' => '/merchant/preferences/x_merchant_preferences',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'business_category',
                    'value' => 'School'
                ],
                [
                    'type'  => 'nft_project',
                    'value' => 'true'
                ]
            ]
        ],
    ],

    'testMerchantGetPreferencesByGroupForIntent' => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'url' => '/merchant/preferences/x_merchant_intent',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'current_account',
                    'value' => 'false'
                ],
                [
                    'type'  => 'demo_onboarding',
                    'value' => 'true'
                ],
                [
                    'type' => 'tax_payments',
                    'value' => 'true'
                ],
            ]
        ],
    ],

    'testMerchantGetPreferencesByGroupForIntentOrderByCreation' => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'url' => '/merchant/preferences/x_merchant_intent',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'tax_payments',
                    'value' => 'true'
                ],
                [
                    'type' => 'current_account',
                    'value' => 'false'
                ],
            ]
        ],
    ],

    'testMerchantGetPreferencesByGroupForSource' => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'url' => '/merchant/preferences/x_merchant_source',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'pg',
                    'value' => 'false'
                ],
                [
                    'type' => 'website',
                    'value' => 'true'
                ],
            ]
        ],
    ],

    'testMerchantGetUndoPayoutPreferencesByGroup' => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'url' => '/merchant/preferences/x_merchant_preferences',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'undo_payouts',
                    'value' => 'true'
                ]
            ]
        ],
    ],

    'testMerchantGetPreferencesByGroupAndType' => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'url' => '/merchant/preferences/x_merchant_preferences',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'business_category',
                    'value' => 'School'
                ]
            ]
        ],
    ],

    'testMerchantGetPreferencesByGroupAndTypeAdmin' => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'url' => '/admin/merchant/preferences/10000000000000/x_merchant_current_accounts/ca_allocated_bank',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'ca_allocated_bank',
                    'value' => 'ICICI'
                ]
            ]
        ],
    ],

    'testGetPreferencesForDashboardSeenType' => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'url' => '/merchant/preferences/x_merchant_preferences',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'explore_dashboard_button_at_welcome_page_clicked',
                    'value' => 'true'
                ]
            ]
        ],
    ],

    'testPostPreferencesForDashboardSeenType' => [
        'request' => [
            'content' => [
                [
                    'type' => 'explore_dashboard_button_at_welcome_page_clicked',
                    'value' => 'true'
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_preferences',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'explore_dashboard_button_at_welcome_page_clicked',
                    'value' => 'true'
                ]
            ]
        ],
    ],

    'testOnboardMerchantOnNetworkBulkWithLimit' => [
        'request' =>[
            'content' => [
                    'limit' => 1,
            ],
            'url' => '/merchants/onboarding/networks/bulk',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "status" => "successful",
                "total_merchant_ids" => 1,
            ]
        ],
    ],

    'testOnboardMerchantOnNetworkBulkWithArray' => [
        'request' =>[
            'content' => [
                    'limit' => 2,
                    'merchant_ids' => ["1000000Razorpay","Client00123456","10000000000000"],
            ],
            'url' => '/merchants/onboarding/networks/bulk',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "status" => "successful",
                "total_merchant_ids" => 3,
            ]
        ],
    ],
];
