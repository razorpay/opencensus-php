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
    'testAnalyticsIndustryLevelQueryInputWhenInvalidAggregationIsPassed' => [
        'aggregations' => [
            'checkout_industry_level_cr' => [
                'filter_key' => 'checkout_industry_level_cr',
                'agg_type'   => 'count',
                'details'    => [
                    'index'    => '123',
                    'mode'     => 'test',
                    'group_by' => [
                        'histogram_hourly',
                        'behav_submit_event',
                        'render_checkout_open_event',
                    ],
                ],
            ],
        ],
        'filters'      => [
            'default'           => [
                [
                    'producer_timestamp' => [
                        'gte' => 1684956600,
                        'lte' => 1684974800,
                    ],
                ],
            ],
            'checkout_industry_level_cr' => [
                [
                    'created_at'        => [
                        'gte' => 1684956600,
                        'lte' => 1684974800,
                    ],
                    'checkout_library'  => ['checkoutjs'],
                    'merchant_category' => 'shopping',
                ],
            ],
        ],
    ],
    'testMerchantAnalyticsSrQuery' => [
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
    'testAnalyticsIndustryLevelQueryInputWhenInvalidFilterIsPassed' => [
        'aggregations' => [
            'checkout_industry_level_cr' => [
                'filter_key' => 'checkout_industry_level_cr',
                'agg_type'   => 'count',
                'details'    => [
                    'index'    => 'cx_high_level_funnel',
                    'mode'     => 'test',
                    'group_by' => [
                        'histogram_hourly',
                        'behav_submit_event',
                        'render_checkout_open_event',
                    ],
                ],
            ],
        ],
        'filters'      => [
            'default'           => [
                [
                    'producer_timestamp' => [
                        'gte' => 1684956600,
                        'lte' => 1684974800,
                    ],
                ],
            ],
            'checkout_industry_level_cr' => [
                [
                    'created_at'       => [
                        'gte' => 1684956600,
                        'lte' => 1684974800,
                    ],
                    'checkout_library' => ['checkoutjs'],
                ],
            ],
        ],
    ],
    'testMerchantAnalyticsCrQuery' => [
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
    'testAnalyticsQueryInputWhenInvalidFilterKeyIsPassed' => [
        'aggregations' => [
            'num_transactions' => [
                'filter_key' => 'checkout_industry_level_cr',
                'agg_type'   => 'count',
                'details'    => [
                    'index'    => '123',
                    'mode'     => 'test',
                    'group_by' => [
                        'histogram_hourly',
                        'behav_submit_event',
                        'render_checkout_open_event',
                    ],
                ],
            ],
        ],
        'filters'      => [
            'default'           => [
                [
                    'producer_timestamp' => [
                        'gte' => 1684956600,
                        'lte' => 1684974800,
                    ],
                ],
            ],
            'checkout_industry_level_cr' => [
                [
                    'created_at'       => [
                        'gte' => 1684956600,
                        'lte' => 1684974800,
                    ],
                    'checkout_library' => ['checkoutjs'],
                ],
            ],
        ],
    ],
    'testAnalyticsQueryWhenIndustryLevelQueryInputExpectsDefaultMerchantNoAdded' => [
        'input'             => [
            'aggregations' => [
                'agg1'              => [
                    'filter_key' => 'agg1',
                    'agg_type'   => 'sum',
                    'details'    => [
                        'index'    => 'payments',
                        'column'   => 'base_amount',
                        'group_by' => [
                            'method',
                            'status',
                        ],
                    ],
                ],
                'checkout_industry_level_cr' => [
                    'filter_key' => 'checkout_industry_level_cr',
                    'agg_type'   => 'count',
                    'details'    => [
                        'index'    => 'cx_high_level_funnel',
                        'mode'     => 'test',
                        'group_by' => [
                            'histogram_hourly',
                            'behav_submit_event',
                            'render_checkout_open_event',
                        ],
                    ],
                ],
            ],
            'filters'      => [
                'default'           => [
                    [
                        'producer_timestamp' => [
                            'gte' => 1684956600,
                            'lte' => 1684974800,
                        ],
                    ],
                ],
                'checkout_industry_level_cr' => [
                    [
                        'created_at'        => [
                            'gte' => 1684956600,
                            'lte' => 1684974800,
                        ],
                        'checkout_library'  => ['checkoutjs'],
                        'merchant_category' => 'shopping',
                    ],
                ],
                'agg1'              => [
                    [
                        'created_at'       => [
                            'gte' => 1684956600,
                            'lte' => 1684974800,
                        ],
                        'checkout_library' => ['checkoutjs'],
                    ],
                ],
            ],
        ],
        'expected_response' => [
            'aggregations' => [
                'agg1' => [
                    'filter_key' => 'agg1',
                    'agg_type'   => 'sum',
                    'details'    => [
                        'index'    => 'payments',
                        'column'   => 'base_amount',
                        'group_by' => [
                            'method',
                            'status',
                        ],
                    ],
                ],
                'checkout_industry_level_cr' => [
                    'filter_key' => 'checkout_industry_level_cr',
                    'agg_type'   => 'count',
                    'details'    => [
                        'index'    => 'cx_high_level_funnel',
                        'mode'     => 'test',
                        'group_by' => [
                            'histogram_hourly',
                            'behav_submit_event',
                            'render_checkout_open_event',
                        ],
                    ],
                ],
            ],
            'filters'      => [
                'default'           => [
                    [
                        'producer_timestamp' => [
                            'gte' => 1684956600,
                            'lte' => 1684974800,
                        ],
                        'merchant_id'        => '10000000000000',
                    ],
                ],
                'checkout_industry_level_cr' => [
                    [
                        'created_at'        => [
                            'gte' => 1684956600,
                            'lte' => 1684974800,
                        ],
                        'checkout_library'  => [
                            'checkoutjs',
                        ],
                        'merchant_category' => 'shopping',
                    ],
                ],
                'agg1'              => [
                    [
                        'created_at'       => [
                            'gte' => 1684956600,
                            'lte' => 1684974800,
                        ],
                        'checkout_library' => [
                            'checkoutjs',
                        ],
                        'merchant_id'      => '10000000000000',
                    ],
                ],
            ],
        ],
    ],
    'testMerchantAnalyticsErrorMetricsMethodLevelQuery' => [
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
    'testMerchantAnalyticsErrorMetricsOverAllLevelQuery' => [
        'checkout_top_error_reasons' => [
            'total'           => 4,
            'last_updated_at' => 1652959842,
            'result'          => [
                [
                    'error_source'  => 'other_failure',
                    'error_reasons' => [
                        'Payment failed' => 1,
                    ],
                ],
                [
                    'error_source'  => 'bank_failure',
                    'error_reasons' => [
                        'Your payment didn\'t go through as it was declined by the bank. Try another payment method or contact your bank.' => 1,
                    ],
                ],
                [
                    'error_source'  => 'business_failure',
                    'error_reasons' => [
                        'Your payment could not be completed as this business accepts domestic (Indian) card payments only. Try another payment method.' => 1,
                    ],
                ],
                [
                    'error_source'  => 'customer_dropp_off',
                    'error_reasons' => [
                        'Payment was unsuccessful as you have not set the UPI PIN on the app. Try using another method.'                                   => 10,
                        'Payment was unsuccessful as the UPI app you\'re trying to pay with is not registered on this device. Try using another method.'    => 9,
                        'Payment was unsuccessful as the phone number linked to this UPI ID is changed/removed. Try using another method.'                 => 8,
                        'Payment was unsuccessful as you exceeded the amount limit for the day with this bank account. Try using another account.'         => 8,
                        'Payment was unsuccessful as you exceeded the number of attempts on the bank account with this UPI ID. Try using another account.' => 7,
                        'Payment was unsuccessful as you may not be registered on the app you\'re trying to pay with. Try using another method.'            => 6,
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
    'error_metrics_overall_pinot_response' => [
        'checkout_top_error_reasons' => [
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
