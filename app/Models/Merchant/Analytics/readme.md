# DataProcessor Class

The `DataProcessor` class is responsible for processing pinot response and performing calculations on it for merchant analytics.

## Function Details

### processMerchantAnalyticsResponse

This function processes the pinot response and performs calculations on the data.

**Data Flow**
- For each aggregation in the response:
    - If the aggregation name is related to conversion rate (CR), the `calculateCR` function is called to calculate the CR for each data point in the aggregation.
    - If the aggregation name is related to success rate (SR), the `calculateSR` function is called to calculate the SR for each data point in the aggregation.
    - If the aggregation name is related to error metrics, the `getErrorMetrics` function is called to get error metrics for each data point in the aggregation.
- The processed response is returned.

### calculateCR

This function calculates the Conversion Rate (CR) for each data point in the given aggregation.

**Parameters**
- `data`: The data points to calculate the CR for.

```php
$data = [
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
];
```

**Return Value**
An array containing the calculated CR for each data point.

**Data Flow**
- Initialize empty arrays `$groupedCounts` and `$otherFields`.
- Get the grouping fields for CR from the `Constant` class.
- Iterate over each data point:
    - Extract the group key using the `extractGroupKey` function. Group key is the extracted based on grouping fields.
        - In case of grouping key for CR, the grouping fields are `behav_submit_event` and `render_checkout_open_event`.
        - The `group key` will be values of fields other than the grouping fields
        - Note - As `value` is count of occurrences it is not considered in group key
        - ```php
            [
                'timestamp'                  => 1684956600,
                'last_selected_method'       => 'card',
                'behav_submit_event'         => 0,
                'render_checkout_open_event' => 1,
                'value'                      => 10,
            ]
          ```
        - For above example group key is  -  **`1684956600-card`**
    - Update the grouped counts using the `updateGroupedCounts` function.
        - This function based on group key groups the count of `behav_submit_event` and `render_checkout_open_event`
        - For the above example
            - The `total_number_of_submit_events` will be `0 * value(count of occurrences)` => `0 * 10` => `0` as `behav_submit_event` is false (i.e., 0)
            - The `total_number_of_open_events` will be `1 * value(count of occurrences)` => `0 * 10` => `10` as `render_checkout_open_event` is true (i.e., 1)
    - Update the other fields using the `updateOtherFields` function.
        - This function based on group key groups the other fields i.e., fields other than grouping fields (`behav_submit_event`, `render_checkout_open_event`)
        - For the above example
        - ```php
            [
                '1684956600-card' => [
                    'timestamp' => 1684956600,
                    'last_selected_method' => 'card',
                    'value' => 5
                ]
            ]
          ```
### Values of $groupedCounts and $otherFields for Entire Input Data
```php
$groupedCounts = [
    '1684956600-card' => [
        'total_number_of_submit_events' => 5,
        'total_number_of_open_events' => 15
    ],
    '1684960200-wallet' => [
        'total_number_of_submit_events' => 0,
        'total_number_of_open_events' => 18
    ],
    '1684967400-wallet' => [
        'total_number_of_submit_events' => 18,
        'total_number_of_open_events' => 18
    ]
];

$otherFields = [
    '1684956600-card' => [
        'timestamp' => 1684956600,
        'last_selected_method' => 'card',
        'value' => 5
    ],
    '1684960200-wallet' => [
        'timestamp' => 1684960200,
        'last_selected_method' => 'wallet',
        'value' => 18
    ],
    '1684967400-wallet' => [
        'timestamp' => 1684967400,
        'last_selected_method' => 'wallet',
        'value' => 18
    ]
];
```
- Divide the grouped counts by the total number of submit events and the total number of open events and multiplies by 100 using the `calculatePercentage` function.
```php
$groupedCounts = [
    '1684956600-card' => [
        'total_number_of_submit_events' => 5,
        'total_number_of_open_events' => 15,
        'value' => 33.333333333333336,
    ],
    '1684960200-wallet' => [
        'total_number_of_submit_events' => 0,
        'total_number_of_open_events' => 18,
        'value' => 0,
    ],
    '1684967400-wallet' => [
        'total_number_of_submit_events' => 18,
        'total_number_of_open_events' => 18,
        'value' => 100,
    ]
];
```
- Format the result by merging the other fields and grouped counts using the `formatResult` function.
- Return the formatted result.
```php
$finalResult = [
    'checkout_overall_cr' => [
        'total' => 8,
        'last_updated_at' => 1652959842,
        'result' => [
            [
                'timestamp' => 1684956600,
                'last_selected_method' => 'card',
                'value' => 33.333333333333336
            ],
            [
                'timestamp' => 1684960200,
                'last_selected_method' => 'wallet',
                'value' => 0
            ],
            [
                'timestamp' => 1684967400,
                'last_selected_method' => 'wallet',
                'value' => 100
            ]
        ]
    ]
];
```

### calculateSR

