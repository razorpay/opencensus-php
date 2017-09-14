<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    'testRouteAnalytics' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'query'=> [
                    'filters' =>  [
                        'default' =>  [
                            [
                                'device' =>  ['desktop']
                            ],
                            [
                                'merchant_id' =>  'abc',
                                'device' =>  ['tablet','mobile'],
                                'created_at' =>  ['gt' =>  10,'lte' =>  40]
                            ]
                        ],
                        'filter2' =>  [
                            [
                                'merchant_id' =>  ['xyz','123'],
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
        ],
        'response' => [
            'status_code'   => 200,
            'success'       => true,
            'url'           => '/v1/analytics/dashboard',
            'content'       => [],
        ],
    ],

    'testRouteAnalyticsDeviceValidation' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'query'=> [
                    'filters' =>  [
                        'default' =>  [
                            [
                                'device' =>  ['desktop']
                            ],
                            [
                                'merchant_id' =>  'abc',
                                'device' =>  ['tablet','computer'],
                                'created_at' =>  ['gt' =>  10,'lte' =>  40]
                            ]
                        ],
                        'filter2' =>  [
                            [
                                'merchant_id' =>  ['xyz','123'],
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

    'testRouteAnalyticsMethodValidation' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'query'=> [
                    'filters' =>  [
                        'default' =>  [
                            [
                                'device' =>  ['desktop'],
                                'method' =>  'unknown_method',
                            ],
                            [
                                'merchant_id' =>  'abc',
                                'device' =>  ['tablet','desktop'],
                                'created_at' =>  ['gt' =>  10,'lte' =>  40]
                            ]
                        ],
                        'filter2' =>  [
                            [
                                'merchant_id' =>  ['xyz','123'],
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
