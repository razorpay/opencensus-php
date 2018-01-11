<?php

//
// Test data corresponding to RZP\Tests\Unit\Models\MerchantAnalytics\AnalyticsTest.
// Every key below has 2 members corresponding to input parameters and expected
// response respectively.
//

return [
    'testAdditionOfMerchantIdFilterInInput' => [
        [
            'filters' =>  [
                'default' =>  [
                    [
                        'device' =>  ['desktop'],
                    ],
                    [
                        'device' =>  ['tablet', 'mobile'],
                        'created_at' =>  ['gt' =>  10, 'lte' =>  40],
                    ],
                ],
                'filter2' =>  [
                    [
                        'device' =>  ['desktop'],
                    ],
                ],
            ],
            'aggregations'  => [
                'agg1' => [
                    'agg_type'  => 'sum',
                    'details'   => [
                        'index'     => 'payments',
                        'column'    => 'base_amount',
                        'group_by'  => [
                            'method',
                            'status',
                        ],
                    ],
                ],
            ],
        ],

        [
            'filters' =>  [
                'default' =>  [
                    [
                        'device'        =>  ['desktop'],
                        'merchant_id'   => '10000000000000',
                    ],
                    [
                        'device'        =>  ['tablet', 'mobile'],
                        'created_at'    =>  ['gt' =>  10, 'lte' =>  40],
                        'merchant_id'   => '10000000000000',
                    ],
                ],
                'filter2' =>  [
                    [
                        'device'        =>  ['desktop'],
                        'merchant_id'   => '10000000000000',
                    ],
                ],
            ],
            'aggregations'  => [
                'agg1' => [
                    'agg_type'      => 'sum',
                    'details'       => [
                        'index'         => 'payments',
                        'column'        => 'base_amount',
                        'group_by'      => [
                            'method',
                            'status',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testAnalyticsInputEmptyFilter'  => [
        [
            'filters' =>  [
                'default' =>  [
                ],
            ],
            'aggregations'  => [
                'agg1' => [
                    'agg_type'      => 'sum',
                    'details'       => [
                        'index'         => 'payments',
                        'column'        => 'base_amount',
                        'group_by'      => [
                            'method',
                            'status',
                        ],
                    ],
                ],
            ],
        ],

        [
            'filters' =>  [
                'default' =>  [
                    [
                        'merchant_id'   => '10000000000000',
                    ],
                ],
            ],
            'aggregations'  => [
                'agg1' => [
                    'agg_type'  => 'sum',
                    'details'   => [
                        'index'     => 'payments',
                        'column'    => 'base_amount',
                        'group_by'  => [
                            'method',
                            'status',
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testAnalyticsInputOverrideMerchantId'  => [
        [
            'filters' =>  [
                'default' =>  [
                    [
                        'device'        =>  ['desktop'],
                        'merchant_id'   => '10000000000011',
                    ],
                    [
                        'device' =>  ['tablet', 'mobile'],
                        'created_at' =>  ['gt' =>  10, 'lte' =>  40],
                        'merchant_id'   => '10000000110000',
                    ],
                ],
            ],
            'aggregations'  => [
                'agg1' => [
                    'agg_type'  => 'sum',
                    'details'   => [
                        'index'     => 'payments',
                        'column'    => 'base_amount',
                        'group_by'  => [
                            'method',
                            'status',
                        ],
                    ],
                ],
            ],
        ],

        [
            'filters' =>  [
                'default' =>  [
                    [
                        'device'        =>  ['desktop'],
                        'merchant_id'   => '10000000000000',
                    ],
                    [
                        'device' =>  ['tablet', 'mobile'],
                        'created_at' =>  ['gt' =>  10, 'lte' =>  40],
                        'merchant_id'   => '10000000000000',
                    ],
                ],
            ],
            'aggregations'  => [
                'agg1' => [
                    'agg_type'  => 'sum',
                    'details'   => [
                        'index'     => 'payments',
                        'column'    => 'base_amount',
                        'group_by'  => [
                            'method',
                            'status',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testAnalyticsInputNoFilter'    => [
        [
            'aggregations'  => [
                'agg1' => [
                    'agg_type'  => 'sum',
                    'details'   => [
                        'index'     => 'payments',
                        'column'    => 'base_amount',
                        'group_by'  => [
                            'method',
                            'status',
                        ],
                    ],
                ],
            ],
        ],

        [
            'filters' =>  [
                'default' =>  [
                    [
                        'merchant_id'   => '10000000000000',
                    ],
                ],
            ],
            'aggregations'  => [
                'agg1' => [
                    'agg_type'  => 'sum',
                    'details'   => [
                        'index'     => 'payments',
                        'column'    => 'base_amount',
                        'group_by'  => [
                            'method',
                            'status',
                        ],
                    ],
                ],
            ],
        ],
    ],
];