This function calculates the Success Rate (SR) for each data point in the given aggregation.

**Parameters**
- `data`: The data points to calculate the SR for.

```php
$data = [
    'checkout_overall_sr' => [
        'total' => 8,
        'last_updated_at' => 1652959842,
        'result' => [
            [
                'timestamp' => 1684956600,
                'last_selected_method' => 'card',
                'status' => 'authorized',
                'value' => 5
            ],
            [
                'timestamp' => 1684956600,
                'last_selected_method' => 'card',
                'status' => 'created',
                'value' => 10
            ],
            [
                'timestamp' => 1684960200,
                'last_selected_method' => 'wallet',
                'status' => 'authorized',
                'value' => 18
            ],
            [
                'timestamp' => 1684960200,
                'last_selected_method' => 'wallet',
                'status' => 'failed',
                'value' => 18
            ],
            [
                'timestamp' => 1684967400,
                'last_selected_method' => 'wallet',
                'status' => 'pending',
                'value' => 18
            ]
        ]
    ]
];
```

**Return Value**
An array containing the calculated SR for each data point.

**Data Flow**
- Iterate over each data point:
    - Extract the group key using the `extractGroupKey` function. Group key is the extracted based on grouping fields.
        - In case of grouping key for CR, the grouping fields are `status`.
        - The `group key` will be values of fields other than the grouping fields
        - Note - As `value` is count of occurrences it is not considered in group key
        - ```php
            [
                'timestamp' => 1684956600,
                'last_selected_method' => 'card',
                'status' => 'authorized',
                'value' => 5
           ]
          ```
  - For above example group key is - `1684956600-card`
  - Update the authorized payments counts using the `updateAuthorizedPaymentsCounts` function.
      - This function based on group key groups the count of `status` field if `status` field is in `['authorized', 'captured', 'refunded']` i.e., Successful payments.
      - For the above example
          - The `number_of_successful_payments` will be `value(count of occurrences)` if payment is successful => `5`.
  - Update the total payments counts using the `updateTotalPaymentsCounts` function.
      - This function based on group key groups the count of number of payments.
      - For the above example
          - The `number_of_total_payments` will be `value(count of occurrences)` => `5`.
  - Update the other fields using the `updateOtherFields` function.
      - This function based on group key groups the other fields i.e., fields other than grouping fields (`status`)
      - For the above example
      - ```php
          [
              '1684956600-card' => [
                  'timestamp' => 1684956600,
                  'last_selected_method' => 'card',
                  'value' => 5
              ]
          ]
        ```
```php
$groupedCounts = [
    '1684956600-card' => [
        'number_of_successful_payments' => 5,
        'number_of_total_payments' => 15
    ],
    '1684960200-wallet' => [
        'number_of_successful_payments' => 18,
        'number_of_total_payments' => 36
    ],
    '1684967400-wallet' => [
        'number_of_successful_payments' => 0,
        'number_of_total_payments' => 18
    ]
];
$otherFields = [
    '1684956600-card' => [
        'timestamp' => 1684956600,
        'last_selected_method' => 'card',
        'value' => 5
    ],
    '1684960200-wallet' => [
        'timestamp' => 1684960200,
        'last_selected_method' => 'wallet',
        'value' => 18
    ],
    '1684967400-wallet' => [
        'timestamp' => 1684967400,
        'last_selected_method' => 'wallet',
        'value' => 18
    ]
];
```
- Divide the grouped counts by the number of successful payments and the total number of payments and multiplies by 100 using the `calculatePercentage` function.
- Format the result by merging the other fields and grouped counts using the `formatResult` function.
- Return the formatted result.

```php
$finalResponse = [
    'status_code' => 200,
    'success' => true,
    'data' => [
        'checkout_overall_sr' => [
            'total' => 8,
            'last_updated_at' => 1652959842,
            'result' => [
                [
                    'timestamp' => 1684956600,
                    'last_selected_method' => 'card',
                    'value' => 0.33333333333333331
                ],
                [
                    'timestamp' => 1684960200,
                    'last_selected_method' => 'wallet',
                    'value' => 0.5
                ],
                [
                    'timestamp' => 1684967400,
                    'last_selected_method' => 'wallet',
                    'value' => 0
                ]
            ]
        ]
    ]
];
```

## getErrorMetrics

The `getErrorMetrics` function is a private function that calculates error metrics based on the provided data. It takes an array of data and an aggregation name as parameters and returns an array containing the calculated error metrics.

**Parameters**

- `data`: An array of data points to calculate error metrics for. The array is passed by reference, allowing modifications to the original data.

```php
$data = [
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
];
```

**Data Flow**

The `getErrorMetrics` function performs the following steps:

