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

    'testCreateAndFetchDefaultPricingPlanAndNoMerchantSpecificPlan' => [
        'request' => [
            'url' => '/instant_refunds/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'custom_pricing' => false,
                'rules' => [
                    [
                        'amount_range_min' => 0,
                        'amount_range_max' => 100000,
                        'fixed_rate'       => 499,
                    ],
                    [
                        'amount_range_min' => 100000,
                        'amount_range_max' => 2500000,
                        'fixed_rate'       => 999,
                    ],
                    [
                        'amount_range_min' => 2500000,
                        'amount_range_max' => 4294967295,
                        'fixed_rate'       => 1999,
                    ],
                ],
            ],
        ],
    ],

    'testCreateAndFetchDefaultPricingV2PlanAndNoMerchantSpecificPlan' => [
        'request' => [
            'url' => '/instant_refunds/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'custom_pricing' => false,
                'rules' => [
                    [
                        'amount_range_min' => 0,
                        'amount_range_max' => 100000,
                        'fixed_rate'       => 799,
                    ],
                    [
                        'amount_range_min' => 100000,
                        'amount_range_max' => 2500000,
                        'fixed_rate'       => 1199,
                    ],
                    [
                        'amount_range_min' => 2500000,
                        'amount_range_max' => 4294967295,
                        'fixed_rate'       => 1499,
                    ],
                ],
            ],
        ],
    ],

    'testCreateAndFetchMerchantSpecificMethodIndependentPlan' => [
        'request' => [
            'url' => '/instant_refunds/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'custom_pricing' => false,
                'rules' => [
                    [
                        'amount_range_min' => 0,
                        'amount_range_max' => 100000,
                        'fixed_rate'       => 567,
                    ],
                    [
                        'amount_range_min' => 100000,
                        'amount_range_max' => 2500000,
                        'fixed_rate'       => 678,
                    ],
                    [
                        'amount_range_min' => 2500000,
                        'amount_range_max' => 4294967295,
                        'fixed_rate'       => 789,
                    ],
                ],
            ],
        ],
    ],

    'testCreateAndFetchMerchantSpecificMethodIndependentPlanWithMethods' => [
        'request' => [
            'url' => '/instant_refunds/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'custom_pricing' => false,
                'rules' => [
                    [
                        'amount_range_min' => 0,
                        'amount_range_max' => 100000,
                        'fixed_rate'       => 567,
                    ],
                    [
                        'amount_range_min' => 100000,
                        'amount_range_max' => 2500000,
                        'fixed_rate'       => 678,
                    ],
                    [
                        'amount_range_min' => 2500000,
                        'amount_range_max' => 4294967295,
                        'fixed_rate'       => 789,
                    ],
                ],
            ],
        ],
    ],

    'testCreateAndFetchMerchantSpecificMethodLevelPlanWithMethods' => [
        'request' => [
            'url' => '/instant_refunds/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'custom_pricing' => true,
                'rules' => [],
            ],
        ],
    ],

    'testCreateAndFetchMerchantSpecificModeLevelPlanWithMethods' => [
        'request' => [
            'url' => '/instant_refunds/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'custom_pricing' => true,
                'rules' => [],
            ],
        ],
    ],

    'testCreateAndFetchMerchantSpecificMethodIndependentPlanWithMethodsMoreThanSixSlabs' => [
        'request' => [
            'url' => '/instant_refunds/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'custom_pricing' => true,
                'rules' => [],
            ],
        ],
    ],

    'testFetchPercentRateForRefunds' => [
        'request' => [
            'url' => '/instant_refunds/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'custom_pricing' => true,
                'rules' => [],
            ],
        ],
    ],

    'testCreateAndFetchAllAndMethodIndependentPricingForRefunds' => [
        'request' => [
            'url' => '/instant_refunds/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'custom_pricing' => false,
                'rules' => [
                    [
                        'amount_range_min' => 0,
                        'amount_range_max' => 100000,
                        'fixed_rate'       => 567,
                    ],
                    [
                        'amount_range_min' => 100000,
                        'amount_range_max' => 2500000,
                        'fixed_rate'       => 678,
                    ],
                    [
                        'amount_range_min' => 2500000,
                        'amount_range_max' => 4294967295,
                        'fixed_rate'       => 789,
                    ],
                ],
            ],
        ],
    ],

    'testCreateAmountRangeActiveFalse' => [
        'request' => [
            'url' => '/instant_refunds/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'custom_pricing' => false,
                'rules' => [
                    [
                        'amount_range_min' => 0,
                        'amount_range_max' => 100000,
                        'fixed_rate'       => 567,
                    ],
                    [
                        'amount_range_min' => 100000,
                        'amount_range_max' => 2500000,
                        'fixed_rate'       => 567,
                    ],
                    [
                        'amount_range_min' => 2500000,
                        'amount_range_max' => 4294967295,
                        'fixed_rate'       => 567,
                    ],
                ],
            ],
        ],
    ],

    'testCreateAmountRangeActiveFalseWithMethods' => [
        'request' => [
            'url' => '/instant_refunds/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'custom_pricing' => false,
                'rules' => [
                    [
                        'amount_range_min' => 0,
                        'amount_range_max' => 100000,
                        'fixed_rate'       => 567,
                    ],
                    [
                        'amount_range_min' => 100000,
                        'amount_range_max' => 2500000,
                        'fixed_rate'       => 567,
                    ],
                    [
                        'amount_range_min' => 2500000,
                        'amount_range_max' => 4294967295,
                        'fixed_rate'       => 567,
                    ],
                ],
            ],
        ],
    ],
];
