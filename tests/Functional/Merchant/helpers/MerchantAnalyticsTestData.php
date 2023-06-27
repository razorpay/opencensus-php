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
    'testMerchantAnalyticsSrQuery' => [
        'request' => [
            'url'     => '/merchant/analytics',
            'method'  => 'post',
            'content' => [
                'aggregations' => [
                    'checkout_method_level_sr' => [
                        'agg_type' => 'count',
                        'details'  => [
                            'index'            => 'cx_high_level_funnel',
                            'histogram_column' => 'created_at',
                            'group_by'         => [
                                'status',
                                'histogram_hourly',
                                'last_selected_method',
                            ],
                        ],
                    ],
                ],
                'filters'      => [
                    'default' => [
                        [
                            'producer_timestamp' => [
                                'gte' => 1684956600,
                                'lte' => 1684974800,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'status_code' => 200,
            'success'     => true,
            'content'     => [
                'checkout_overall_sr' => [
                    'total'           => 3,
                    'last_updated_at' => 1652959842,
                    'result'          => [
                        [
                            'timestamp'            => 1684956600,
                            'last_selected_method' => 'card',
                            'value'                => 33.333333333333336,
                        ],
                        [
                            'timestamp'            => 1684960200,
                            'last_selected_method' => 'wallet',
                            'value'                => 50,
                        ],
                        [
                            'timestamp'            => 1684967400,
                            'last_selected_method' => 'wallet',
                            'value'                => 0,
                        ],
                    ],
                ],
            ],
        ],
    ],
    'testMerchantAnalyticsSrQueryWhenRequiredFieldIsNotPassedExpectsBadRequestException' => [
        'request' => [
            'url'     => '/merchant/analytics',
            'method'  => 'post',
            'content' => [
                'aggregations' => [
                    'checkout_method_level_sr' => [
                        'agg_type' => 'count',
                        'details'  => [
                            'index'            => 'cx_high_level_funnel',
                            'histogram_column' => 'created_at',
                            'group_by'         => [
                                'histogram_hourly',
                                'last_selected_method',
                            ],
                        ],
                    ],
                ],
            ],
            'filters' => [
                'default' => [
                    [
                        'producer_timestamp' => [
                            'gte' => 1684956600,
                            'lte' => 1684974800,
                        ],
                    ],
                ],
            ],
        ],
    ],
    'testMerchantAnalyticsCrQuery' => [
        'request' => [
            'url'     => '/merchant/analytics',
            'method'  => 'post',
            'content' => [
                'aggregations' => [
                    'checkout_method_level_cr' => [
                        'agg_type' => 'count',
                        'details'  => [
                            'index'            => 'cx_high_level_funnel',
                            'histogram_column' => 'created_at',
                            'group_by'         => [
                                'behav_submit_event',
                                'render_checkout_open_event',
                                'histogram_hourly',
                                'last_selected_method',
                            ],
                        ],
                    ],
                ],
                'filters'      => [
                    'default' => [
                        [
                            'producer_timestamp' => [
                                'gte' => 1684956600,
                                'lte' => 1684974800,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'checkout_overall_cr' => [
                    'total'           => 3,
                    'last_updated_at' => 1652959842,
                    'result'          => [
                        [
                            'timestamp'            => 1684956600,
                            'last_selected_method' => 'card',
                            'value'                => 33.333333333333336,
                        ],
                        [
                            'timestamp'            => 1684960200,
                            'last_selected_method' => 'wallet',
                            'value'                => 0,
                        ],
                        [
                            'timestamp'            => 1684967400,
                            'last_selected_method' => 'wallet',
                            'value'                => 100,
                        ],
                    ],
                ],
            ],
        ],
    ],
    'testMerchantAnalyticsCrQueryWhenRequiredFieldIsNotPassedExpectsBadRequestException' => [
        'request' => [
            'url'     => '/merchant/analytics',
            'method'  => 'post',
            'content' => [
                'aggregations' => [
                    'checkout_method_level_sr' => [
                        'agg_type' => 'count',
                        'details'  => [
                            'index'            => 'cx_high_level_funnel',
                            'histogram_column' => 'created_at',
                            'group_by'         => [
                                'behav_submit_event',
                                'histogram_hourly',
                                'last_selected_method',
                            ],
                        ],
                    ],
                ],
                'filters'      => [
                    'default' => [
                        [
                            'producer_timestamp' => [
                                'gte' => 1684956600,
                                'lte' => 1684974800,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'testMerchantAnalyticsErrorMetricsQuery' => [
        'request' => [
            'url'     => '/merchant/analytics',
            'method'  => 'post',
            'content' => [
                'aggregations' => [
                    'checkout_method_level_top_error_reasons' => [
                        'agg_type' => 'count',
                        'details'  => [
                            'group_by' => [
                                'last_selected_method',
                                'internal_error_code',
                            ],
                        ],
                    ],
                ],
            ],
            'filters' => [
                'default' => [
                    [
                        'producer_timestamp' => [
                            'gte' => 1684956600,
                            'lte' => 1684974800,
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'checkout_method_level_top_error_reasons' => [
                    'total'           => 5,
                    'last_updated_at' => 1652959842,
                    'result'          => [
                        [
                            'last_selected_method' => 'emi',
                            'error_source'         => 'other_failure',
                            'error_reasons'        => [
                                'Payment failed' => 1,
                            ],
                        ],
                        [
                            'last_selected_method' => 'netbanking',
                            'error_source'         => 'bank_failure',
                            'error_reasons'        => [
                                'Your payment didn\'t go through as it was declined by the bank. Try another payment method or contact your bank.' => 1,
                            ],
                        ],
                        [
                            'last_selected_method' => 'card',
                            'error_source'         => 'business_failure',
                            'error_reasons'        => [
                                'Your payment could not be completed as this business accepts domestic (Indian) card payments only. Try another payment method.' => 1,
                            ],
                        ],
                        [
                            'last_selected_method' => 'upi',
                            'error_source'         => 'customer_dropp_off',
                            'error_reasons'        => [
                                'Payment was unsuccessful as you have not set the UPI PIN on the app. Try using another method.'                                   => 10,
                                'Payment was unsuccessful as the UPI app you\'re trying to pay with is not registered on this device. Try using another method.'   => 9,
                                'Payment was unsuccessful as the phone number linked to this UPI ID is changed/removed. Try using another method.'                 => 8,
                                'Payment was unsuccessful as you exceeded the amount limit for the day with this bank account. Try using another account.'         => 8,
                                'Payment was unsuccessful as you exceeded the number of attempts on the bank account with this UPI ID. Try using another account.' => 7,
                                'Payment was unsuccessful as you may not be registered on the app you\'re trying to pay with. Try using another method.'           => 6,
                            ],
                        ],
                        [
                            'last_selected_method' => 'wallet',
                            'error_source'         => 'customer_dropp_off',
                            'error_reasons'        => [
                                'Your payment could not be completed due to insufficient wallet balance. Try another payment method.' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'testMerchantAnalyticsErrorMetricsQueryWhenRequiredFieldIsNotPassedExpectsBadRequestException' => [
        'request' => [
            'url'     => '/merchant/analytics',
            'method'  => 'post',
            'content' => [
                'aggregations' => [
                    'checkout_method_level_top_error_reasons' => [
                        'agg_type' => 'count',
                        'details'  => [
                            'group_by' => [
                                'last_selected_method',
                            ],
                        ],
                    ],
                ],
            ],
            'filters' => [
                'default' => [
                    [
                        'producer_timestamp' => [
                            'gte' => 1684956600,
                            'lte' => 1684974800,
                        ],
                    ],
                ],
            ],
        ],
    ],
    'sr_pinot_response' => [
        'checkout_overall_sr' => [
            'total'           => 8,
            'last_updated_at' => 1652959842,
            'result'          => [
                [
                    'timestamp'            => 1684956600,
                    'last_selected_method' => 'card',
                    'status'               => 'authorized',
                    'value'                => 5,
                ],
                [
                    'timestamp'            => 1684956600,
                    'last_selected_method' => 'card',
                    'status'               => 'created',
                    'value'                => 10,
                ],
                [
                    'timestamp'            => 1684960200,
                    'last_selected_method' => 'wallet',
                    'status'               => 'authorized',
                    'value'                => 18,
                ],
                [
                    'timestamp'            => 1684960200,
                    'last_selected_method' => 'wallet',
                    'status'               => 'failed',
                    'value'                => 18,
                ],
                [
                    'timestamp'            => 1684967400,
                    'last_selected_method' => 'wallet',
                    'status'               => 'pending',
                    'value'                => 18,
                ],
            ],
        ],
    ],
    'cr_pinot_response' => [
        'checkout_overall_cr' => [
            'total'           => 8,
            'last_updated_at' => 1652959842,
            'result'          => [
                [
                    'timestamp'                  => 1684956600,
                    'last_selected_method'       => 'card',
                    'behav_submit_event'         => 1,
                    'render_checkout_open_event' => 1,
                    'value'                      => 5,
                ],
                [
                    'timestamp'                  => 1684956600,
                    'last_selected_method'       => 'card',
                    'behav_submit_event'         => 0,
                    'render_checkout_open_event' => 1,
                    'value'                      => 10,
                ],
                [
                    'timestamp'                  => 1684960200,
                    'last_selected_method'       => 'wallet',
                    'behav_submit_event'         => 0,
                    'render_checkout_open_event' => 1,
                    'value'                      => 18,
                ],
                [
                    'timestamp'                  => 1684967400,
                    'last_selected_method'       => 'wallet',
                    'behav_submit_event'         => 1,
                    'render_checkout_open_event' => 1,
                    'value'                      => 18,
                ],
            ],
        ],
    ],
    'error_metrics_pinot_response' => [
        'checkout_method_level_top_error_reasons' => [
            'total'           => 3,
            'last_updated_at' => 1652959842,
            'result'          => [
                [
                    'internal_error_code'  => 'BAD_REQUEST_PAYMENT_FAILED',
                    'last_selected_method' => 'emi',
                    'value'                => 1,
                ],
                [
                    'internal_error_code'  => 'BAD_REQUEST_PAYMENT_FAILED',
                    'last_selected_method' => 'netbanking',
                    'value'                => 1,
                ],
                [
                    'internal_error_code'  => 'BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_GATEWAY',
                    'last_selected_method' => 'card',
                    'value'                => 1,
                ],
                [
                    'internal_error_code'  => 'BAD_REQUEST_UPI_MPIN_NOT_SET',
                    'last_selected_method' => 'upi',
                    'value'                => 10,
                ],
                [
                    'internal_error_code'  => 'BAD_REQUEST_PSP_DOESNT_EXIST',
                    'last_selected_method' => 'upi',
                    'value'                => 9,
                ],
                [
                    'internal_error_code'  => 'BAD_REQUEST_REGISTERED_MOBILE_NUMBER_NOT_FOUND',
                    'last_selected_method' => 'upi',
                    'value'                => 8,
                ],
                [
                    'internal_error_code'  => 'BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED',
                    'last_selected_method' => 'upi',
                    'value'                => 8,
                ],
                [
                    'internal_error_code'  => 'BAD_REQUEST_TRANSACTION_FREQUENCY_LIMIT_EXCEEDED',
                    'last_selected_method' => 'upi',
                    'value'                => 7,
                ],
                [
                    'internal_error_code'  => 'BAD_REQUEST_UPI_INVALID_DEVICE_FINGERPRINT',
                    'last_selected_method' => 'upi',
                    'value'                => 6,
                ],
                [
                    'internal_error_code'  => 'GATEWAY_ERROR_INSUFFICIENT_FUNDS_REMITTER_ACCOUNT',
                    'last_selected_method' => 'upi',
                    'value'                => 4,
                ],
                [
                    'internal_error_code'  => 'BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE',
                    'last_selected_method' => 'wallet',
                    'value'                => 1,
                ],
            ],
        ],
    ],
];
