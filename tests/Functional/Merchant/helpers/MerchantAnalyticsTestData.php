<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    'testMerchantAnalytics' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                    'filters' =>  [
                        'default' =>  [
                            [
                                'device' =>  ['desktop']
                            ],
                            [
                                'device' =>  ['tablet','mobile'],
                                'created_at' =>  ['gt' =>  10,'lte' =>  40]
                            ]
                        ],
                        'filter2' =>  [
                            [
                                'device' =>  ['desktop']
                            ]
                        ]
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
        'response' => [
            'status_code'   => 200,
            'success'       => true,
            'url'           => '/v1/analytics/dashboard',
            'content'       => [],
        ],
    ],

    'testMerchantAnalyticsPayment' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'filters' =>  [
                    'default' =>  [
                        [
                            'created_at' =>  ['gte' =>  0,'lte' =>  1505396637]
                        ]
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
        'response' => [
            'status_code'   => 200,
            'success'       => true,
            'url'           => '/v1/analytics/dashboard',
            'content'       => [],
        ],
    ],

    'testMerchantAnalyticsNoFilter' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'filters' =>  [
                    'default' =>  [
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
        'response' => [
            'status_code'   => 200,
            'success'       => true,
            'url'           => '/v1/analytics/dashboard',
            'content'       => [],
        ],
    ],

];
