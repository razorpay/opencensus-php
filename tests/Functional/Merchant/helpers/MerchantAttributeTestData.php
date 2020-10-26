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
                ]
            ],
            'url' => '/merchant/preferences/x_merchant_preferences',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'type' => 'business_category',
                    'value' => 'Education'
                ],
                [
                    'type' => 'team_size',
                    'value' => '200+'
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
    ]
];
