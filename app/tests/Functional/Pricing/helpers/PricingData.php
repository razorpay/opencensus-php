<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testCreatePricingPlan' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'payment_method' => 'card',
                'payment_method_type'  => 'credit',
                'payment_network' => 'DICL',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000
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
                        'percent_rate' => 1000
                    ),
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
                'percent_rate' => 1000
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
                'percent_rate' => 1000
            ],
        ],
    ],

    'testAddPricingPlanNBRule' => [
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

    'testAddDuplicatePricingPlanRule' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'payment_method' => 'card',
                'payment_method_type'  => 'credit',
                'payment_network' => 'DICL',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000,
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
            'class' => 'EE\Exception\BadRequestException',
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
                    ),
                    array(
                        'plan_name' => 'TestPlan2',
                        'payment_method' => 'card',
                        'payment_method_type' => 'debit',
                        'payment_network' => 'MAES',
                        'payment_issuer' => 'PUNB',
                        'percent_rate' => 250,
                        'fixed_rate' => 0,
                    ),
                    array(
                        'plan_name' => 'TestPlan2',
                        'payment_method' => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network' => 'DICL',
                        'payment_issuer' => 'ICIC',
                        'percent_rate' => 250,
                        'fixed_rate' => 0,
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
                            ),
                            array(
                                'plan_name' => 'TestPlan2',
                                'payment_method' => 'card',
                                'payment_method_type' => 'debit',
                                'payment_network' => 'MAES',
                                'payment_issuer' => 'PUNB',
                                'percent_rate' => 250,
                                'fixed_rate' => 0,
                            ),
                            array(
                                'plan_name' => 'TestPlan2',
                                'payment_method' => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network' => 'DICL',
                                'payment_issuer' => 'ICIC',
                                'percent_rate' => 250,
                                'fixed_rate' => 0,
                            ),
                            array(
                                'plan_name' => 'TestPlan2',
                                'payment_method' => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network' => 'DICL',
                                'payment_issuer' => 'SBIN',
                                'percent_rate' => 275,
                                'fixed_rate' => 0,
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
                                'fixed_rate' =>  0,
                                'expired_at' => NULL
                            )
                        )
                    ),
                    array(
                        'name' => 'testDefaultPlan',
                        'entity' => 'pricing',
                        'count' => 5,
                        'rules' => array(
                            array(),
                        ),
                    ),
                )
            ]
        ]
    ],

    'testMerchantAssignPricingPlan' => [
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
                        'percent_rate' => 1000
                    ),
                ),
            ],
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
                        'percent_rate' => 1000
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
                        'fixed_rate' => 10000
                    ),
                ),
            ],
        ]
    ]
];