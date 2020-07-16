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
                    'recurring_type'  => 'before',
                    'recurring_value' => 30,
                    'start_time'        => Carbon::now()->addDay(1)->getTimestamp(),
                    'end_time'       => Carbon::now()->addDay(60)->getTimestamp(),
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
    'testCreateOrderWithInvalidAmount' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'method'          => 'upi',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'      => 250000,
                    'frequency'       => 'monthly',
                    'recurring_type'  => 'before',
                    'recurring_value' => 30,
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
                    'description' => 'The max amount may not be greater than 200000.',
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
    'testCreateOrderWithIncorrectFrequency' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'method'          => 'upi',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'      => 150000,
                    'frequency'       => 'montly',
                    'recurring_type'  => 'before',
                    'recurring_value' => 30,
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
                    'description' => 'Not a valid frequency: montly',
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
                    'recurring_type'  => 'befre',
                    'recurring_value' => 30,
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
    'testCreateOrderWithInvalidStartTimeAndEndTime' => [
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
                    'recurring_type'  => 'before',
                    'recurring_value' => 30,
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
                    'description' => 'The start time should be less than end time and greater than current time.',
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
                    'recurring_type'  => 'before',
                    'recurring_value' => 30,
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
    ]
];
