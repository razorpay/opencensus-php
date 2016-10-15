<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreatePricingPlan' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'payment_method' => 'card',
                'payment_method_type'  => 'credit',
                'payment_network' => 'DICL',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000,
                'international' => 0,
                'amount_range_active' => '0',
                'amount_range_min' => null,
                'amount_range_max' => null,
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name' => 'TestPlan1',
                'entity' => 'pricing',
                'count' => 1,
                'rules' => array(
                    array(
                        'plan_name' => 'TestPlan1',
                        'payment_method' => 'card',
                        'payment_method_type'  => 'credit',
                        'payment_network' => 'DICL',
                        'payment_issuer' => 'HDFC',
                        'percent_rate' => 1000,
                        'international' => false,
                        'amount_range_active' => false,
                        'amount_range_min' => null,
                        'amount_range_max' => null,
                    ),
                ),
            ],
        ],
    ],

    'testUploadPricingPlan' => [
        'request' => [
            'content' => [
                [
                    'plan_name'      => 'TestUploadPlan2',
                    'payment_method' => 'netbanking',
                    'percent_rate'   => 1000,
                ],
                [
                    'payment_method' => 'card',
                    'percent_rate'   => 1000,
                ]
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name' => 'TestUploadPlan2',
                'entity' => 'pricing',
                'count' => 2,
                'rules' => array(
                    array(
                        'plan_name' => 'TestUploadPlan2',
                        'payment_method' => 'card',
                        'percent_rate' => 1000,
                        'international' => false,
                        'amount_range_active' => false,
                        'amount_range_min' => null,
                        'amount_range_max' => null,
                    ),
                    array(
                        'plan_name' => 'TestUploadPlan2',
                        'payment_method' => 'netbanking',
                        'percent_rate' => 1000,
                        'international' => false,
                        'amount_range_active' => false,
                        'amount_range_min' => null,
                        'amount_range_max' => null,
                    )
                ),
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

    'testAddPricingPlanNBRule' => [
        'request' => [
            'content' => [
                'payment_method' => 'netbanking',
                'percent_rate' => 1000,
                'payment_network' => 'SIBL',
                'amount_range_active' => true,
                'amount_range_min' => 0,
                'amount_range_max' => 100000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'payment_method' => 'netbanking',
                'payment_method_type' => null,
                'payment_network' => 'SIBL',
                'payment_issuer' => null,
                'percent_rate' => 1000,
                'amount_range_active' => true,
                'amount_range_min' => 0,
                'amount_range_max' => 100000,
            ],
        ],
    ],

    'testAddPricingPlanNBNoNetworkRule' => [
        'request' => [
            'content' => [
                'payment_method' => 'netbanking',
                'percent_rate' => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'payment_method' => 'netbanking',
                'payment_method_type' => null,
                'payment_network' => null,
                'payment_issuer' => null,
                'percent_rate' => 1000
            ],
        ],
    ],


    'testAddPricingPlanWalletRule' => [
        'request' => [
            'content' => [
                'payment_method' => 'wallet',
                'payment_network' => 'paytm',
                'percent_rate' => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'payment_method' => 'wallet',
                'payment_method_type' => null,
                'payment_network' => 'paytm',
                'payment_issuer' => null,
                'percent_rate' => 1000
            ],
        ],
    ],

    'testAddDuplicatePricingPlanRule' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'payment_method' => 'card',
                'payment_method_type'  => 'credit',
                'payment_network' => 'DICL',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000,
                'international' => 0,
                'amount_range_active' => 0,
                'amount_range_min' => null,
                'amount_range_max' => null,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],

    'testGetPricingPlan' => [
        'response' => [
            'content' => [
                'name' => 'TestPlan2',
                'entity' => 'pricing',
                'count' => 4,
                'rules' => array(
                    array(
                        'plan_name' => 'TestPlan2',
                        'payment_method' => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network' => 'MC',
                        'payment_issuer' => 'AXIS',
                        'percent_rate' => 0,
                        'fixed_rate' => 3000,
                        'international' => false,
                        'amount_range_active' => false,
                        'amount_range_min' => null,
                        'amount_range_max' => null,
                    ),
                    array(
                        'plan_name' => 'TestPlan2',
                        'payment_method' => 'card',
                        'payment_method_type' => 'debit',
                        'payment_network' => 'MAES',
                        'payment_issuer' => 'PUNB',
                        'percent_rate' => 250,
                        'fixed_rate' => 0,
                        'international' => false,
                        'amount_range_active' => false,
                        'amount_range_min' => null,
                        'amount_range_max' => null,
                    ),
                    array(
                        'plan_name' => 'TestPlan2',
                        'payment_method' => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network' => 'DICL',
                        'payment_issuer' => 'ICIC',
                        'percent_rate' => 250,
                        'fixed_rate' => 0,
                        'international' => false,
                        'amount_range_active' => false,
                        'amount_range_min' => null,
                        'amount_range_max' => null,
                    ),
                    array(
                        'plan_name' => 'TestPlan2',
                        'gateway' => NULL,
                        'payment_method' => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network' => 'DICL',
                        'payment_issuer' => 'SBIN',
                        'percent_rate' => 275,
                        'fixed_rate' => 0,
                        'international' => false,
                        'amount_range_active' => false,
                        'amount_range_min' => null,
                        'amount_range_max' => null,
                    ),
                )
            ]
        ]
    ],

    'testGetPricingPlans' => [
        'request' => [
            'url' => '/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count' => 3,
                'entity' => 'collection',
                'items' => array(
                    array(
                        'name' => 'TestPlan2',
                        'entity' => 'pricing',
                        'count' => 4,
                        'rules' => array(
                            array(
                                'plan_name' => 'TestPlan2',
                                'gateway' => NULL,
                                'payment_method' => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network' => 'MC',
                                'payment_issuer' => 'AXIS',
                                'percent_rate' => 0,
                                'fixed_rate' => 3000,
                                'international' => false,
                            ),
                            array(
                                'plan_name' => 'TestPlan2',
                                'payment_method' => 'card',
                                'payment_method_type' => 'debit',
                                'payment_network' => 'MAES',
                                'payment_issuer' => 'PUNB',
                                'percent_rate' => 250,
                                'fixed_rate' => 0,
                                'international' => false,
                            ),
                            array(
                                'plan_name' => 'TestPlan2',
                                'payment_method' => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network' => 'DICL',
                                'payment_issuer' => 'ICIC',
                                'percent_rate' => 250,
                                'fixed_rate' => 0,
                                'international' => false,
                            ),
                            array(
                                'plan_name' => 'TestPlan2',
                                'payment_method' => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network' => 'DICL',
                                'payment_issuer' => 'SBIN',
                                'percent_rate' => 275,
                                'fixed_rate' => 0,
                                'international' => false,
                            ),
                        )
                    ),
                    array(
                        'name' => 'TestPlan1',
                        'entity' => 'pricing',
                        'count' => 1,
                        'rules' => array(
                            array(
                                'plan_name' =>  'TestPlan1',
                                'gateway' => NULL,
                                'payment_method' =>  'card',
                                'payment_method_type' => 'credit',
                                'payment_network' =>  'DICL',
                                'payment_issuer' =>  'HDFC',
                                'percent_rate' =>  1000,
                                'international' => false,
                                'fixed_rate' =>  0,
                                'expired_at' => NULL
                            )
                        )
                    ),
                    array(
                        'name' => 'testDefaultPlan',
                        'entity' => 'pricing',
                        'count' => 10,
                        'rules' => array(
                            array(),
                        ),
                    ),
                )
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
                'count' => 3,
                'entity' => 'collection',
                'items' => array(
                    array(
                        'name' => 'TestPlan2',
                        'entity' => 'pricing',
                        'count' => 4,
                        'rules' => array(
                            array(
                                'plan_name' => 'TestPlan2',
                                'gateway' => NULL,
                                'payment_method' => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network' => 'MC',
                                'payment_issuer' => 'AXIS',
                                'percent_rate' => 0,
                                'fixed_rate' => 3000,
                                'international' => false,
                            ),
                            array(
                                'plan_name' => 'TestPlan2',
                                'payment_method' => 'card',
                                'payment_method_type' => 'debit',
                                'payment_network' => 'MAES',
                                'payment_issuer' => 'PUNB',
                                'percent_rate' => 250,
                                'fixed_rate' => 0,
                                'international' => false,
                            ),
                            array(
                                'plan_name' => 'TestPlan2',
                                'payment_method' => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network' => 'DICL',
                                'payment_issuer' => 'ICIC',
                                'percent_rate' => 250,
                                'fixed_rate' => 0,
                                'international' => false,
                            ),
                            array(
                                'plan_name' => 'TestPlan2',
                                'payment_method' => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network' => 'DICL',
                                'payment_issuer' => 'SBIN',
                                'percent_rate' => 275,
                                'fixed_rate' => 0,
                                'international' => false,
                            ),
                        )
                    ),
                    array(
                        'name' => 'TestPlan1',
                        'entity' => 'pricing',
                        'count' => 2,
                        'rules' => array(
                            array(
                                'plan_name' =>  'TestPlan1',
                                'gateway' => NULL,
                                'payment_method' =>  'card',
                                'payment_method_type' => 'credit',
                                'payment_network' =>  'MAES',
                                'payment_issuer' =>  'HDFC',
                                'percent_rate' =>  1000,
                                'international' => false,
                                'fixed_rate' =>  0,
                                'expired_at' => NULL
                            ),
                            array(
                                'plan_name' =>  'TestPlan1',
                                'gateway' => NULL,
                                'payment_method' =>  'card',
                                'payment_method_type' => 'credit',
                                'payment_network' =>  'DICL',
                                'payment_issuer' =>  'HDFC',
                                'percent_rate' =>  1000,
                                'international' => false,
                                'fixed_rate' =>  0,
                                'expired_at' => NULL
                            )
                        )
                    ),
                    array(
                        'name' => 'testDefaultPlan',
                        'entity' => 'pricing',
                        'count' => 10,
                        'rules' => array(
                            array(),
                        ),
                    ),
                )
            ]
        ]
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
                'rules' => array(
                    array(
                        'payment_method' => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network' => 'DICL',
                        'payment_issuer' => 'HDFC',
                        'percent_rate' => 1000,
                        'international' => false,
                    ),
                ),
            ],
        ]
    ],

    'testMerchantAssignPricingPlanWithInternational' =>[
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testMerchantWithAmexEnabled' =>[
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_FOR_AMEX_NOT_PRESENT,
        ]
    ],

    'testMerchantAssignPricingPlanMerchantDefault' =>[
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
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
                'rules' => array(
                    array(
                        'payment_method' => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network' => 'DICL',
                        'payment_issuer' => 'HDFC',
                        'percent_rate' => 1000,
                        'international' => false,
                    ),
                ),
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
                'rules' => array(
                    array(),
                    array(),
                    array(),
                    array()
                ),
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
                'rules' => array(
                    array(
                        'payment_method' => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network' => 'VISA',
                        'payment_issuer' => 'ICIC',
                        'percent_rate' => 1000,
                        'fixed_rate' => 10000,
                        'international' => false,
                    ),
                ),
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddInternationalPricingPlanRule' => [
        'request' => [
            'content' => [
                'payment_method' => 'card',
                'payment_method_type'  => null,
                'payment_network' => null,
                'payment_issuer' => null,
                'percent_rate' => 1100,
                'international' => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'payment_method' => 'card',
                'payment_method_type'  => null,
                'payment_network' => null,
                'payment_issuer' => null,
                'percent_rate' => 1100,
                'international' => true,
            ],
        ],
    ],

    'testAddDuplicateInternationalPricingPlanRule' => [
        'request' => [
            'content' => [
                'payment_method' => 'card',
                'payment_method_type'  => null,
                'payment_network' => null,
                'payment_issuer' => null,
                'percent_rate' => 1200,
                'international' => 1,
                'amount_range_active' => 0,
                'amount_range_min' => null,
                'amount_range_max' => null,
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],

    'testAddInternationalPricingPlanRuleForNonCardMethod' => [
        'request' => [
            'content' => [
                'payment_method' => 'netbanking',
                'payment_method_type'  => null,
                'payment_network' => null,
                'payment_issuer' => null,
                'percent_rate' => 1200,
                'international' => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Internatioanl pricing rule is only allowed for card method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddInternationalPricingPlanRuleWithExtraFields' => [
        'request' => [
            'content' => [
                'payment_method' => 'card',
                'payment_method_type'  => 'credit',
                'payment_network' => null,
                'payment_issuer' => null,
                'percent_rate' => 1200,
                'international' => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'For international pricing rule, attribute payment_method_type should not be set',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddDuplicateWalletPricingRule' => [
        'request' => [
            'content' => [
                'payment_method' => 'wallet',
                'payment_method_type'  => 'credit',
                'payment_network' => null,
                'payment_issuer' => null,
                'percent_rate' => 1200,
                'international' => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payment method type field may be sent only when payment method is card',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddDuplicateWalletPricingRule' => [
        'request' => [
            'content' => [
                'payment_method' => 'wallet',
                'payment_method_type' => null,
                'payment_network' => null,
                'payment_issuer' => null,
                'percent_rate' => 300,
                'fixed_rate' => 0,
                'international' => 0,
                'amount_range_active' => 0,
                'amount_range_min' => null,
                'amount_range_max' => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
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
                'payment_method' => 'card',
                'payment_method_type' => 'debit',
                'payment_network' => null,
                'payment_issuer' => null,
                'percent_rate' => 1500,
                'fixed_rate' => 0,
                'international' => 0,
                'amount_range_active' => 1,
                'amount_range_min' => 100,
                'amount_range_max' => 25000,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'payment_method' => 'card',
                'payment_method_type' => 'debit',
                'payment_network' => null,
                'payment_issuer' => null,
                'percent_rate' => 1500,
                'international' => false,
                'amount_range_active' => true,
                'amount_range_min' => 100,
                'amount_range_max' => 25000,
            ],
        ],
    ],

    'testAddAmountRangePricingPlanRuleOverlap' => [
        'request' => [
            'content' => [
                'payment_method' => 'card',
                'payment_method_type' => 'debit',
                'payment_network' => null,
                'payment_issuer' => null,
                'percent_rate' => 1500,
                'fixed_rate' => 0,
                'international' => 0,
                'amount_range_active' => 1,
                'amount_range_min' => 2500,
                'amount_range_max' => 100000000,
            ],
            'method' => 'POST'
        ],
        'response'  =>  [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_RULE_FOR_AMOUNT_RANGE_OVERLAP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testAddAmountRangePricingPlanRuleDuplicate' => [
        'request' => [
            'content' => [
                'payment_method' => 'card',
                'payment_method_type' => 'debit',
                'payment_network' => null,
                'payment_issuer' => null,
                'percent_rate' => 1500,
                'fixed_rate' => 0,
                'international' => 0,
                'amount_range_active' => 1,
                'amount_range_min' => 100,
                'amount_range_max' => 25000,
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],
];
