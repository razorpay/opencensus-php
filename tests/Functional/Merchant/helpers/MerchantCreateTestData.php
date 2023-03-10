<?php

use RZP\Error\ErrorCode;
use RZP\Models\Batch\Header;
use RZP\Error\PublicErrorCode;
use RZP\Models\Merchant\Entity;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Balance\BalanceConfig;

return [
    'testCreateMerchantWithDuplicateEmail' => [
        'request'   => [
            'content' => [
                'id'    => 'randommerchant',
                'name'  => 'Random Name',
                'email' => 'test@razorpay.com',
            ],
            'url'     => '/merchants',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EMAIL_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateMerchantWithDuplicateId' => [
        'request'   => [
            'content' => [
                'id'    => '10000000000000',
                'name'  => 'Random Merchant Name',
                'email' => 'test2@razorpay.com',
            ],
            'url'     => '/merchants',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id has already been taken.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateMerchant' => [
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

    'testGetTerminalsInTestForCreatedMerchant' => [
        'request'  => [
            'url'    => '/merchants/1X4hRFHFx4UiXt/terminals',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count'  => 0,
                'entity' => 'collection',
                'items'  => [
                ]
            ]
        ],
    ],

    'testGetTerminalsInLiveForCreatedMerchant' => [
        'request'  => [
            'url'    => '/merchants/1X4hRFHFx4UiXt/terminals',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count'  => 0,
                'entity' => 'collection',
                'items'  => []
            ]
        ],
    ],

    'testBalanceInTestAfterCreatedMerchant' => [
        'request'  => [
            'url'    => '/balance',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'merchant_id'   => '1X4hRFHFx4UiXt',
                'currency'      => 'INR',
                'balance'       => 0,
            ]
        ]
    ],

    'testBalanceInLiveAfterCreatedMerchant' => [
        'request'  => [
            'url'    => '/balance',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id'      => '1X4hRFHFx4UiXt',
                'balance' => 0
            ]
        ],
    ],

    'testGetBankAccountsAfterCreatedMerchant' => [
        'request'  => [
            'url'    => '/merchants/1X4hRFHFx4UiXt/banks',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'enabled'  => [],
                'disabled' => [],
            ]
        ]
    ],

    'testCheckSalesforceGroupForSubmerchantCreate' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'Submerchant',
            ],
        ],
        'response' => [
            'content' => [
                'id'              => 'NewSubmerchant',
                'name'            => 'Submerchant',
                'email'           => 'test@razorpay.com',
                'pricing_plan_id' => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCheckSalesforceGroupForMarketplaceLinkedAccount' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gcKngYfqyDMjN',
                'name'    => 'Linked Account 2',
                'email'   => 'linkedaccount@razorpay.com',
                'account' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'              => '7gcKngYfqyDMjN',
                'name'            => 'Linked Account 2',
                'email'           => 'linkedaccount@razorpay.com',
                'pricing_plan_id' => '1In3Yh5Mluj605',
            ],
        ],
    ],

    'testCreateSubMerchant' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'Submerchant',
            ],
        ],
        'response' => [
            'content' => [
                'id'              => 'NewSubmerchant',
                'name'            => 'Submerchant',
                // Email is same as the test merchant
                'email'           => 'test@razorpay.com',
                'pricing_plan_id' => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateSubMerchantWithRatelimitExceeded' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'Submerchant',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_DAILY_LIMIT_SUBMERCHANT_ONBOARDING_EXCEEDED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_DAILY_LIMIT_SUBMERCHANT_ONBOARDING_EXCEEDED,
        ],
    ],

    'testCreateSubMerchantWithoutFeatureMarketplaceOrPartner' => [
        'request'   => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id' => 'NewSubmerchant',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT,
        ],
    ],

    'testCreateSubMerchantWithoutName' => [
        'request'   => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id' => 'NewSubmerchant',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The name field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubMerchantWrongUserRole' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'new name',
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

    'testCreateSubMerchantWithEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'submerchant@razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'              => 'NewSubmerchant',
                'name'            => 'Submerchant',
                'email'           => 'submerchant@razorpay.com',
                'pricing_plan_id' => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testSegmentEventSkipPartnerAddedFirstSubmerchant' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'submerchant@razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'              => 'NewSubmerchant',
                'name'            => 'Submerchant',
                'email'           => 'submerchant@razorpay.com',
                'pricing_plan_id' => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testSegmentEventPartnerAddedFirstSubmerchant' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'submerchant@razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'              => 'NewSubmerchant',
                'name'            => 'Submerchant',
                'email'           => 'submerchant@razorpay.com',
                'pricing_plan_id' => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateSubMerchantWithEmailUserExists' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'SubmerchantTwo',
                'email' => 'submerchant@razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'SubmerchantTwo',
                'email' => 'submerchant@razorpay.com',
            ],
        ],
    ],

    'testCreateSubMerchantWithDuplicateEmail' => [
        'request'   => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant 2',
                'email' => 'test2@razorpay.com'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The email has already been taken.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubMerchantByFullyManagedWOEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'Submerchant',
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_NewSubmerchant',
                'name'             => 'Submerchant',
                'email'            => 'test@razorpay.com',
                'details'          => [
                    'activation_status' => null,
                ],
                'user'             => null,
                'dashboard_access' => true,
                'pricing_plan_id'  => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateSubMerchantByFullyManagedWithEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_NewSubmerchant',
                'name'             => 'Submerchant',
                'email'            => 'testsub@razorpay.com',
                'details'          => [
                    'activation_status' => null,
                ],
                'user'             => [
                    'email'     => 'testsub@razorpay.com',
                ],
                'dashboard_access' => true,
                'pricing_plan_id'  => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateMalaysianSubMerchantByFullyManagedWithEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_NewSubmerchant',
                'name'             => 'Submerchant',
                'email'            => 'testsub@razorpay.com',
                'details'          => [
                    'activation_status' => null,
                ],
                'user'             => [
                    'email'     => 'testsub@razorpay.com',
                ],
                'dashboard_access' => true,
                'pricing_plan_id'  => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateSubMerchantForXByFullyManagedWithEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => 'NewSubmerchant',
                'name'    => 'Submerchant',
                'email'   => 'testsub@razorpay.com',
                'product' => 'banking'
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_NewSubmerchant',
                'name'             => 'Submerchant',
                'email'            => 'testsub@razorpay.com',
                'details'          => [
                    'activation_status' => null,
                ],
                'user'             => [
                    'email'     => 'testsub@razorpay.com',
                ],
                'dashboard_access' => true,
                'pricing_plan_id'  => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateSubMerchantByFullyManagedWithEmailUserExists' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => 'NewSubmerchant',
                'name'    => 'Submerchant',
                'email'   => 'testsub@razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_NewSubmerchant',
                'name'             => 'Submerchant',
                'email'            => 'testsub@razorpay.com',
                'details'          => [
                    'activation_status' => null,
                ],
                'user'             => [
                    'email'     => 'testsub@razorpay.com',
                ],
                'dashboard_access' => true,
                'pricing_plan_id'  => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateSubMerchantForXByFullyManagedWithEmailUserExists' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => 'NewSubmerchant',
                'name'    => 'Submerchant',
                'email'   => 'testsub@razorpay.com',
                'product' => 'banking'
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_NewSubmerchant',
                'name'             => 'Submerchant',
                'email'            => 'testsub@razorpay.com',
                'details'          => [
                    'activation_status' => null,
                ],
                'user'             => [
                    'email'     => 'testsub@razorpay.com',
                ],
                'dashboard_access' => true,
                'pricing_plan_id'  => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateSubMerchantByAggregatorWithEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_NewSubmerchant',
                'name'             => 'Submerchant',
                'email'            => 'testsub@razorpay.com',
                'details'          => [
                    'activation_status' => null,
                ],
                'user'             => [
                    'email'     => 'testsub@razorpay.com',
                ],
                'dashboard_access' => true,
                'pricing_plan_id'  => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateMalaysianSubMerchantByAggregatorWithEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_NewSubmerchant',
                'name'             => 'Submerchant',
                'email'            => 'testsub@razorpay.com',
                'details'          => [
                    'activation_status' => null,
                ],
                'user'             => [
                    'email'     => 'testsub@razorpay.com',
                ],
                'dashboard_access' => true,
                'pricing_plan_id'  => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateSubMerchantForXByAggregatorWithEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => 'NewSubmerchant',
                'name'    => 'Submerchant',
                'email'   => 'testsub@razorpay.com',
                'product' => 'banking',
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_NewSubmerchant',
                'name'             => 'Submerchant',
                'email'            => 'testsub@razorpay.com',
                'details'          => [
                    'activation_status' => null,
                ],
                'user'             => [
                    'email'     => 'testsub@razorpay.com',
                ],
                'dashboard_access' => true,
                'pricing_plan_id'  => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateSubMerchantForXByResellerWithEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => 'NewSubmerchant',
                'name'    => 'Submerchant',
                'email'   => 'testsub@razorpay.com',
                'product' => 'banking',
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_NewSubmerchant',
                'name'             => 'Submerchant',
                'email'            => 'testsub@razorpay.com',
                'details'          => [
                    'activation_status' => null,
                ],
                'user'             => [
                    'email'     => 'testsub@razorpay.com',
                ],
                'dashboard_access' => false,
                'pricing_plan_id'  => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateSubMerchantByAggregatorBatch' => [
        'request'  => [
            'url'     => '/submerchants/batch',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Entity-Id' => '10000000000000',
            ],
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com',
                'contact_mobile' => ''
            ],
        ],
        'response' => [
            'content' => [
                'account_id'   => 'acc_NewSubmerchant',
                'account_name' => 'Submerchant',
                'email'        => 'testsub@razorpay.com',
            ],
        ],
    ],

    'testCreateSubMerchantWithMobileNoByAggregatorBatchForX' => [
        'request'  => [
            'url'     => '/submerchants/batch',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Entity-Id' => '10000000000000',
            ],
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com',
                'contact_mobile' => '9876543210',
                'product' => 'banking'
            ],
        ],
        'response' => [
            'content' => [
                'account_id'   => 'acc_NewSubmerchant',
                'account_name' => 'Submerchant',
                'email'        => 'testsub@razorpay.com',
            ],
        ],
    ],

    'testCreateSubMerchantByResellerBatchForLOC' => [
        'request'  => [
            'url'     => '/submerchants/batch',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Entity-Id' => '10000000000000',
            ],
            'content' => [
                "business_name"           => "Erebor Travels",
                "name"                    => "Erebor Travels",
                "contact_mobile"          => "9999999999",
                "email"                   => "testing.capital@razorpay.com",
                "annual_turnover_min"     => "100000",
                "annual_turnover_max"     => "1000000",
                "company_address_line_1"  => "Erebor Travels Pvt. Ltd.",
                "company_address_line_2"  => "Major Industry Area",
                "company_address_city"    => "Akola",
                "company_address_state"   => "Maharashtra",
                "company_address_country" => "IN",
                "company_address_pincode" => "444001",
                "business_type"           => "PROPRIETORSHIP",
                "business_vintage"        => "BETWEEN_6MONTHS_12MONTHS",
                "gstin"                   => "37ABCBS1234N1Z1",
                "promoter_pan"            => "ABCPS1234N",
                'product'                 => 'capital'
            ],
        ],
        'response' => [
            'content' => [
                'account_name' => 'Erebor Travels',
                'email'        => 'testing.capital@razorpay.com',
            ],
        ],
    ],

    'testCreateSubMerchantByResellerBatchForLOCInvalidBusinessType' => [
        'request'  => [
            'url'     => '/submerchants/batch',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Entity-Id' => '10000000000000',
            ],
            'content' => [
                "business_name"           => "Erebor Travels",
                "name"                    => "Erebor Travels",
                "contact_mobile"          => "9999999999",
                "email"                   => "testing.capital@razorpay.com",
                "annual_turnover_min"     => "100000",
                "annual_turnover_max"     => "1000000",
                "company_address_line_1"  => "Erebor Travels Pvt. Ltd.",
                "company_address_line_2"  => "Major Industry Area",
                "company_address_city"    => "Akola",
                "company_address_state"   => "Maharashtra",
                "company_address_country" => "IN",
                "company_address_pincode" => "444001",
                "business_type"           => "N/A",
                "business_vintage"        => "BETWEEN_6MONTHS_12MONTHS",
                "gstin"                   => "37ABCBS1234N1Z1",
                "promoter_pan"            => "ABCPS1234N",
                'product'                 => 'capital'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Invalid business type: n/a",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubMerchantByAggregatorBatchRatelimitExceeded' => [
        'request'  => [
            'url'     => '/submerchants/batch',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Entity-Id' => '10000000000000',
            ],
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_DAILY_LIMIT_SUBMERCHANT_ONBOARDING_EXCEEDED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_DAILY_LIMIT_SUBMERCHANT_ONBOARDING_EXCEEDED,
        ],
    ],

    'testCreateSubMByAggregatorBatchRatelimitExceededNewRatelimiter' => [
        'request'  => [
            'url'     => '/submerchants/batch',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Entity-Id' => '10000000000000',
            ],
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_DAILY_LIMIT_SUBMERCHANT_ONBOARDING_EXCEEDED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_DAILY_LIMIT_SUBMERCHANT_ONBOARDING_EXCEEDED,
        ],
    ],

    'testCreateSubMerchantByAggregatorWithDefaultPaymentMethods' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'Submerchant',
                'email' => 'testsub@razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'id'              => 'acc_NewSubmerchant',
                'name'            => 'Submerchant',
                // Email is same as the test merchant
                'email'           => 'testsub@razorpay.com',
                'pricing_plan_id' => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateSubMerchantByAggregatorWithoutEmail' => [
        'request'   => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'Submerchant',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUBMERCHANT_WITHOUT_EMAIL_NOT_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUBMERCHANT_WITHOUT_EMAIL_NOT_ALLOWED,
        ],
    ],

    'testCreateMalaysianSubMerchantByAggregatorWithoutEmail' => [
        'request'   => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'Submerchant',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUBMERCHANT_WITHOUT_EMAIL_NOT_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUBMERCHANT_WITHOUT_EMAIL_NOT_ALLOWED,
        ],
    ],

    'testCreateSubMerchantByAggregatorWithoutApp' => [
        'request'   => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_PARTNER_APP_NOT_FOUND,
            'message'             => 'Server error app not found',
        ],
    ],

    'testCreateSubMerchantByAggregatorExceptionWithoutEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'Submerchant',
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_NewSubmerchant',
                'name'             => 'Submerchant',
                'email'            => 'test@razorpay.com',
                'details'          => [
                    'activation_status' => null,
                ],
                'user'             => null,
                'dashboard_access' => true,
                'pricing_plan_id'  => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateMarketplaceLinkedAccount' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gcKngYfqyDMjN',
                'name'    => 'Linked Account 2',
                'email'   => 'linkedaccount@razorpay.com',
                'account' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'              => '7gcKngYfqyDMjN',
                'name'            => 'Linked Account 2',
                'email'           => 'linkedaccount@razorpay.com',
                'pricing_plan_id' => '1In3Yh5Mluj605',
            ],
        ],
    ],

    'testCreateMarketplaceLinkedAccountWithDashboardUser' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'               => '7gcKngYfqyDMjN',
                'name'             => 'Linked Account 2',
                'email'            => 'linkedaccount@razorpay.com',
                'account'          => true,
                'dashboard_access' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'    => '7gcKngYfqyDMjN',
                'name'  => 'Linked Account 2',
                'email' => 'linkedaccount@razorpay.com',
            ],
        ],
    ],

    'testLinkedAccountFetchWithoutBusinessType' => [
        'request'       => [
            'url'       =>  '/beta/accounts',
            'method'    => 'GET',
        ],
        'response'      =>  [
            'content'   =>  [
                'entity'    =>  "account",
                'name'      =>  "Linked Account 2",
                'email'     =>  "linkedaccount@razorpay.com"
            ],
        ],
    ],

    'testCreateMarketplaceLinkedAccountWithRefundAllowed' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'                 => '7gcKngYfqyDMjN',
                'name'               => 'Linked Account 2',
                'email'              => 'linkedaccount@razorpay.com',
                'account'            => true,
                'allow_reversals'    => true,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_DASHBOARD_ACCESS_REQUIRED_TO_ALLOW_REVERSALS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_DASHBOARD_ACCESS_REQUIRED_TO_ALLOW_REVERSALS,
        ],
    ],

    'testCreateMarketplaceLinkedAccountWithAlreadyExistingUser' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'name'             => 'Linked Account Name',
                'account'          => true,
                'dashboard_access' => true,
            ],
        ],
        'response' => [
            'content' => [
                'name'  => 'Linked Account Name',
            ],
        ],
    ],

    'testCreateMarketplaceLinkedAccountWithoutEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gcKngYfqyDMjN',
                'name'    => 'Linked Account 2',
                'account' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'    => '7gcKngYfqyDMjN',
                'name'  => 'Linked Account 2',
                'email' => 'test@razorpay.com',
            ],
        ],
    ],

    'testCreateLinkedAccountMaxPaymentLimit' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gcKngYfqyDMjN',
                'name'    => 'Linked Account 4',
                'email'   => 'linkedaccount@razorpay.com',
                'account' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'                 => '7gcKngYfqyDMjN',
                'name'               => 'Linked Account 4',
                'email'              => 'linkedaccount@razorpay.com',
                'max_payment_amount' => 6000
            ],
        ],
    ],

    'testCreateMarketplaceLAWithoutEmailWithPartnerBank' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gcKngYfqyDMjN',
                'name'    => 'Linked Account 2',
                'account' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'   => '7gcKngYfqyDMjN',
                'name' => 'Linked Account 2',
            ],
        ],
    ],

    'testCreateSubMerchantWithoutEmailWithPartnerFMAndMarketplace' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => '7gcKngYfqyDMjN',
                'name' => 'Linked Account 2',
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_7gcKngYfqyDMjN',
                'name'             => 'Linked Account 2',
                'details'          => [
                    'activation_status' => null,
                ],
                'user'             => null,
                'dashboard_access' => true,
                'pricing_plan_id'  => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testCreateMarketplaceLAWithoutEmailWithPartnerFM' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gcKngYfqyDMjN',
                'name'    => 'Linked Account 2',
                'account' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'              => '7gcKngYfqyDMjN',
                'name'            => 'Linked Account 2',
                'pricing_plan_id' => '1In3Yh5Mluj605',
            ],
        ],
    ],

    'testLinkedAccountDefaultSchedule'   => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gbqextd68Co4t',
                'name'    => 'Linked Account 3',
                'email'   => 'linkedaccount@razorpay.com',
                'account' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'    => '7gbqextd68Co4t',
                'name'  => 'Linked Account 3',
                'email' => 'linkedaccount@razorpay.com',
            ],
        ],
    ],

    'testUpdateLinkedAccountEmail' => [
        'request'  => [
            'url'     => '/la-merchants/email',
            'method'  => 'put',
            'content' => [
                'email' => 'testing@testing.com',
            ],
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
        ],
        'response' => [

        ],
    ],

    'testUpdateLinkedAccountEmailTeamUser' => [
        'request'  => [
            'url'     => '/la-merchants/email',
            'method'  => 'put',
            'content' => [
                'email' => 'testing2@testing.com',
            ],
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
        ],
        'response' => [

        ],
    ],

    'testCreateLinkedAccountBatch' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'linked_account',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'linked_account',
                'status'           => 'created',
                'total_count'      => 2,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testCreateLinkedAccountFromBatch' => [
        'request' => [
            'url' => '/linked_accounts/batch',
            'method' => 'post',
            'content' => [
                'account_name'      => 'LA_1',
                'account_email'     => 'la.1@rzp.com',
                'dashboard_access'  => 0,
                'customer_refunds'  => 0,
                'business_name'     => 'Business',
                'business_type'     => 'individual',
                'ifsc_code'         => 'SBIN0000002',
                'account_number'    => '9876543210',
                'beneficiary_name'  => 'Beneficiary'
            ],
        ],
        'response' => [
            'content' => [
                'account_name'      => 'LA_1',
                'account_email'     => 'la.1@rzp.com',
                'dashboard_access'  => '0',
                'customer_refunds'  => '0',
                'business_name'     => 'Business',
                'business_type'     => 'individual',
                'ifsc_code'         => 'SBIN0000002',
                'account_number'    => '9876543210',
                'beneficiary_name'  => 'Beneficiary',
                'account_status'    => 'Activated',
            ],
        ],
    ],

    'testCreateLinkedAccountDashboardAccess' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'dashboard_access' => true,
            ],
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testCreateLinkedAccountDashboardAccessNoEmail' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'dashboard_access' => true,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_EMAIL_LINKED_ACCOUNT_DASHBOARD_ACCESS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_EMAIL_LINKED_ACCOUNT_DASHBOARD_ACCESS,
        ],
    ],

    'testCreateLinkedAccountDashboardAccessRevoke' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'dashboard_access' => false,
            ],
        ],
        'response'  => [
            'content'     => [
                'success' => true,
            ],
        ],
    ],

    'testLinkedAccountDashboardAccessRevokeNoUsers' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'dashboard_access' => false,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_LINKED_ACCOUNT_DASHBOARD_USERS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_LINKED_ACCOUNT_DASHBOARD_USERS,
        ],
    ],

    'testCreateLinkedAccountOnProxyAuth' => [
        'request' => [
            'url' => '/submerchants',
            'method' => 'post',
            'content' => [
                'name'      => 'Bobby Fischer',
                'account'   => true,
                'email'     => 'bobby.fischer@chess.com',
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'merchant',
                'name'      => 'Bobby Fischer',
                'email'     => 'bobby.fischer@chess.com',
                'activated' => false,
                'live'      => false,
            ],
        ],
    ],

    'testUpdateBankAccountForNotActivatedLinkedAccount' => [
        'request' => [
            'method' => 'patch',
            'content' => [
                'beneficiary_name'  => 'Bobby Fischer Junior',
                'account_number'    => '987698769876',
                'ifsc_code'         => 'SBIN0000003',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'class'                 => 'BAD_REQUEST',
                    'internal_error_code'   => ErrorCode::BAD_REQUEST_CANNOT_UPDATE_BANK_ACCOUNT_FOR_LINKED_ACCOUNT_NOT_ACTIVATED,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testUpdateBankAccountForActivatedLinkedAccount' => [
        'request' => [
            'method' => 'patch',
            'content' => [
                'beneficiary_name'  => 'Bobby Fischer Junior',
                'account_number'    => '987698769876',
                'ifsc_code'         => 'SBIN0000003',
            ],
        ],
        'response' => [
            'content' => [
                'status'            => 'activated',
                'beneficiary_name'  => 'Bobby Fischer Junior',
                'account_number'    => '987698769876',
                'ifsc_code'         => 'SBIN0000003',
            ],
        ],
    ],

    'testUpdateBankAccountForLinkedAccountWithoutFeature' => [
        'request' => [
            'method' => 'patch',
            'content' => [
                'beneficiary_name'  => 'Bobby Fischer Junior',
                'account_number'    => '987698769876',
                'ifsc_code'         => 'SBIN0000003',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'class'                 => 'BAD_REQUEST',
                    'internal_error_code'   => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_BANK_ACCOUNT_UPDATE_FEATURE_NOT_ENABLED,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testUpdateBankAccountForLinkedAccountWithPennyTesting' => [
        'request' => [
            'method' => 'patch',
            'content' => [
                'beneficiary_name'  => 'Bobby Fischer Junior',
                'account_number'    => '987698769876',
                'ifsc_code'         => 'SBIN0000003',
            ],
        ],
        'response' => [
            'content' => [
                'status'            => 'verification_pending',
                'beneficiary_name'  => 'Bobby Fischer Junior',
                'account_number'    => '987698769876',
                'ifsc_code'         => 'SBIN0000003',
            ],
        ],
    ],

    'testLinkedAccountDashboardAccessAlreadyGiven' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'dashboard_access' => true,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_LINKED_ACCOUNT_DASHBOARD_ACCESS_ALREADY_GIVEN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_DASHBOARD_ACCESS_ALREADY_GIVEN,
        ],
    ],

    'testBackFillMerchantId' => [
        'request' => [
            'url' => '/merchants/balances/backfill',
            'method' => 'post',
        ],
        'response'  => [
            'content'     => [
                'total'      =>  5,
                'success'    =>  5,
                'failed'     =>  0,
                'failed_ids' => [],
            ],
        ],
    ],

    'testLinkedAccountReversalFeature' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'allow_reversals' => true,
            ],
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testLinkedAccountReversalFeatureRevoke' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'allow_reversals' => false,
            ],
        ],
        'response'  => [
            'content'     => [
                'success' => true,
            ],
        ],
    ],

    'testLinkedAccountReversalFeatureAlreadyGiven' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'allow_reversals' => true,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_LINKED_ACCOUNT_REVERSAL_ABILITY_ALREADY_GIVEN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_REVERSAL_ABILITY_ALREADY_GIVEN,
        ],
    ],

    'testLinkedAccountReversalFeatureAlreadyRemoved' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'allow_reversals' => false,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_LINKED_ACCOUNT_REVERSAL_ABILITY_ALREADY_REMOVED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_REVERSAL_ABILITY_ALREADY_REMOVED,
        ],
    ],

    'testLinkedAccountReversalFeatureNoUsers' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'allow_reversals' => false,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_LINKED_ACCOUNT_DASHBOARD_USERS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_LINKED_ACCOUNT_DASHBOARD_USERS,
        ],
    ],

    'testLinkedAccountDisbaleReversalFeatureAndDashboardAccess' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'dashboard_access' => false,
                'allow_reversals'  => false,
            ],
        ],
        'response'  => [
            'content'     => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testLinkedAccountEnableReversalFeatureAndDashboardAccess' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'dashboard_access' => true,
                'allow_reversals'  => true,
            ],
        ],
        'response'  => [
            'content'     => [
                'success' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateLinkedAccountForMutualFundDistributorMerchant' => [
        'request'   => [
            'url'     => '/beta/accounts',
            'method'  => 'post',
            'content' => [
                'name'            => 'Linked Account 1',
                'email'           => 'linked1@account.com',
                'tnc_accepted'    => true,
                'notes'           => [
                    'custom_account_id' => 'Qwerty123',
                    'custom_attribute'  => 'some_value',
                ],
                'account_details' => [
                    'business_name' => 'Acme solutions',
                    'business_type' => 'proprietorship',
                ],
                'bank_account' => [
                    'ifsc_code'             => 'ICIC0001206',
                    'account_number'        => '0002020000304030434',
                    'account_type'          => 'current',
                    'beneficiary_name'      => 'Test R4zorpay:'
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Linked account creation is not allowed for your business type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CREATION_NOT_ALLOWED,
        ],
    ],

    'testCreateMarketplaceLinkedAccountForMutualFundDistributorMerchant' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gcKngYfqyDMjN',
                'name'    => 'Linked Account 2',
                'email'   => 'linkedaccount@razorpay.com',
                'account' => true,
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Linked account creation is not allowed for your business type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CREATION_NOT_ALLOWED,
        ],
    ],

    'testUpdateLinkedAccountEmailMutualFundDistributorMerchant' => [
        'request' => [
            'url' => '/la-merchants/email',
            'method' => 'put',
            'content' => [
                'email' => 'test+1@razorpay.com'
            ],
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Linked account updation is not allowed for your business type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_UPDATION_NOT_ALLOWED,
        ],
    ],

    'testUpdateLinkedAccountConfigMutualFundDistributorMerchant' => [
        'request' => [
            'url' => '/la-merchants/email',
            'method' => 'put',
            'content' => [
                'allow_reversals'    => true,
                'accountId'          => 'acc_10000000000000'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Linked account updation is not allowed for your business type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_UPDATION_NOT_ALLOWED,
        ],
    ],


    'testUpdateLinkedAccountBankAccountForMutualFundDistributorMerchant' => [
        'request' => [
            'method' => 'patch',
            'content' => [
                'beneficiary_name'  => 'Bobby Fischer Junior',
                'account_number'    => '987698769876',
                'ifsc_code'         => 'SBIN0000003',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Linked account updation is not allowed for your business type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_UPDATION_NOT_ALLOWED,
        ],
    ],

    'testBalanceConfigInTestAfterCreatedMerchant' => [
        'request'  => [
            'url'    => '/balance_configs',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'items' => [
                    '0' => [
                        'type'                          => 'primary',
                        'negative_limit_auto'          => BalanceConfig\Entity::DEFAULT_MAX_NEGATIVE,
                        'negative_limit_manual'        => 0,
                        'negative_transaction_flows'   => ['payment']
                    ]
                ]
            ]
        ]
    ],

    'testCreateSubMerchantByAdminForAggregatorBatch' => [
        'request'  => [
            'url'     => '/submerchants_bulk_onboard/batch',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Entity-Id' => '10000000000000',
            ],
            'content' => [
                Header::MERCHANT_NAME             => 'SubMerchantone',
                Header::MERCHANT_EMAIL            => 'merch1@razorpay.com',
                Header::CONTACT_NAME              => 'merch',
                Header::CONTACT_EMAIL             => 'merch1@razorpay.com',
                Header::CONTACT_MOBILE            => '9302930211',
                Header::TRANSACTION_REPORT_EMAIL  => 'merch1@razorpay.com',
                Header::ORGANIZATION_TYPE         => 3,
                Header::BUSINESS_NAME             => 'sub merch business',
                Header::BILLING_LABEL             => 'acme',
                Header::INTERNATIONAL             => 0,
                Header::PAYMENTS_FOR              => 'business',
                Header::BUSINESS_MODEL            => 'acme',
                Header::BUSINESS_CATEGORY         => 'financial_services',
                Header::BUSINESS_SUB_CATEGORY     => 'lending',
                Header::REGISTERED_ADDRESS        => 'acme',
                Header::REGISTERED_CITY           => 'bangalore',
                Header::REGISTERED_STATE          => 'karnataka',
                Header::REGISTERED_PINCODE        => '849583',
                Header::OPERATIONAL_ADDRESS       => 'acme',
                Header::OPERATIONAL_CITY          => 'bangalore',
                Header::OPERATIONAL_STATE         => 'karnataka',
                Header::OPERATIONAL_PINCODE       => '930293',
                Header::DOE                       => '1990-02-12',
                Header::GSTIN                     => '22AAAAA0000A1Z5',
                Header::PROMOTER_PAN              => 'KDOPK0930L',
                Header::WEBSITE_URL               => 'http://www.facebook.com',
                Header::PROMOTER_PAN_NAME         => 'sdfds',
                Header::BANK_ACCOUNT_NUMBER       => '123456789098',
                Header::BANK_BRANCH_IFSC          => 'HDFC0000077',
                Header::BANK_ACCOUNT_NAME         => 'Mr merch',
                Header::FEE_BEARER                => 'Merchant',
                Header::COMPANY_CIN               => 'U65999KA2018PTC114468',
                Header::COMPANY_PAN               => 'JFKCU3829K',
                Header::COMPANY_PAN_NAME          => 'dsfdfsd',
                Header::MERCHANT_ID               => '',
                Entity::AUTO_SUBMIT               => 1,
                Entity::AUTOFILL_DETAILS          => 1,
                Entity::AUTO_ACTIVATE             => 1,
                Entity::USE_EMAIL_AS_DUMMY        => 0,
                Entity::PARTNER_ID                => '10000000000000',
                Entity::SKIP_BA_REGISTRATION      => 1,
                Entity::AUTO_ENABLE_INTERNATIONAL => 0,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_name' => "SubMerchantone",
                'merchant_email' => "merch1@razorpay.com",
                'Status' => "success",
            ],
        ],
    ],

    'testCreateSubMerchantWithAutoPricingPlanByAdminBatch' => [
        'request'  => [
            'url'     => '/submerchants_bulk_onboard/batch',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Entity-Id' => '10000000000000',
            ],
            'content' => [
                Header::MERCHANT_NAME             => 'SubMerchantone',
                Header::MERCHANT_EMAIL            => 'merch1@razorpay.com',
                Header::CONTACT_NAME              => 'merch',
                Header::CONTACT_EMAIL             => 'merch1@razorpay.com',
                Header::CONTACT_MOBILE            => '9302930211',
                Header::TRANSACTION_REPORT_EMAIL  => 'merch1@razorpay.com',
                Header::ORGANIZATION_TYPE         => 3,
                Header::BUSINESS_NAME             => 'sub merch business',
                Header::BILLING_LABEL             => 'acme',
                Header::INTERNATIONAL             => 0,
                Header::PAYMENTS_FOR              => 'business',
                Header::BUSINESS_MODEL            => 'acme',
                Header::BUSINESS_CATEGORY         => 'financial_services',
                Header::BUSINESS_SUB_CATEGORY     => 'lending',
                Header::REGISTERED_ADDRESS        => 'acme',
                Header::REGISTERED_CITY           => 'bangalore',
                Header::REGISTERED_STATE          => 'karnataka',
                Header::REGISTERED_PINCODE        => '849583',
                Header::OPERATIONAL_ADDRESS       => 'acme',
                Header::OPERATIONAL_CITY          => 'bangalore',
                Header::OPERATIONAL_STATE         => 'karnataka',
                Header::OPERATIONAL_PINCODE       => '930293',
                Header::DOE                       => '1990-02-12',
                Header::GSTIN                     => '22AAAAA0000A1Z5',
                Header::PROMOTER_PAN              => 'KDOPK0930L',
                Header::WEBSITE_URL               => 'http://www.facebook.com',
                Header::PROMOTER_PAN_NAME         => 'sdfds',
                Header::BANK_ACCOUNT_NUMBER       => '123456789098',
                Header::BANK_BRANCH_IFSC          => 'HDFC0000077',
                Header::BANK_ACCOUNT_NAME         => 'Mr merch',
                Header::FEE_BEARER                => 'Merchant',
                Header::COMPANY_CIN               => 'U65999KA2018PTC114468',
                Header::COMPANY_PAN               => 'JFKCU3829K',
                Header::COMPANY_PAN_NAME          => 'dsfdfsd',
                Header::MERCHANT_ID               => '',
                Entity::AUTO_SUBMIT               => 1,
                Entity::AUTOFILL_DETAILS          => 1,
                Entity::AUTO_ACTIVATE             => 1,
                Entity::USE_EMAIL_AS_DUMMY        => 0,
                Entity::PARTNER_ID                => '10000000000000',
                Entity::SKIP_BA_REGISTRATION      => 1,
                Entity::AUTO_ENABLE_INTERNATIONAL => 0,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_name' => "SubMerchantone",
                'merchant_email' => "merch1@razorpay.com",
                'Status' => "success",
            ],
        ],
    ],

    'testCreateSubMerchantWithAutoFeeBearerByAdminBatch' => [
        'request'  => [
            'url'     => '/submerchants_bulk_onboard/batch',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Entity-Id' => '10000000000000',
            ],
            'content' => [
                Header::MERCHANT_NAME             => 'SubMerchantone',
                Header::MERCHANT_EMAIL            => 'merch1@razorpay.com',
                Header::CONTACT_NAME              => 'merch',
                Header::CONTACT_EMAIL             => 'merch1@razorpay.com',
                Header::CONTACT_MOBILE            => '9302930211',
                Header::TRANSACTION_REPORT_EMAIL  => 'merch1@razorpay.com',
                Header::ORGANIZATION_TYPE         => 3,
                Header::BUSINESS_NAME             => 'sub merch business',
                Header::BILLING_LABEL             => 'acme',
                Header::INTERNATIONAL             => 0,
                Header::PAYMENTS_FOR              => 'business',
                Header::BUSINESS_MODEL            => 'acme',
                Header::BUSINESS_CATEGORY         => 'financial_services',
                Header::BUSINESS_SUB_CATEGORY     => 'lending',
                Header::REGISTERED_ADDRESS        => 'acme',
                Header::REGISTERED_CITY           => 'bangalore',
                Header::REGISTERED_STATE          => 'karnataka',
                Header::REGISTERED_PINCODE        => '849583',
                Header::OPERATIONAL_ADDRESS       => 'acme',
                Header::OPERATIONAL_CITY          => 'bangalore',
                Header::OPERATIONAL_STATE         => 'karnataka',
                Header::OPERATIONAL_PINCODE       => '930293',
                Header::DOE                       => '1990-02-12',
                Header::GSTIN                     => '22AAAAA0000A1Z5',
                Header::PROMOTER_PAN              => 'KDOPK0930L',
                Header::WEBSITE_URL               => 'http://www.facebook.com',
                Header::PROMOTER_PAN_NAME         => 'sdfds',
                Header::BANK_ACCOUNT_NUMBER       => '123456789098',
                Header::BANK_BRANCH_IFSC          => 'HDFC0000077',
                Header::BANK_ACCOUNT_NAME         => 'Mr merch',
                Header::FEE_BEARER                => 'Payer',
                Header::COMPANY_CIN               => 'U65999KA2018PTC114468',
                Header::COMPANY_PAN               => 'JFKCU3829K',
                Header::COMPANY_PAN_NAME          => 'dsfdfsd',
                Header::MERCHANT_ID               => '',
                Entity::AUTO_SUBMIT               => 1,
                Entity::AUTOFILL_DETAILS          => 1,
                Entity::AUTO_ACTIVATE             => 1,
                Entity::USE_EMAIL_AS_DUMMY        => 0,
                Entity::PARTNER_ID                => '10000000000000',
                Entity::SKIP_BA_REGISTRATION      => 1,
                Entity::AUTO_ENABLE_INTERNATIONAL => 0,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_name' => "SubMerchantone",
                'merchant_email' => "merch1@razorpay.com",
                'Status' => "success",
            ],
        ],
    ],

    'testCreateSubMerchantWithAutoFeeBearerDynamicByAdminBatch' => [
        'request'  => [
            'url'     => '/submerchants_bulk_onboard/batch',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Entity-Id' => '10000000000000',
            ],
            'content' => [
                Header::MERCHANT_NAME             => 'SubMerchantone',
                Header::MERCHANT_EMAIL            => 'merch1@razorpay.com',
                Header::CONTACT_NAME              => 'merch',
                Header::CONTACT_EMAIL             => 'merch1@razorpay.com',
                Header::CONTACT_MOBILE            => '9302930211',
                Header::TRANSACTION_REPORT_EMAIL  => 'merch1@razorpay.com',
                Header::ORGANIZATION_TYPE         => 3,
                Header::BUSINESS_NAME             => 'sub merch business',
                Header::BILLING_LABEL             => 'acme',
                Header::INTERNATIONAL             => 0,
                Header::PAYMENTS_FOR              => 'business',
                Header::BUSINESS_MODEL            => 'acme',
                Header::BUSINESS_CATEGORY         => 'financial_services',
                Header::BUSINESS_SUB_CATEGORY     => 'lending',
                Header::REGISTERED_ADDRESS        => 'acme',
                Header::REGISTERED_CITY           => 'bangalore',
                Header::REGISTERED_STATE          => 'karnataka',
                Header::REGISTERED_PINCODE        => '849583',
                Header::OPERATIONAL_ADDRESS       => 'acme',
                Header::OPERATIONAL_CITY          => 'bangalore',
                Header::OPERATIONAL_STATE         => 'karnataka',
                Header::OPERATIONAL_PINCODE       => '930293',
                Header::DOE                       => '1990-02-12',
                Header::GSTIN                     => '22AAAAA0000A1Z5',
                Header::PROMOTER_PAN              => 'KDOPK0930L',
                Header::WEBSITE_URL               => 'http://www.facebook.com',
                Header::PROMOTER_PAN_NAME         => 'sdfds',
                Header::BANK_ACCOUNT_NUMBER       => '123456789098',
                Header::BANK_BRANCH_IFSC          => 'HDFC0000077',
                Header::BANK_ACCOUNT_NAME         => 'Mr merch',
                Header::FEE_BEARER                => 'Dynamic',
                Header::COMPANY_CIN               => 'U65999KA2018PTC114468',
                Header::COMPANY_PAN               => 'JFKCU3829K',
                Header::COMPANY_PAN_NAME          => 'dsfdfsd',
                Header::MERCHANT_ID               => '',
                Entity::AUTO_SUBMIT               => 1,
                Entity::AUTOFILL_DETAILS          => 1,
                Entity::AUTO_ACTIVATE             => 1,
                Entity::USE_EMAIL_AS_DUMMY        => 0,
                Entity::PARTNER_ID                => '10000000000000',
                Entity::SKIP_BA_REGISTRATION      => 1,
                Entity::AUTO_ENABLE_INTERNATIONAL => 0,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_name' => "SubMerchantone",
                'merchant_email' => "merch1@razorpay.com",
                'Status' => "success",
            ],
        ],
    ],



    'testCreateLinkedAccountForExistingEmailsWithFeatureEnabledToDisallow' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'email'            => 'dynamically generated',
                'name'             => 'Linked Account Name',
                'account'          => true,
                'dashboard_access' => true,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_LINKED_ACCOUNT_CREATION_WITH_DUPLICATE_EMAIL_NOT_ENABLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CREATION_WITH_DUPLICATE_EMAIL_NOT_ENABLED,
        ],
    ],

    'testCreateLinkedAccountForExistingEmailsWithoutDashboardAccess' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'email'            => 'dynamically generated',
                'name'             => 'Linked Account Name',
                'account'          => true,
            ],
        ],
        'response' => [
            'content' => [
                'name'  => 'Linked Account Name',
            ],
        ],
    ],

    'testCreateLinkedAccountForExistingEmailsWithDashboardAccess' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'email'            => 'dynamically generated',
                'name'             => 'Linked Account Name',
                'account'          => true,
                'dashboard_access' => true,
            ],
        ],
        'response' => [
            'content' => [
                'name'  => 'Linked Account Name',
            ],
        ],
    ],

    'testCreateLinkedAccountForExistingEmailWithoutDashboardAccessHavingExistingLinkedAccount' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'email'            => 'dynamically generated',
                'name'             => 'Linked Account Name',
                'account'          => true,
            ],
        ],
        'response' => [
            'content' => [
                'name'  => 'Linked Account Name',
            ],
        ],
    ],

    'testCreateLinkedAccountForExistingEmailWithDashboardAccessHavingExistingLinkedAccount' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'email'            => 'dynamically generated',
                'name'             => 'Linked Account Name',
                'account'          => true,
                'dashboard_access' => true,
            ],
        ],
        'response' => [
            'content' => [
                'name'  => 'Linked Account Name',
            ],
        ],
    ],

    'testCreateLinkedAccountForExistingEmailsOtherLinkedAccountExistsForSameParent' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'email'            => 'dynamically generated',
                'name'             => 'Linked Account Name',
                'account'          => true,
                'dashboard_access' => true,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_EMAIL_ALREADY_EXISTS.'10000000000000',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_ALREADY_EXISTS,
        ],
    ],

    'testLinkedAccountDashboardAccessRevokeDoesNotAffectOtherUsers' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'dashboard_access' => false,
            ],
        ],
        'response'  => [
            'content'     => [
                'success' => true,
            ],
        ],
    ],

    'testLinkedAccountDashboardAccessAllowDoesNotAffectOtherUsers' => [
        'request' => [
            'url' => '/la-merchants/config',
            'method' => 'post',
            'content' => [
                'dashboard_access' => true,
            ],
        ],
        'response'  => [
            'content'     => [
                'success' => true,
            ],
        ],
    ],

    'testCreateSubMerchantWithInvalidEmailByAdminForAggregatorBatch' => [
        'request'  => [
            'url'     => '/submerchants_bulk_onboard/batch',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Entity-Id' => '10000000000000',
            ],
            'content' => [
                Header::MERCHANT_NAME             => 'SubMerchantone',
                Header::MERCHANT_EMAIL            => 'merch1@razorpay.com',
                Header::CONTACT_NAME              => 'merch',
                Header::CONTACT_EMAIL             => 'merch1@razorpay.com',
                Header::CONTACT_MOBILE            => '9302930211',
                Header::TRANSACTION_REPORT_EMAIL  => 'merch1@razorpay.com',
                Header::ORGANIZATION_TYPE         => 3,
                Header::BUSINESS_NAME             => 'sub merch business',
                Header::BILLING_LABEL             => 'acme',
                Header::INTERNATIONAL             => 0,
                Header::PAYMENTS_FOR              => 'business',
                Header::BUSINESS_MODEL            => 'acme',
                Header::BUSINESS_CATEGORY         => 'financial_services',
                Header::BUSINESS_SUB_CATEGORY     => 'lending',
                Header::REGISTERED_ADDRESS        => 'acme',
                Header::REGISTERED_CITY           => 'bangalore',
                Header::REGISTERED_STATE          => 'karnataka',
                Header::REGISTERED_PINCODE        => '849583',
                Header::OPERATIONAL_ADDRESS       => 'acme',
                Header::OPERATIONAL_CITY          => 'bangalore',
                Header::OPERATIONAL_STATE         => 'karnataka',
                Header::OPERATIONAL_PINCODE       => '930293',
                Header::DOE                       => '1990-02-12',
                Header::GSTIN                     => '22AAAAA0000A1Z5',
                Header::PROMOTER_PAN              => 'KDOPK0930L',
                Header::WEBSITE_URL               => 'http://www.facebook.com',
                Header::PROMOTER_PAN_NAME         => 'sdfds',
                Header::BANK_ACCOUNT_NUMBER       => '123456789098',
                Header::BANK_BRANCH_IFSC          => 'HDFC0000077',
                Header::BANK_ACCOUNT_NAME         => 'Mr merch',
                Header::FEE_BEARER                => 'Merchant',
                Header::COMPANY_CIN               => 'U65999KA2018PTC114468',
                Header::COMPANY_PAN               => 'JFKCU3829K',
                Header::COMPANY_PAN_NAME          => 'dsfdfsd',
                Header::MERCHANT_ID               => '',
                Entity::AUTO_SUBMIT               => 1,
                Entity::AUTOFILL_DETAILS          => 1,
                Entity::AUTO_ACTIVATE             => 1,
                Entity::USE_EMAIL_AS_DUMMY        => 0,
                Entity::PARTNER_ID                => '10000000000000',
                Entity::SKIP_BA_REGISTRATION      => 1,
                Entity::AUTO_ENABLE_INTERNATIONAL => 0,
            ],
        ],
        'response' => [
            'content' => [
                'Status'            => "failure",
                'Error Code'        => "BAD_REQUEST_ERROR",
                'Error Description' => "The email has already been taken.",
            ],
        ],
    ],

    'testCreateLinkedAccountReferenceData' => [
        'request' => [
            'url' => '/la_reference_data',
            'method' => 'post',
            'content' => [
                "la_reference_data" => [
                    [
                        "account_name" => "ABC Mutual Fund - Online Collection Account",
                        "account_number"=> "123000000000000",
                        "account_email" => "test+1@gmail.com",
                        "beneficiary_name"=> "ABC Mutual Fund - Funds Collection Account",
                        "business_name"=> "Test Asset Management Limited",
                        "business_type"=> "private_limited",
                        "dashboard_access"=> 0,
                        "customer_refund_access"=> 0,
                        "ifsc_code" => "HDFC0000060",
                        "category" => "amc_bank_account"
                    ],
                    [
                        "account_name"=>"XAY Mutual Fund - Online Collection Account",
                        "account_number"=> "000000000000123",
                        "account_email"=> "test+2@gmail.com",
                        "beneficiary_name"=> "XAY Mutual Fund - Online Collection Account",
                        "business_name"=> "Test Asset Management Limited",
                        "business_type"=> "private_limited",
                        "dashboard_access"=> 1,
                        "customer_refund_access"=> 0,
                        "ifsc_code"=> "UTIB0054004"
                    ],
                    [
                        "account_name"=>"XAY Mutual Fund - Online Collection Account",
                        "account_number"=> "000000000000123",
                        "account_email"=> "test+2@gmail.com",
                        "beneficiary_name"=> "XAY Mutual Fund - Online Collection Account",
                        "business_name"=> "Test Asset Management Limited",
                        "business_type"=> "private_limited",
                        "dashboard_access"=> 1,
                        "customer_refund_access"=> 0,
                        "ifsc_code"=> "UTIB0054004",
                        "category" => "wrong_category",
                    ],
                ]
            ]
        ],
        'response' => [
            'content' => [
                "success" => 1,
                "failure" => 2,
                "total"  => 3,
                "data"   => [
                    "successful" => [
                        "Test Asset Management Limited",
                    ],

                    "failed" => [
                        "Test Asset Management Limited",
                        "Test Asset Management Limited",
                    ],
                ]
            ]
        ]
    ],

    'testUpdateLinkedAccountReferenceData'   => [
        'request'   =>  [
            'url'   =>  '/la_reference_data/{id}',
            'method'    => 'PATCH',
            'content'   => [
                'account_number'    =>  '1234567890',
                'account_name'      =>  'XAY Mutual Fund test',
                'ifsc_code'         =>  'SBIN0004903',
            ],
        ],
        'response'  =>  [
            'content'   =>  [
                'account_number'    =>  '1234567890',
                'account_name'      =>  'XAY Mutual Fund test',
                'ifsc_code'         =>  'SBIN0004903',
            ],
        ],
    ],

    'testUpdateLinkedAccountReferenceDataException'   => [
        'request'   =>  [
            'url'   =>  '/la_reference_data/{id}',
            'method'    => 'PATCH',
            'content'   => [
                'account_number'    =>  '1234567890',
                'account_name'      =>  'XAY Mutual Fund test',
                'ifsc_code'         =>  'SBIN0004903123',
            ],
        ],
        'response'  =>  [
            'content'   =>  [
                'status'    => 'failed',
                'message'   => 'The ifsc code must be 11 characters.',
            ],
        ],
    ],

    'testAmcLinkedAccountCreateForMutualFundDistributorMerchantAdminApi' => [
        'request' => [
            'url'    => '/la_amc',
            'method' => 'post',
        ],
        'response' => [
            'content'  => [
                'message'  => 'Request processing'
            ]
        ]
    ]
];
