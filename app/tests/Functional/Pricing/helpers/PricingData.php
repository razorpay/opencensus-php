<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testCreatePricingPlan' => [
        'request' => [
            'content' => [
                'plan_name' => 'haha',
                'payment_mode' => 'card',
                'payment_mode_type'  => 'credit',
                'payment_network' => 'DICL',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name' => 'haha',
                'entity' => 'pricing_plan',
                'count' => 1,
                'rules' => array(
                    array(
                        'plan_name' => 'haha',
                        'payment_mode' => 'card',
                        'payment_mode_type'  => 'credit',
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
                'payment_mode' => 'card',
                'payment_mode_type'  => 'credit',
                'payment_network' => 'MAES',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name' => 'haha',
                'payment_mode' => 'card',
                'payment_mode_type' => 'credit',
                'payment_network' => 'MAES',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000
            ],
        ],
    ],

    'testGetPricingPlan' => [
        'response' => [
            'content' => [
                'name' => 'testPlan',
                'entity' => 'pricing_plan',
                'count' => 4,
                'rules' => array(
                    array(
                        'plan_name' => 'testPlan',
                        'payment_mode' => 'card',
                        'payment_mode_type' => 'credit',
                        'payment_network' => 'MC',
                        'payment_issuer' => 'AXIS',
                        'percent_rate' => 0,
                        'fixed_rate' => 3000,
                    ),
                    array(
                        'plan_name' => 'testPlan',
                        'payment_mode' => 'card',
                        'payment_mode_type' => 'debit',
                        'payment_network' => 'MAES',
                        'payment_issuer' => 'PUNB',
                        'percent_rate' => 250,
                        'fixed_rate' => 0,
                    ),
                    array(
                        'plan_name' => 'testPlan',
                        'payment_mode' => 'card',
                        'payment_mode_type' => 'credit',
                        'payment_network' => 'DICL',
                        'payment_issuer' => 'ICIC',
                        'percent_rate' => 250,
                        'fixed_rate' => 0,
                    ),
                    array(
                        'plan_name' => "testPlan",
                        'gateway' => NULL,
                        'payment_mode' => "card",
                        'payment_mode_type' => "credit",
                        'payment_network' => "DICL",
                        'payment_issuer' => "SBIN",
                        'percent_rate' => "275",
                        'fixed_rate' => "0",
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
                'data' => array(
                    array(),
                    array(
                        'name' => "haha",
                        'entity' => "pricing_plan",
                        'count' => 1,
                        'rules' => array(
                            array(
                                'plan_name' =>  "haha",
                                'gateway' => NULL,
                                'payment_mode' =>  "card",
                                'payment_mode_type' => "credit",
                                'payment_network' =>  "DICL",
                                'payment_issuer' =>  "HDFC",
                                'percent_rate' =>  "1000",
                                'fixed_rate' =>  "0",
                                'expired_at' => NULL
                            )
                        )
                    ),
                    array(
                        'name' => 'testPlan',
                        'entity' => 'pricing_plan',
                        'count' => 4,
                        'rules' => array(
                            array(
                                'plan_name' => 'testPlan',
                                'payment_mode' => 'card',
                                'payment_mode_type' => 'credit',
                                'payment_network' => 'DICL',
                                'payment_issuer' => 'SBIN',
                                'percent_rate' => 275,
                                'fixed_rate' => 0,
                            ),
                            array(
                                'plan_name' => 'testPlan',
                                'payment_mode' => 'card',
                                'payment_mode_type' => 'credit',
                                'payment_network' => 'DICL',
                                'payment_issuer' => 'ICIC',
                                'percent_rate' => 250,
                                'fixed_rate' => 0,
                            ),
                            array(
                                'plan_name' => 'testPlan',
                                'payment_mode' => 'card',
                                'payment_mode_type' => 'debit',
                                'payment_network' => 'MAES',
                                'payment_issuer' => 'PUNB',
                                'percent_rate' => 250,
                                'fixed_rate' => 0,
                            ),
                            array(
                                'plan_name' => "testPlan",
                                'gateway' => NULL,
                                'payment_mode' => "card",
                                'payment_mode_type' => "credit",
                                'payment_network' => "MC",
                                'payment_issuer' => "AXIS",
                                'percent_rate' => "0",
                                'fixed_rate' => "3000",
                            ),
                        )
                    )
                )
            ]
        ]
    ],

    'testMerchantAssignPricingPlan' => [
        'request' => [
            'url' => '/merchants/363e4efa820b0c06208ccd99/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name' => 'haha',
                'entity' => 'pricing_plan',
                'count' => 1,
                'rules' => array(
                    array(
                        'payment_mode' => 'card',
                        'payment_mode_type' => 'credit',
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
            'url' => '/merchants/363e4efa820b0c06208ccd99/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'name' => 'haha',
                'entity' => 'pricing_plan',
                'count' => 1,
                'rules' => array(
                    array(
                        'payment_mode' => 'card',
                        'payment_mode_type' => 'credit',
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
            'url' => '/merchants/363e4efa820b0c06208ccd99/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name' => 'testPlan',
                'entity' => 'pricing_plan',
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
            'url' => '/merchants/363e4efa820b0c06208ccd99/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testMerchantGetPricingPlan' => [
        'request' => [
            'url' => '/merchants/543cdc2e93ae13f61f52b3eb/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id' => '5053edf267a4a6d1d26b43df',
                'name' => 'testFixturePlan',
                'entity' => 'pricing_plan',
                'count' => 1,
                'rules' => array(
                    array(
                        'payment_mode' => 'card',
                        'payment_mode_type' => 'credit',
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