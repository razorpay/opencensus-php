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

    'testMerchantAnalyticsDeviceValidation' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                    'filters' =>  [
                        'default' =>  [
                            [
                                'device' =>  ['desktop']
                            ],
                            [
                                'device' =>  ['tablet','computer'],
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
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid device',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantAnalyticsMethodValidation' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                    'filters' =>  [
                        'default' =>  [
                            [
                                'device' =>  ['desktop'],
                                'method' =>  'unknown_method',
                            ],
                            [
                                'device' =>  ['tablet','desktop'],
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
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

];
