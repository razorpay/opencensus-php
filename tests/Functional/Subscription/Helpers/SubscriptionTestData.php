<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreatePlan' => [
        'request' => [
            'url' => '/plans',
            'method' => 'post',
            'content' => [
                'amount'            => 2000,
                'currency'          => 'INR',
                'period'            => 'monthly',
                'interval'          => 2,
                'name'              => 'test plan',
            ],
        ],
        'response' => [
            'content' => [
                'amount' =>  2000,
                'currency' => 'INR',
                'period' => 'monthly',
                'interval' => 2,
                'name' => 'test plan',
                'notes' => [],
            ],
        ],
    ],

    'testCreatePlanWithBadMonthlyIntervalPeriod' => [
        'request' => [
            'url'     => '/plans',
            'method'  => 'post',
            'content' => [
                'amount'            => 2000,
                'currency'          => 'INR',
                'period'            => 'monthly',
                'interval'          => 14,
                'name'              => 'test plan',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Exceeds the maximum interval allowed for the given interval',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePlanWithBadYearlyIntervalPeriod' => [
        'request' => [
            'url'     => '/plans',
            'method'  => 'post',
            'content' => [
                'amount'            => 2000,
                'currency'          => 'INR',
                'period'            => 'yearly',
                'interval'          => 2,
                'name'              => 'test plan',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Exceeds the maximum interval allowed for the given interval',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubscriptionWithNoStartAt' => [
        'request' => [
            'url' => '/plans/plan_1000000000plan/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
                'total_count'   => 6, // Every two months
            ],
        ],
        'response' => [
            'content' => [
                'customer_id' => 'cust_100000customer',
                'status' => 'created',
                'current_start' => null,
                'current_end' => null,
                'ended_at' => null,
                'quantity' => 1,
                'token_id' => null,
                'notes' => [],
                'charge_at' => null,
                'start_at' => null,
                'end_at' => null,
                'total_count' => 6,
                'paid_count' => 0,
            ],
        ],
    ],

    'testCreateSubscriptionWithNoStartAtAndWithAddOn' => [
        'request' => [
            'url' => '/plans/plan_1000000000plan/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'    => 'cust_100000customer',
                'quantity'       => 1,
                'total_count'    => 6, // Every two months
                'add_ons'        => [
                    [
                        'amount' => 300,
                        // TODO: Add a test case with USD
                        'currency' => 'INR',
                        'name' => 'Sample Upfront Amount'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_id' => 'cust_100000customer',
                'status' => 'created',
                'current_start' => null,
                'current_end' => null,
                'ended_at' => null,
                'quantity' => 1,
                'token_id' => null,
                'notes' => [],
                'charge_at' => null,
                'start_at' => null,
                'end_at' => null,
                // 'upfront_amount' => 300,
                'total_count' => 6,
                'paid_count' => 0,
            ],
        ],
    ],

    'testCreateSubscriptionWithStartAtAndAddOn' => [
        'request' => [
            'url' => '/plans/plan_1000000000plan/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'    => 'cust_100000customer',
                'quantity'       => 1,
                'start_at'       => 1516386600, // 1-20-2017, 12:00:00 AM
                'total_count'    => 6, // Every two months
                'add_ons'        => [
                    [
                        'amount' => 300,
                        // TODO: Add a test case with USD
                        'currency' => 'INR',
                        'name' => 'Sample Upfront Amount'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_id' => 'cust_100000customer',
                'status' => 'created',
                'current_start' => null,
                'current_end' => null,
                'ended_at' => null,
                'quantity' => 1,
                'token_id' => null,
                'notes' => [],
                'charge_at' => 1516386600, // 1-20-2018, 12:00:00 AM
                'start_at' => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at' => 1545244200, // 12-20-2018, 12:00:00 AM
                'total_count' => 6,
                'paid_count' => 0,
            ],
        ],
    ],

    'testCreateSubscriptionWithOneYearLateStartAt' => [
        'request' => [
            'url' => '/plans/plan_1000000000plan/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
                'start_at'      => 1800383400, // 1-20-2027, 12:00:00 AM
                'total_count'   => 6, // Every two months
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'start_at must be less than one year from now.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubscriptionWithPastTime' => [
        'request' => [
            'url' => '/plans/plan_1000000000plan/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
                'start_at'      => 1484850600, // 1-20-2017, 12:00:00 AM
                'total_count'   => 6, // Every two months
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'start_at cannot be lesser than the current time.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubscriptionWithTotalCount' => [
        'request' => [
            'url' => '/plans/plan_1000000000plan/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'total_count'   => 6, // Every two months
            ],
        ],
        'response' => [
            'content' => [
                'customer_id' => 'cust_100000customer',
                'status' => 'created',
                'current_start' => null,
                'current_end' => null,
                'ended_at' => null,
                'quantity' => 1,
                'token_id' => null,
                'notes' => [],
                'charge_at' => 1516386600, // 1-20-2018, 12:00:00 AM
                'start_at' => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at' => 1545244200, // 12-20-2018, 12:00:00 AM
                'total_count' => 6,
                'paid_count' => 0
            ],
        ],
    ],

    'testCreateSubscriptionWithEndAt' => [
        'request' => [
            'url' => '/plans/plan_1000000000plan/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at'        => 1545244200, // Every two months
            ],
        ],
        'response' => [
            'content' => [
                'customer_id' => 'cust_100000customer',
                'status' => 'created',
                'current_start' => null,
                'current_end' => null,
                'ended_at' => null,
                'quantity' => 1,
                'token_id' => null,
                'notes' => [],
                'charge_at' => 1516386600, // 1-20-2018, 12:00:00 AM
                'start_at' => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at' => 1545244200, // 12-20-2018, 12:00:00 AM
                'total_count' => 6,
                'paid_count' => 0
            ],
        ],
    ],

    'testCreateSubscriptionWithBothTotalCountAndEndAt' => [
        'request' => [
            'url' => '/plans/plan_1000000000plan/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at'        => 1545244200, // Every two months
                'total_count'   => 6,
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Either end_at or total_count should be sent and not both.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_END_AT_AND_TOTAL_COUNT_SENT,
        ],
    ],

    'testCreateSubscriptionWithEndAtLesserThanStartAt' => [
        'request' => [
            'url' => '/plans/plan_1000000000plan/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at'        => 1505244200, // Every two months
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'end_at cannot be lesser than start_at.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubscriptionWithVeryFarEndAt' => [
        'request' => [
            'url' => '/plans/plan_1000000000plan/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
                'end_at'        => 1579458600, // 1-20-2020, 12:00:00 AM
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'end_at should be within 1 year/s of start_at.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubscriptionWithoutTotalCountAndEndAt' => [
        'request' => [
            'url' => '/plans/plan_1000000000plan/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
                'start_at'      => 1516386600, // 1-20-2018, 12:00:00 AM
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The total count field is required when end at is not present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSubscriptionCharge' => [
        'request' => [
            'url' => '/subscriptions/charge',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],
];
