<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreatePricingPlanWithMinAndMaxFee' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'rules'     => [
                    [
                        'product'               => 'primary',
                        'feature'               => 'refund',
                        'type'                  => 'pricing',
                        'payment_method_type'   => 'IMPS',
                        'percent_rate'          => 1000,
                        'fixed_rate'            => 100,
                        'amount_range_active'   => '1',
                        'amount_range_min'      => 0,
                        'amount_range_max'      => 10000,
                    ],
                ],
            ],
            'url' => '/pricing/vas/fetch',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'name' => 'TestPlan1',
                'entity' => 'pricing',
                'count' => 1,
                'rules' => [
                    [
                        'product'               => 'primary',
                        'feature'               => 'refund',
                        'type'                  => 'pricing',
                        'payment_method'        => null,
                        'payment_method_type'   => 'IMPS',
                        'payment_network'       => null,
                        'percent_rate'          => 1000,
                        'fixed_rate'            => 100,
                        'international'         => false,
                        'amount_range_active'   => true,
                        'amount_range_min'      => 0,
                        'amount_range_max'      => 10000,
                        //Defaults to 0
                        'min_fee'               => 0,
                        'max_fee'               => null,
                    ],
                ],
            ],
        ],
    ],

    'testAddPricingPlanRuleAffordabilityWidgetNullValidation' => [
        'request'  => [
            'content' => [
                'feature'             => 'affordability',
                'payment_method'      => 'widget',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 12,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'fee_bearer'          => null,
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'feature'             => 'affordability',
                'payment_method'      => 'widget',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 12,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleAffordabilityWidgetDailyValidation' => [
        'request'  => [
            'content' => [
                'feature'             => 'affordability',
                'payment_method'      => 'widget',
                'payment_method_type' => 'daily',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 10,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'fee_bearer'          => null,
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'feature'             => 'affordability',
                'payment_method'      => 'widget',
                'payment_method_type' => 'daily',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 10,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleAffordabilityWidgetMonthlyValidation' => [
        'request'  => [
            'content' => [
                'feature'             => 'affordability',
                'payment_method'      => 'widget',
                'payment_method_type' => 'monthly',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 8,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'fee_bearer'          => null,
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'feature'             => 'affordability',
                'payment_method'      => 'widget',
                'payment_method_type' => 'monthly',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 8,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleAffordabilityEligibilityValidation' => [
        'request'  => [
            'content' => [
                'feature'             => 'affordability',
                'payment_method'      => 'eligibility',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 15,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'fee_bearer'          => null,
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'feature'             => 'affordability',
                'payment_method'      => 'eligibility',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 15,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleSMSValidation' => [
        'request'  => [
            'content' => [
                'feature'             => 'sms',
                'payment_method'      => null,
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 15,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'fee_bearer'          => null,
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'feature'             => 'sms',
                'payment_method'      => null,
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 15,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testGetVASPricingAffordabilityWidgetDaily' => [
        'request'  => [
            'content' => [
                'merchant_id'         => '10000000000000',
                'feature'             => 'affordability',
                'method'              => 'widget',
                'frequency'           => 'daily',
                'units'               => 30,
            ],
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                35400,
                5400,
                [
                    [
                        'name' => 'affordability',
                        'percentage' => null,
                        'amount' => 30000,
                        'pricing_rule' => [
                            'id' => 'AAAAAAAAAAAAAA',
                            'org_id' => '100000razorpay',
                            'plan_name' => 'TestPlan1',
                            'product' => 'primary',
                            'feature' => 'affordability',
                            'type' => 'pricing',
                            'gateway' => null,
                            'procurer' => null,
                            'payment_method' => 'widget',
                            'payment_method_type' => 'daily',
                            'payment_method_subtype' => null,
                            'auth_type' => null,
                            'payment_network' => null,
                            'payment_issuer' => null,
                            'emi_duration' => null,
                            'international' => false,
                            'fee_bearer' => 'platform',
                            'receiver_type' => null,
                            'account_type' => null,
                            'channel' => null,
                            'amount_range_active' => false,
                            'amount_range_min' => null,
                            'amount_range_max' => null,
                            'percent_rate' => 0,
                            'fixed_rate' => 1000,
                            'min_fee' => 0,
                            'max_fee' => null,
                            'deleted_at' => null,
                            'expired_at' => null,
                            'payouts_filter' => null,
                            'app_name' => null,
                            'fee_model' => null
                        ]
                    ],
                    [
                        'name' => 'tax',
                        'percentage' => 1800,
                        'amount' => 5400,
                        'pricing_rule_id' => null
                    ]
                ]
            ],
        ],
    ],

    'testGetVASPricingAffordabilityWidgetMonthly' => [
        'request'  => [
            'content' => [
                'merchant_id'         => '10000000000000',
                'feature'             => 'affordability',
                'method'              => 'widget',
                'frequency'           => 'monthly',
                'units'               => 30,
            ],
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                42480,
                6480,
                [
                    [
                        'name' => 'affordability',
                        'percentage' => null,
                        'amount' => 36000,
                        'pricing_rule' => [
                            'id' => 'AAAAAAAAAAAAAA',
                            'org_id' => '100000razorpay',
                            'plan_name' => 'TestPlan1',
                            'product' => 'primary',
                            'feature' => 'affordability',
                            'type' => 'pricing',
                            'gateway' => null,
                            'procurer' => null,
                            'payment_method' => 'widget',
                            'payment_method_type' => 'monthly',
                            'payment_method_subtype' => null,
                            'auth_type' => null,
                            'payment_network' => null,
                            'payment_issuer' => null,
                            'emi_duration' => null,
                            'international' => false,
                            'fee_bearer' => 'platform',
                            'receiver_type' => null,
                            'account_type' => null,
                            'channel' => null,
                            'amount_range_active' => false,
                            'amount_range_min' => null,
                            'amount_range_max' => null,
                            'percent_rate' => 0,
                            'fixed_rate' => 1200,
                            'min_fee' => 0,
                            'max_fee' => null,
                            'deleted_at' => null,
                            'expired_at' => null,
                            'payouts_filter' => null,
                            'app_name' => null,
                            'fee_model' => null
                        ]
                    ],
                    [
                        'name' => 'tax',
                        'percentage' => 1800,
                        'amount' => 6480,
                        'pricing_rule_id' => null
                    ]
                ]
            ],
        ],
    ],

    'testGetVASPricingAffordabilityEligibility' => [
        'request'  => [
            'content' => [
                'merchant_id'         => '10000000000000',
                'feature'             => 'affordability',
                'method'              => 'eligibility_api',
                'units'               => 15,
            ],
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                26550,
                4050,
                [
                    [
                        'name' => 'affordability',
                        'percentage' => null,
                        'amount' => 22500,
                        'pricing_rule' => [
                            'id' => 'AAAAAAAAAAAAAA',
                            'org_id' => '100000razorpay',
                            'plan_name' => 'TestPlan1',
                            'product' => 'primary',
                            'feature' => 'affordability',
                            'type' => 'pricing',
                            'gateway' => null,
                            'procurer' => null,
                            'payment_method' => 'eligibility_api',
                            'payment_method_type' => null,
                            'payment_method_subtype' => null,
                            'auth_type' => null,
                            'payment_network' => null,
                            'payment_issuer' => null,
                            'emi_duration' => null,
                            'international' => false,
                            'fee_bearer' => 'platform',
                            'receiver_type' => null,
                            'account_type' => null,
                            'channel' => null,
                            'amount_range_active' => false,
                            'amount_range_min' => null,
                            'amount_range_max' => null,
                            'percent_rate' => 0,
                            'fixed_rate' => 1500,
                            'min_fee' => 0,
                            'max_fee' => null,
                            'deleted_at' => null,
                            'expired_at' => null,
                            'payouts_filter' => null,
                            'app_name' => null,
                            'fee_model' => null
                        ]
                    ],
                    [
                        'name' => 'tax',
                        'percentage' => 1800,
                        'amount' => 4050,
                        'pricing_rule_id' => null
                    ]
                ]
            ],
        ],
    ],

    'testGetVASPricingSMS' => [
        'request'  => [
            'content' => [
                'merchant_id'         => '10000000000000',
                'feature'             => 'sms',
                'method'              => null,
                'units'               => 95,
            ],
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                78470,
                11970,
                [
                    [
                        'name' => 'sms',
                        'percentage' => null,
                        'amount' => 66500,
                        'pricing_rule' => [
                            'id' => 'AAAAAAAAAAAAAA',
                            'org_id' => '100000razorpay',
                            'plan_name' => 'TestPlan1',
                            'product' => 'primary',
                            'feature' => 'sms',
                            'type' => 'pricing',
                            'gateway' => null,
                            'procurer' => null,
                            'payment_method' => null,
                            'payment_method_type' => null,
                            'payment_method_subtype' => null,
                            'auth_type' => null,
                            'payment_network' => null,
                            'payment_issuer' => null,
                            'emi_duration' => null,
                            'international' => false,
                            'fee_bearer' => 'platform',
                            'receiver_type' => null,
                            'account_type' => null,
                            'channel' => null,
                            'amount_range_active' => false,
                            'amount_range_min' => null,
                            'amount_range_max' => null,
                            'percent_rate' => 0,
                            'fixed_rate' => 700,
                            'min_fee' => 0,
                            'max_fee' => null,
                            'deleted_at' => null,
                            'expired_at' => null,
                            'payouts_filter' => null,
                            'app_name' => null,
                            'fee_model' => null
                        ]
                    ],
                    [
                        'name' => 'tax',
                        'percentage' => 1800,
                        'amount' => 11970,
                        'pricing_rule_id' => null
                    ]
                ]
            ],
        ],
    ],

    'testGetVASPricingMissingUnits' => [
        'request'  => [
            'content' => [
                'merchant_id'         => '10000000000000',
                'feature'             => 'sms',
                'method'              => null,
            ],
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                0,
                0,
                [
                    [
                        'name' => 'sms',
                        'percentage' => null,
                        'amount' => 0,
                        'pricing_rule' => [
                            'id' => 'AAAAAAAAAAAAAA',
                            'org_id' => '100000razorpay',
                            'plan_name' => 'TestPlan1',
                            'product' => 'primary',
                            'feature' => 'sms',
                            'type' => 'pricing',
                            'gateway' => null,
                            'procurer' => null,
                            'payment_method' => null,
                            'payment_method_type' => null,
                            'payment_method_subtype' => null,
                            'auth_type' => null,
                            'payment_network' => null,
                            'payment_issuer' => null,
                            'emi_duration' => null,
                            'international' => false,
                            'fee_bearer' => 'platform',
                            'receiver_type' => null,
                            'account_type' => null,
                            'channel' => null,
                            'amount_range_active' => false,
                            'amount_range_min' => null,
                            'amount_range_max' => null,
                            'percent_rate' => 0,
                            'fixed_rate' => 700,
                            'min_fee' => 0,
                            'max_fee' => null,
                            'deleted_at' => null,
                            'expired_at' => null,
                            'payouts_filter' => null,
                            'app_name' => null,
                            'fee_model' => null
                        ]
                    ],
                    [
                        'name' => 'tax',
                        'percentage' => 1800,
                        'amount' => 0,
                        'pricing_rule_id' => null
                    ]
                ]
            ],
        ],
    ],

    'testGetVASPricingTokenHQ' => [
        'request'  => [
            'content' => [
                'merchant_id'         => '10000000000000',
                'feature'             => 'token_hq',
                'method'              => 'fetch_cryptogram',
                'units'               => 46,
                'amount'              => 400,
            ],
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                27150,
                4142,
                [
                    [
                        'name' => 'token_hq',
                        'percentage' => null,
                        'amount' => 23008,
                        'pricing_rule' => [
                            'id' => 'AAAAAAAAAAAAAA',
                            'org_id' => '100000razorpay',
                            'plan_name' => 'TestPlan1',
                            'product' => 'primary',
                            'feature' => 'token_hq',
                            'type' => 'pricing',
                            'gateway' => null,
                            'procurer' => null,
                            'payment_method' => 'fetch_cryptogram',
                            'payment_method_type' => null,
                            'payment_method_subtype' => null,
                            'auth_type' => null,
                            'payment_network' => null,
                            'payment_issuer' => null,
                            'emi_duration' => null,
                            'international' => false,
                            'fee_bearer' => 'platform',
                            'receiver_type' => null,
                            'account_type' => null,
                            'channel' => null,
                            'amount_range_active' => false,
                            'amount_range_min' => null,
                            'amount_range_max' => null,
                            'percent_rate' => 200,
                            'fixed_rate' => 500,
                            'min_fee' => 0,
                            'max_fee' => null,
                            'deleted_at' => null,
                            'expired_at' => null,
                            'payouts_filter' => null,
                            'app_name' => null,
                            'fee_model' => null
                        ]
                    ],
                    [
                        'name' => 'tax',
                        'percentage' => 1800,
                        'amount' => 4142,
                        'pricing_rule_id' => null
                    ]
                ]
            ],
        ],
    ],

    'testGetVASPricingMissingFeature' => [
        'request'  => [
            'content' => [
                'merchant_id'         => '10000000000000',
                'method'              => 'fetch_cryptogram',
                'units'               => 46,
                'amount'              => 400,
            ],
            'method'  => 'GET'
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
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description'         => 'The feature field is required.'
        ],
    ],

    'testGetVASPricingMissingMethod' => [
        'request'  => [
            'content' => [
                'merchant_id'         => '10000000000000',
                'feature'             => 'token_hq',
                'units'               => 46,
                'amount'              => 400,
            ],
            'method'  => 'GET'
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
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description'         => 'The method field is required unless feature is in sms.'
        ],
    ],

    'testGetVASPricingMissingMID' => [
        'request'  => [
            'content' => [
                'feature'             => 'token_hq',
                'method'              => 'fetch_cryptogram',
                'units'               => 46,
                'amount'              => 400,
            ],
            'method'  => 'GET'
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
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description'         => 'The merchant id field is required.'
        ],
    ],

    'testGetVASPricingAffordabilityWrongMethod' => [
        'request'  => [
            'content' => [
                'merchant_id'         => '10000000000000',
                'feature'             => 'affordability',
                'method'              => 'eligibility_apii',
                'units'               => 46,
            ],
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\LogicException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT,
        ],
    ],

    'testGetVASPricingInvalidFeature' => [
        'request'  => [
            'content' => [
                'merchant_id'         => '10000000000000',
                'feature'             => 'affordabilityy',
                'method'              => 'widget',
                'units'               => 46,
            ],
            'method'  => 'GET'
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
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description'         => 'The selected feature is invalid.'
        ],
    ],
];
