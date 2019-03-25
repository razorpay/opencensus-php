<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testBulkPricingPlan' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestUploadPlan2',
                'rules'     => [
                        [
                            'payment_method'    => 'netbanking',
                            'percent_rate'      => 1000,
                            'payment_network'   => 'SIBL',
                            'type'              => 'pricing',
                        ],
                        [
                            'feature'           => 'fund_account_validation',
                            'payment_method'    => 'bank_account',
                            'fixed_rate'        => 1000,
                            'type'              => 'pricing',
                        ],
                        [
                            'payment_method'        => 'card',
                            'payment_method_type'   => 'credit',
                            'payment_network'       => 'DICL',
                            'payment_issuer'        => 'HDFC',
                            'percent_rate'          => 1000,
                            'amount_range_active'   => false,
                            'amount_range_min'      => null,
                            'amount_range_max'      => null,
                            'min_fee'               => 10,
                            'max_fee'               => 10000,
                        ],
                        [
                            'payment_method'        => 'wallet',
                            'payment_network'       => 'paytm',
                            'percent_rate'          => 1000
                        ]
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'      => 'TestUploadPlan2',
                'entity'    => 'pricing',
                'count'     => 4,
                'rules'     => [
                    [
                        'plan_name'             => 'TestUploadPlan2',
                        'payment_method'        => 'wallet',
                        'payment_method_type'   => null,
                        'payment_network'       => 'paytm',
                        'payment_issuer'        => null,
                        'percent_rate'          => 1000,
                        'type'                  => 'pricing',
                    ],
                    [
                        'plan_name'             => 'TestUploadPlan2',
                        'payment_method'        => 'netbanking',
                        'percent_rate'          => 1000,
                        'payment_network'       => 'SIBL',
                    ],
                    [
                        'plan_name'             => 'TestUploadPlan2',
                        'payment_method'        => 'card',
                        'payment_method_type'   => 'credit',
                        'payment_network'       => 'DICL',
                        'payment_issuer'        => 'HDFC',
                        'percent_rate'          => 1000,
                        'amount_range_active'   => false,
                        'amount_range_min'      => null,
                        'amount_range_max'      => null,
                        'min_fee'               => 10,
                        'max_fee'               => 10000,
                    ],
                    [
                        'plan_name'             => 'TestUploadPlan2',
                        'feature'               => 'fund_account_validation',
                        'payment_method'        => 'bank_account',
                        'fixed_rate'            => 1000,
                    ],
                ],
            ],
        ],
    ],

    'testBulkPricingPlanOfMultipleTypes' => [
        'request'  => [
            'content' => [
                'plan_name' => 'TestUploadPlan2',
                'rules'     => [
                    [
                        'payment_method'    => 'netbanking',
                        'percent_rate'      => 1000,
                        'payment_network'   => 'SIBL',
                    ],
                    [
                        'feature'           => 'fund_account_validation',
                        'payment_method'    => 'bank_account',
                        'fixed_rate'        => 1000,
                        'type'              => 'commission',
                    ],
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_PLAN_CANNOT_HAVE_MULTIPLE_TYPES,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_PLAN_CANNOT_HAVE_MULTIPLE_TYPES,
        ],
    ],

    'testEmptyBulkPricingPlan' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestUploadPlan2',
                'rules'     => [],
            ],
            'url'           => '/pricing',
            'method'        => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testDuplicateBulkPricingPlan' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestUploadPlan2',
                'rules'     => [
                    [
                        'payment_method'        => 'card',
                        'percent_rate'          => 1000,
                        'payment_method_type'   => 'credit',
                        'payment_network'       => 'DICL',
                        'payment_issuer'        => 'HDFC',
                        'international'         => '0',
                        'amount_range_active'   => '0',
                        'amount_range_min'      => null,
                        'amount_range_max'      => null,
                    ],
                    [
                        'payment_method'        => 'card',
                        'percent_rate'          => 1000,
                        'payment_method_type'   => 'credit',
                        'payment_network'       => 'DICL',
                        'payment_issuer'        => 'HDFC',
                        'international'         => '0',
                        'amount_range_active'   => '0',
                        'amount_range_min'      => null,
                        'amount_range_max'      => null,
                    ],
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ]
    ],
    'testCreatePricingPlanWithMinAndMaxFee' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'rules'     => [
                        [
                            'payment_method'        => 'card',
                            'payment_method_type'   => 'credit',
                            'payment_network'       => 'DICL',
                            'payment_issuer'        => 'HDFC',
                            'percent_rate'          => 1000,
                            'international'         => '0',
                            'amount_range_active'   => '0',
                            'amount_range_min'      => null,
                            'amount_range_max'      => null,
                            'min_fee'               => null,
                            'max_fee'               => null,
                        ],
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name' => 'TestPlan1',
                'entity' => 'pricing',
                'count' => 1,
                'rules' => [
                    [
                        'payment_method'        => 'card',
                        'payment_method_type'   => 'credit',
                        'payment_network'       => 'DICL',
                        'payment_issuer'        => 'HDFC',
                        'percent_rate'          => 1000,
                        'international'         => false,
                        'amount_range_active'   => false,
                        'amount_range_min'      => null,
                        'amount_range_max'      => null,
                        //Defaults to 0
                        'min_fee'               => 0,
                        'max_fee'               => null,
                    ],
                ],
            ],
        ],
    ],
    'testCreatePricingPlanWithInvalidMinAndMaxFee' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'rules'     => [
                    [
                        'payment_method'        => 'card',
                        'payment_method_type'   => 'credit',
                        'payment_network'       => 'DICL',
                        'payment_issuer'        => 'HDFC',
                        'percent_rate'          => 1000,
                        'amount_range_active'   => false,
                        'amount_range_min'      => null,
                        'amount_range_max'      => null,
                        'min_fee'               => 10000,
                        'max_fee'               => 10,
                    ],
                ],
            ],
            'url' => '/pricing/',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Min fee chargeable for a rule needs to be greater than Max fee',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreatePricingPlanByRZPAdmin' => [
        'request'  => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'rules'     => [
                    [
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'HDFC',
                        'percent_rate'        => 1000,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                        'min_fee'             => null,
                        'max_fee'             => null,
                    ],
                ],
            ],
            'url'     => '/pricing/',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'name'   => 'TestPlan1',
                'entity' => 'pricing',
                'count'  => 1,
                'rules'  => [
                    [
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'HDFC',
                        'percent_rate'        => 1000,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                        //Defaults to 0
                        'min_fee'             => 0,
                        'max_fee'             => null,
                    ],
                ],
            ],
        ],
    ],

    'testAddPricingPlanRule' => [
        'request' => [
            'content' => [
                'payment_method' => 'card',
                'payment_method_type'  => 'credit',
                'payment_network' => 'MAES',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000,
                'international' => 0,
                'amount_range_active' => '0',
                'amount_range_min' => null,
                'amount_range_max' => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'MAES',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000,
                'international' => false,
                'amount_range_active' => false,
                'amount_range_min' => null,
                'amount_range_max' => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleByRZPAdmin' => [
        'request'  => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleBySBIAdmin' => [
        'request'   => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testOrgIdPricing' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleWithDebitPinFeature' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => 'MAES',
                'auth_type'           => 'pin',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'auth_type'           => 'pin',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleWithReceiver' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'DICL',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'receiver_type'       => 'qr_code',
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'DICL',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'receiver_type'       => 'qr_code',
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],


    'testDuplicateReceiverRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'DICL',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'receiver_type'       => 'qr_code',
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ]
    ],



    'testAddPricingPlanNBRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'netbanking',
                'percent_rate'        => 1000,
                'payment_network'     => 'BARB_R',
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => 'BARB_R',
                'payment_issuer'      => null,
                'percent_rate'        => 1000,
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'type'                => 'pricing',
            ],
        ],
    ],

    'testAddingPricingPlanNBRuleDifferentTypes' => [
        'request' => [
            'content' => [
                'payment_method'      => 'netbanking',
                'percent_rate'        => 1000,
                'payment_network'     => 'BARB_R',
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'type'                => 'commission',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_PLAN_CANNOT_HAVE_MULTIPLE_TYPES,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_PLAN_CANNOT_HAVE_MULTIPLE_TYPES,
        ],
    ],

    'testAddCommissionPlanNBRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'netbanking',
                'percent_rate'        => 1000,
                'payment_network'     => 'BARB_R',
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'type'                => 'commission',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => 'BARB_R',
                'payment_issuer'      => null,
                'percent_rate'        => 1000,
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'type'                => 'commission',
            ],
        ],
    ],

    'testAddPricingPlanNBRuleWithReceiver' => [
        'request' => [
            'content' => [
                'payment_method'      => 'netbanking',
                'percent_rate'        => 1000,
                'payment_network'     => 'SIBL',
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'receiver_type'       => 'qr_code',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The receiver type field may be sent only when payment method is card',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanNBNoNetworkRule' => [
        'request' => [
            'content' => [
                'payment_method' => 'netbanking',
                'percent_rate'   => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1000
            ],
        ],
    ],


    'testAddPricingPlanWalletRule' => [
        'request' => [
            'content' => [
                'payment_method'  => 'wallet',
                'payment_network' => 'paytm',
                'percent_rate'    => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'wallet',
                'payment_method_type' => null,
                'payment_network'     => 'paytm',
                'payment_issuer'      => null,
                'percent_rate'        => 1000
            ],
        ],
    ],

    'testAddPricingPlanFundAccountValidationRule' => [
        'request' => [
            'content' => [
                'feature'        => 'fund_account_validation',
                'payment_method' => 'bank_account',
                'fixed_rate'     => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'feature'             => 'fund_account_validation',
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'bank_account',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 1000
            ],
        ],
    ],

    'testAddPricingPlanEmandateRule' => [
        'request' => [
            'content' => [
                'payment_method' => 'emandate',
                'fixed_rate'     => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'emandate',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 1000
            ],
        ],
    ],

    'testAddPricingPlanEmandateRegistrationRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'emandate',
                'payment_method_type' => 'netbanking',
                'payment_issuer'      => 'initial',
                'fixed_rate'          => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'netbanking',
                'payment_network'     => null,
                'payment_issuer'      => 'initial',
                'percent_rate'        => 0,
                'fixed_rate'          => 1000
            ],
        ],
    ],

    'testAddPricingPlanEmandateDebitAadhaarRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar',
                'payment_issuer'      => 'auto',
                'fixed_rate'          => 2000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar',
                'payment_network'     => null,
                'payment_issuer'      => 'auto',
                'percent_rate'        => 0,
                'fixed_rate'          => 2000
            ],
        ],
    ],

    'testAddPricingPlanEmandatePercentageRateRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar',
                'payment_issuer'      => 'auto',
                'percent_rate'        => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Percentage rate pricing is not allowed for E-mandate',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddDuplicatePricingPlanRule' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'DICL',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => 0,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],

    'testAddPricingPlanRuleWithMaxFee' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'min_fee'             => null,
                'max_fee'             => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'min_fee'             => 0,
                'max_fee'             => null,
            ],
        ],
    ],
    'testUpdatePricingPlanRule' => [
        'request' => [
            'content' => [
                'min_fee'      => 101,
                'max_fee'      => 10000,
                'percent_rate' => 450,
                'fixed_rate'   => 0,
            ],
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'plan_name'    => 'TestPlan2',
                'percent_rate' => 450,
                'fixed_rate'   => 0,
                'min_fee'      => 101,
                'max_fee'      => 10000,
            ],
        ],
    ],

    'testUpdateCommissionRule' => [
        'request' => [
            'content' => [
                'min_fee'      => 101,
                'max_fee'      => 10000,
                'percent_rate' => 450,
                'fixed_rate'   => 0,
            ],
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'plan_name'    => 'TestPlan2',
                'percent_rate' => 450,
                'fixed_rate'   => 0,
                'min_fee'      => 101,
                'max_fee'      => 10000,
            ],
        ],
    ],

    'testUpdatePricingPlanRuleByRZPAdmin' => [
        'request'  => [
            'content' => [
                'min_fee'      => 101,
                'max_fee'      => 10000,
                'percent_rate' => 450,
                'fixed_rate'   => 0,
            ],
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'plan_name'    => 'TestPlan2',
                'percent_rate' => 450,
                'fixed_rate'   => 0,
                'min_fee'      => 101,
                'max_fee'      => 10000,
            ],
        ],
    ],

    'testCreateCommissionPlanBySBIOrg' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'rules'     => [
                    [
                        'payment_method'        => 'card',
                        'payment_method_type'   => 'credit',
                        'payment_network'       => 'DICL',
                        'payment_issuer'        => 'HDFC',
                        'percent_rate'          => 1000,
                        'international'         => '0',
                        'type'                  => 'commission',
                    ],
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_TYPE_COMMISSION_INVALID_FOR_NON_RZP_ORG,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_TYPE_COMMISSION_INVALID_FOR_NON_RZP_ORG,
        ],
    ],

    'testUpdatePricingPlanRuleBySBIAdmin' => [
        'request'   => [
            'content' => [
                'min_fee'      => 101,
                'max_fee'      => 10000,
                'percent_rate' => 450,
                'fixed_rate'   => 0,
            ],
            'method'  => 'PATCH'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No db records found.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testGetPricingPlan' => [
        'response' => [
            'content' => [
                'name' => 'TestPlan2',
                'entity' => 'pricing',
                'count' => 4,
                'rules' => [
                    [
                        'plan_name'           => 'TestPlan2',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'MC',
                        'payment_issuer'      => 'AXIS',
                        'percent_rate'        => 0,
                        'fixed_rate'          => 3000,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                    [
                        'plan_name'           => 'TestPlan2',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'debit',
                        'payment_network'     => 'MAES',
                        'payment_issuer'      => 'PUNB',
                        'percent_rate'        => 250,
                        'fixed_rate'          => 0,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                    [
                        'plan_name'           => 'TestPlan2',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'ICIC',
                        'percent_rate'        => 250,
                        'fixed_rate'          => 0,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                    [
                        'plan_name'           => 'TestPlan2',
                        'gateway'             => null,
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'SBIN',
                        'percent_rate'        => 275,
                        'fixed_rate'          => 0,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                ],
            ],
        ],
    ],

    'testGetPricingPlanByRZPAdmin' => [
        'response' => [
            'content' => [
                'name'   => 'TestPlan2',
                'entity' => 'pricing',
                'count'  => 4,
                'rules'  => [
                    [
                        'plan_name'           => 'TestPlan2',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'MC',
                        'payment_issuer'      => 'AXIS',
                        'percent_rate'        => 0,
                        'fixed_rate'          => 3000,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                    [
                        'plan_name'           => 'TestPlan2',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'debit',
                        'payment_network'     => 'MAES',
                        'payment_issuer'      => 'PUNB',
                        'percent_rate'        => 250,
                        'fixed_rate'          => 0,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                    [
                        'plan_name'           => 'TestPlan2',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'ICIC',
                        'percent_rate'        => 250,
                        'fixed_rate'          => 0,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                    [
                        'plan_name'           => 'TestPlan2',
                        'gateway'             => null,
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'SBIN',
                        'percent_rate'        => 275,
                        'fixed_rate'          => 0,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                ],
            ],
        ],
    ],

    'testGetPricingPlanBySBIAdmin' => [
        'response' => [
            'content' => []
        ],
    ],

    'testGetPricingPlans' => [
        'request' => [
            'url' => '/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count' => 8,
                'entity' => 'collection',
                'items' => [
                    [
                        'name' => 'CommDefaultPlan',
                    ],
                    [
                        'name' => 'Banking default plan',
                    ],
                    [
                        'name' => 'testDefaultEmiPlan',
                        'entity' => 'pricing',
                        'count' => 1,
                        'rules' => [
                            [],
                        ],
                    ],
                    [
                        'name' => 'testDefaultQrPlan',
                        'entity' => 'pricing',
                        'count' => 2,
                        'rules' => [
                            [],
                        ],
                    ],
                    [
                        'name' => 'TestPlan2',
                        'entity' => 'pricing',
                        'count' => 4,
                        'rules' => [
                            [
                                'plan_name'           => 'TestPlan2',
                                'gateway'             => null,
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'MC',
                                'payment_issuer'      => 'AXIS',
                                'percent_rate'        => 0,
                                'fixed_rate'          => 3000,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'debit',
                                'payment_network'     => 'MAES',
                                'payment_issuer'      => 'PUNB',
                                'percent_rate'        => 250,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'ICIC',
                                'percent_rate'        => 250,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'SBIN',
                                'percent_rate'        => 275,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                        ]
                    ],
                    [
                        'name' => 'TestPlan1',
                        'entity' => 'pricing',
                        'count' => 1,
                        'rules' => [
                            [
                                'plan_name'           => 'TestPlan1',
                                'gateway'             => null,
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'HDFC',
                                'percent_rate'        => 1000,
                                'international'       => false,
                                'fixed_rate'          => 0,
                                'expired_at'          => null
                            ]
                        ]
                    ],
                    [
                        'name' => 'testDefaultPlan',
                        'entity' => 'pricing',
                        'count' => 20,
                        'rules' => [
                            [],
                        ],
                    ],
                ]
            ]
        ]
    ],

    'testGetPricingPlansTypeFilter' => [
        'request' => [
            'url'     => '/pricing',
            'method'  => 'GET',
            'content' => [
                'type' => 'commission',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'items' => [
                    [
                        'rules' => [
                            [
                                'type' => 'commission',
                            ]
                        ]
                    ]
                ],
            ],
        ]
    ],

    'testGetPricingPlansByRZPAdmin' => [
        'request'  => [
            'url'    => '/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count'  => 8,
                'entity' => 'collection',
                'items'  => [
                    [
                        'name' => 'CommDefaultPlan',
                    ],
                    [
                        'name' => 'Banking default plan',
                    ],
                    [
                        'name'   => 'testDefaultEmiPlan',
                        'entity' => 'pricing',
                        'count'  => 1,
                        'rules'  => [
                            [],
                        ],
                    ],
                    [
                        'name'   => 'testDefaultQrPlan',
                        'entity' => 'pricing',
                        'count'  => 2,
                        'rules'  => [
                            [],
                        ],
                    ],
                    [
                        'name'   => 'TestPlan2',
                        'entity' => 'pricing',
                        'count'  => 4,
                        'rules'  => [
                            [
                                'plan_name'           => 'TestPlan2',
                                'gateway'             => null,
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'MC',
                                'payment_issuer'      => 'AXIS',
                                'percent_rate'        => 0,
                                'fixed_rate'          => 3000,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'debit',
                                'payment_network'     => 'MAES',
                                'payment_issuer'      => 'PUNB',
                                'percent_rate'        => 250,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'ICIC',
                                'percent_rate'        => 250,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'SBIN',
                                'percent_rate'        => 275,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                        ]
                    ],
                    [
                        'name'   => 'TestPlan1',
                        'entity' => 'pricing',
                        'count'  => 1,
                        'rules'  => [
                            [
                                'plan_name'           => 'TestPlan1',
                                'gateway'             => null,
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'HDFC',
                                'percent_rate'        => 1000,
                                'international'       => false,
                                'fixed_rate'          => 0,
                                'expired_at'          => null
                            ]
                        ]
                    ],
                    [
                        'name'   => 'testDefaultPlan',
                        'entity' => 'pricing',
                        'count'  => 20,
                        'rules'  => [
                            [],
                        ],
                    ],
                ]
            ]
        ]
    ],

    'testGetPricingPlansBySBIAdmin' => [
        'request'  => [
            'url'    => '/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count'  => 1,
                'entity' => 'collection',
                'items'  => [
                    [
                        'name'   => 'TestPlan2',
                        'entity' => 'pricing',
                        'count'  => 4,
                        'rules'  => [
                            [
                                'plan_name'           => 'TestPlan2',
                                'gateway'             => null,
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'MC',
                                'payment_issuer'      => 'AXIS',
                                'percent_rate'        => 0,
                                'fixed_rate'          => 3000,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'debit',
                                'payment_network'     => 'MAES',
                                'payment_issuer'      => 'PUNB',
                                'percent_rate'        => 250,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'ICIC',
                                'percent_rate'        => 250,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'SBIN',
                                'percent_rate'        => 275,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                        ]
                    ],
                ]
            ]
        ]
    ],

    'testGetPricingPlansGrouping' => [
        'request' => [
            'url' => '/pricing/merchants',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'plan_name'   => 'CommDefaultPlan',
                    'rules_count' => 2,
                    'type'        => 'commission',
                ],
                [
                    'plan_name'   => 'Banking default plan',
                    'rules_count' => 6,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultEmiPlan',
                    'rules_count' => 1,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultQrPlan',
                    'rules_count' => 2,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'TestPlan2',
                    'rules_count' => 4,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'TestPlan1',
                    'rules_count' => 2,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultPlan',
                    'rules_count' => 20,
                    'type'        => 'pricing',
                ],
            ],
        ],
    ],

    'testGetMerchantPlansWithFilters' => [
        'request' => [
            'url' => '/pricing/merchants',
            'method' => 'GET',
            'content' => [
                'type' => 'commission'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'plan_name'   => 'CommDefaultPlan',
                    'rules_count' => 2,
                    'type'        => 'commission',
                ],
                [
                    'plan_name'   => 'TestPlan9',
                    'rules_count' => 1,
                    'type'        => 'commission',
                ],
            ],
        ],
    ],

    'testGetPricingPlansGroupingByRZPAdmin' => [
        'request'  => [
            'url'    => '/pricing/merchants',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'plan_name'   => 'CommDefaultPlan',
                    'rules_count' => 2,
                    'type'        => 'commission',
                ],
                [
                    'plan_name'   => 'Banking default plan',
                    'rules_count' => 6,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultEmiPlan',
                    'rules_count' => 1,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultQrPlan',
                    'rules_count' => 2,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'TestPlan2',
                    'rules_count' => 4,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'TestPlan1',
                    'rules_count' => 1,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultPlan',
                    'rules_count' => 20,
                    'type'        => 'pricing',
                ],
            ],
        ],
    ],

    'testGetPricingPlansGroupingBySBIAdmin' => [
        'request'  => [
            'url'    => '/pricing/merchants',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'plan_name'   => 'TestPlan2',
                    'rules_count' => 4,
                ],
            ],
        ],
    ],

    'testMerchantAssignPricingPlanDefault' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name' => 'TestPlan1',
                'entity' => 'pricing',
                'count' => 1,
                'rules' => [
                    [
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'HDFC',
                        'percent_rate'        => 1000,
                        'international'       => false,
                    ],
                ],
            ],
        ]
    ],

    'testMerchantAssignPricingPlanWithInternational' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
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
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testMerchantWithAmexEnabled' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_FOR_AMEX_NOT_PRESENT,
        ]
    ],

    'testMerchantAssignPricingPlanMerchantDefault' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
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
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testMerchantAssignAndGetPricingPlan' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'name' => 'TestPlan1',
                'entity' => 'pricing',
                'count' => 1,
                'rules' => [
                    [
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'HDFC',
                        'percent_rate'        => 1000,
                        'international'       => false,
                    ],
                ],
            ],
        ]
    ],

    'testMerchantReplacePricingPlan' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name' => 'TestPlan2',
                'entity' => 'pricing',
                'count' => 4,
                'rules' => [
                    [],
                    [],
                    [],
                    []
                ],
            ],
        ]
    ],

    'testMerchantGetPricingPlanNoPlanAssigned' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testMerchantGetPricingPlan' => [
        'request' => [
            'url' => '/merchants/1FcXNxsHt5dOPI/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id' => '1ycviEdCgurrFI',
                'name' => 'testFixturePlan',
                'entity' => 'pricing',
                'count' => 1,
                'rules' => [
                    [
                        'payment_method'      => 'card',
                        'payment_method_type' => 'debit',
                        'payment_network'     => 'VISA',
                        'payment_issuer'      => 'hdfc',
                        'percent_rate'        => 1000,
                        'fixed_rate'          => 10000,
                        'international'       => false,
                    ],
                ],
            ],
        ]
    ],

    'testDeletePricingPlanRule' => [
        'request' => [
            'url' => '/pricing/1hDYlICobzOCYt/rule/1zD0BXpeOyaqpB',
            'method' => 'delete',
        ],
        'response' => [
            'content' => [
                'message' => 'Pricing successfully deleted',
            ],
        ],
    ],

    'testDeleteCommissionRule' => [
        'request' => [
            'method' => 'delete',
        ],
        'response' => [
            'content' => [
                'message' => 'Pricing successfully deleted',
            ],
        ],
    ],

    'testDeletePricingPlanRuleByRZPAdmin' => [
        'request'  => [
            'url'    => '/pricing/1hDYlICobzOCYt/rule/1zE3QYFf1zbys6',
            'method' => 'delete',
        ],
        'response' => [
            'content' => [
                'message' => 'Pricing successfully deleted',
            ],
        ],
    ],

    'testDeletePricingPlanRuleBySBIAdmin' => [
        'request'   => [
            'url'    => '/pricing/1hDYlICobzOCYt/rule/1zD0BXpeOyaqpB',
            'method' => 'delete',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No db records found.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testDeletePricingPlanRuleForce' => [
        'request' => [
            'url' => '/pricing/1hDYlICobzOCYt/rule/1zD0BXpeOyaqpB/force',
            'method' => 'delete',
        ],
        'response' => [
            'content' => [
                'message' => 'Pricing successfully deleted',
            ],
        ],
    ],

    'testDeleteUsedPricingPlanRule' => [
        'request' => [
            'url' => '/pricing/1hDYlICobzOCYt/rule/1zD0BXpeOyaqpB',
            'method' => 'delete',
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
        ],
    ],

    'testAddInternationalPricingPlanRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1100,
                'international'       => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1100,
                'international'       => true,
            ],
        ],
    ],

    'testAddDuplicateInternationalPricingPlanRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1200,
                'international'       => 1,
                'amount_range_active' => 0,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],

    'testAddInternationalPricingPlanRuleForNonCardMethod' => [
        'request' => [
            'content' => [
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1200,
                'international'       => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'International pricing rule is only allowed for card method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddInternationalPricingPlanRuleWithExtraFields' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1200,
                'international'       => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'For international pricing rule, attribute payment_method_type should not be set',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddDuplicateWalletPricingRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'wallet',
                'payment_method_type' => 'credit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1200,
                'international'       => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payment method type field may be sent only when payment method is card',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddDuplicateWalletPricingRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'wallet',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'amount_range_active' => 0,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],

    'testGetPricingNetworks' => [
        'request' => [
            'url'       => '/pricing/networks',
            'method'    => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAddAmountRangePricingPlanRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1500,
                'fixed_rate'          => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 25000,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1500,
                'international'       => false,
                'amount_range_active' => true,
                'amount_range_min'    => 100,
                'amount_range_max'    => 25000,
            ],
        ],
    ],

    'testAddAmountRangePricingPlanRuleOverlap' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1500,
                'fixed_rate'          => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_min'    => 2500,
                'amount_range_max'    => 100000000,
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_RULE_FOR_AMOUNT_RANGE_OVERLAP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testAddAmountRangePricingPlanRuleDuplicate' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1500,
                'fixed_rate'          => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 25000,
            ],
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],

    'testAddPricingPlanRuleWithFeature' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'recurring',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'recurring',
            ],
        ],
    ],
    'testAddPricingPlanRuleWithFeatureESAutomatic' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'esautomatic',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'esautomatic',
            ],
        ],
    ],
];
