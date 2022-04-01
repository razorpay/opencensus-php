<?php


use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateUpiRecurringOrder' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'method'          => 'upi',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'      => 150000,
                    'frequency'       => 'monthly',
                    'start_at'        => Carbon::now()->addDay(1)->getTimestamp(),
                    'expire_at'       => Carbon::now()->addDay(60)->getTimestamp(),
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
            ],
        ],
    ],

    'testCreateOrderWithMaxAmountLesserThanMinLimit' => [
    'request' => [
        'content' => [
            'amount'          => 99,
            'currency'        => 'INR',
            'method'          => 'upi',
            'customer_id'     => 'cust_100000customer',
            'payment_capture' => 1,
            'token'           => [
                'max_amount'      => 99,
                'frequency'       => 'monthly',
                'start_at'        => Carbon::now()->addDay(1)->getTimestamp(),
                'expire_at'       => Carbon::now()->addDay(60)->getTimestamp(),
            ]
        ],
        'method'    => 'POST',
        'url'       => '/orders',
    ],
    'response' => [
        'content' => [
            'error' => [
                'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                'description' => 'Max amount for UPI recurring payment cannot be less than Rs. 1.00',
                'field' => 'max_amount'
            ],
        ],
        'status_code' => 400,
    ],
    'exception' => [
        'class' => 'RZP\Exception\BadRequestValidationFailureException',
        'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
    ],
],
    'testCreateOrderWithMaxAmountGreaterThanMaxLimit' => [
        'request' => [
            'content' => [
                'amount'          => 200,
                'currency'        => 'INR',
                'method'          => 'upi',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'      => 210000000,
                    'frequency'       => 'monthly',
                    'start_at'        => Carbon::now()->addDay(1)->getTimestamp(),
                    'expire_at'       => Carbon::now()->addDay(60)->getTimestamp(),
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Max amount for UPI recurring payment cannot be greater than Rs. 200000.00',
                    'field' => 'max_amount'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateOrderWithInvalidRecurringType' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'method'          => 'upi',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'      => 150000,
                    'frequency'       => 'monthly',
                    'start_at'        => Carbon::now()->addDay(1)->getTimestamp(),
                    'expire_at'       => Carbon::now()->addDay(60)->getTimestamp(),
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid recurring type: befre',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
    'testCreateOrderWithStartTimeGreaterThanEndTime' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'method'          => 'upi',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'      => 150000,
                    'frequency'       => 'monthly',
                    'start_at'        => Carbon::now()->addDay(60)->getTimestamp(),
                    'expire_at'       => Carbon::now()->addDay(1)->getTimestamp(),
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The start time should be less than end time',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
    'testCreateOrderWithoutStartAndEndTime' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'method'          => 'upi',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'      => 150000,
                    'frequency'       => 'monthly',
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
            ],
        ],
    ],

    'testCreateOrderWithStartTimeAndNoEndTime' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'method'          => 'upi',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'      => 150000,
                    'frequency'       => 'monthly',
                    'start_at'        => Carbon::now()->addDay(1)->getTimestamp(),
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
            ],
        ],
    ],

    'testCreateOrderWithEndTimeAndNoStartTime' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'method'          => 'upi',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'      => 150000,
                    'frequency'       => 'monthly',
                    'expire_at'       => Carbon::now()->addYear(5)->getTimestamp(),
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
            ],
        ],
    ],

    'testCreateOrderWithoutFrequency' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'method'          => 'upi',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'      => 150000,
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
            ],
        ],
    ],

    'testCreateOrderWithAsPresentedFrequency' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'method'          => 'upi',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'      => 150000,
                    'frequency'       => 'as_presented',
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
            ],
        ],
    ],

    'testPreferencesForUpiRecurringOrder' => [
        'request' => [
            'content' => [],
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'upi' => true,
                    'recurring' => [
                        'upi' => true,
                    ]
                ],
                'order' => [
                    'token' => [
                        'recurring_type' => 'before',
                        'frequency' => 'monthly'
                    ]
                ],
            ],
        ],
    ],
    'testCreateOrderAmountGreaterThanMaxAmount' => [
        'request' => [
            'content' => [
                'amount'          => 21000000,
                'currency'        => 'INR',
                'method'          => 'upi',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'      => 20000000,
                    'frequency'       => 'monthly',
                    'start_at'        => Carbon::now()->addDay(1)->getTimestamp(),
                    'expire_at'       => Carbon::now()->addDay(60)->getTimestamp(),
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The order amount cannot be greater than the token max amount for upi recurring',
                    'field' => 'max_amount'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
];