- Calls the `getErrorSourceCategoryForFailureAnalysis` method to get the error source category for failure analysis, based on the `Constant::INTERNAL_ERROR_CODE` and `Constant::LAST_SELECTED_METHOD` values.
- Calls the `getErrorReasonForErrorMetrics` method to get the error reason for error metrics, based on the `Constant::INTERNAL_ERROR_CODE` and `Constant::LAST_SELECTED_METHOD` values.
    - After getting error reason and source. Data will look like below.
    - ```php
        [
            'error_source' => 'business_failure',
            'last_selected_method' => 'card',
            'error_description' => 'International cards are not allowed for this merchant on payment gateway',
            'value' => 10,
        ]
        ```
- Iterate over each data point in the `data` array.
    - Extract the group key using the `extractGroupKey` function. Group key is the extracted based on grouping fields.
        - In case of grouping key for Error metrics, the grouping field is `error_description`.
        - The `group key` will be values of fields other than the grouping fields
        - Note - As `value` is count of occurrences it is not considered in group key
        - ```php
            [
                'error_source' => 'business_failure',
                'last_selected_method' => 'card',
                'error_description' => 'International cards are not allowed for this merchant on payment gateway',
                'value' => 10,
            ]
            ```
        - For above example group key is - `business_failure-card`
    - Update the grouped counts for error metrics using the `updateGroupedCountsForErrorMetrics` method. Groups all the error descriptions based on group key.
    - Update the other fields using the `updateOtherFields` method. This function based on group key groups the other fields i.e., fields other than grouping fields (`error_description`)
    - Examples for $groupedCounts and $otherFields are given below.
```php
$groupedCounts = [
    'emi-other_failure' => [
        'Payment failed' => 1
    ],
    'bank_failure-netbanking' => [
        'Your payment didn\'t go through as it was declined by the bank. Try another payment method or contact your bank.' => 1
    ],
    'business_failure-card' => [
        'International cards are not allowed for this merchant on payment gateway' => 1
    ],
    'customer_dropp_off-upi' => [
        'Payment was unsuccessful as you have not set the UPI PIN on the app. Try using another method.' => 10,
        'Payment was unsuccessful as the UPI app you\'re trying to pay with is not registered on this device. Try using another method.' => 9,
        'Payment was unsuccessful as the phone number linked to this UPI ID is changed/removed. Try using another method.' => 8,
        'Payment was unsuccessful as you exceeded the amount limit for the day with this bank account. Try using another account.' => 8,
        'Payment was unsuccessful as you exceeded the number of attempts on the bank account with this UPI ID. Try using another account.' => 7,
        'Payment was unsuccessful as you may not be registered on the app you\'re trying to pay with. Try using another method.' => 6,
        'Transaction failed due to insufficient funds.' => 4
    ],
    'customer_dropp_off-wallet' => [
        'Your payment could not be completed due to insufficient wallet balance. Try another payment method.' => 1
    ]
];

$otherFields = [
    'emi-other_failure' => [
        'last_selected_method' => 'emi',
        'error_source' => 'other_failure'
    ],
    'bank_failure-netbanking' => [
        'last_selected_method' => 'netbanking',
        'error_source' => 'bank_failure'
    ],
    'business_failure-card' => [
        'last_selected_method' => 'card',
        'error_source' => 'business_failure'
    ],
    'customer_dropp_off-upi' => [
        'last_selected_method' => 'upi',
        'error_source' => 'customer_dropp_off'
    ],
    'customer_dropp_off-wallet' => [
        'last_selected_method' => 'wallet',
        'error_source' => 'customer_dropp_off'
    ]
];
```
- `filterTopErrorReasonsForEachGroup` Processes the $groupedCounts and return top errors based on error reason limit current limit is 6.
```php
$groupedCounts = [
    'emi-other_failure' => [
        'Payment failed' => 1
    ],
    'bank_failure-netbanking' => [
        'Your payment didn\'t go through as it was declined by the bank. Try another payment method or contact your bank.' => 1
    ],
    'business_failure-card' => [
        'International cards are not allowed for this merchant on payment gateway' => 1
    ],
    'customer_dropp_off-upi' => [
        'Payment was unsuccessful as you have not set the UPI PIN on the app. Try using another method.' => 10,
        'Payment was unsuccessful as the UPI app you\'re trying to pay with is not registered on this device. Try using another method.' => 9,
        'Payment was unsuccessful as the phone number linked to this UPI ID is changed/removed. Try using another method.' => 8,
        'Payment was unsuccessful as you exceeded the amount limit for the day with this bank account. Try using another account.' => 8,
        'Payment was unsuccessful as you exceeded the number of attempts on the bank account with this UPI ID. Try using another account.' => 7,
        'Payment was unsuccessful as you may not be registered on the app you\'re trying to pay with. Try using another method.' => 6
    ],
    'customer_dropp_off-wallet' => [
        'Your payment could not be completed due to insufficient wallet balance. Try another payment method.' => 1
    ]
];
```
- Return the formatted result for error metrics using the `formatResultForErrorMetrics` method, which combines the other fields and grouped counts into a single array.

```php
[
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
                    'International cards are not allowed for this merchant on payment gateway' => 1,
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
]
```
