<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateSchedule' => [
        'name'       => 'Every Wednesday',
        'period'     => 'weekly',
        'interval'   => 1,
        'anchor'     => 3,
        'delay'      => 1,
        'type'       => 'settlement',
    ],

    'testCreateSchedule' => [
        'name'       => 'Every Wednesday',
        'period'     => 'weekly',
        'interval'   => 1,
        'anchor'     => 3,
        'delay'      => 1,
        'type'       => 'settlement',
    ],

    'testAssignSchedule' => [
        'method'  => 'POST',
        'url'     => '/merchants/10000000000000/schedules',
        'content' => [],
    ],

    'testAssignScheduleById' => [
        'method'  => 'POST',
        'url'     => '/merchants/10000000000000/schedules',
        'content' => [],
    ],

    'testCreateAssignScheduleWorkflowWithObserverData' => [
        'method'  => 'POST',
        'url'     => '/merchants/10000000000000/schedules',
        'content' => [
        ],
    ],

    'testEditSchedule' => [
        'method'  => 'PUT',
        'url'     => '/schedules/',
        'content' => [
            'anchor' => 3,
        ],
    ],

    'timedScheduleBody' => [
        'name'       => 'Timed Schedule',
        'period'     => 'daily',
        'interval'   => 5,
        'hour'       => 12,
        'delay'      => 1,
    ],

    'testUpdateNextRunAt' => [
        'method'  => 'POST',
        'url'     => '/schedules/update_next_run/',
        'content' => [
            'type' => 'settlement',
        ],
    ],

    'testFetchSettlementSchedules' => [
        'request' => [
            'url' => '/settlements/schedules',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' =>  [
                    [
                        'name'        => 'Basic T3',
                        'period'      => 'daily',
                        'interval'    => 1,
                        'anchor'      => null,
                        'hour'        => 5,
                        'delay'       => 3,
                        'org_id'      => '100000razorpay',
                    ],
                    [
                        'name'        => 'Basic T60',
                        'period'      => 'daily',
                        'interval'    => 1,
                        'delay'       => 60,
                        'org_id'      => '100000razorpay',

                    ],
                ]
            ],
        ],
    ],

    'testDeleteScheduleInUse' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_SCHEDULE_IN_USE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_SCHEDULE_IN_USE,
        ],
    ],

    'testScheduleInvalidPeriod' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_SCHEDULE_INVALID_PERIOD,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_SCHEDULE_INVALID_PERIOD,
        ],
    ],

    'testScheduleInvalidType' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_SCHEDULE_INVALID_TYPE,
        ],
    ],

    'testScheduleInvalidHour' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_SCHEDULE_HOURLY_HOUR_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_SCHEDULE_HOURLY_HOUR_NOT_PERMITTED,
        ],
    ],

    'testScheduleSyncLiveAndTest' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'createSubscriptionToSync' => [
        'url' => '/subscriptions',
        'method' => 'post',
        'content' => [
            'customer_id'     => '',
            'plan_id'         => 'plan_1000000000plan',
            'quantity'        => 1,
            'total_count'     => 6, // Every two months
            'start_at'        => 1516386600,
            'customer_notify' => 0,
            'addons'        => [
                [
                    'item' => [
                        'amount' => 300,
                        'currency' => 'INR',
                        'name' => 'Sample Upfront Amount'
                    ]
                ]
            ],
        ],
    ],

    'testExpireCredits' => [
        'method'  => 'POST',
        'url'     => '/schedules/process_tasks',
        'content' => [
            'type'      => 'promotion',
        ],
    ],

    'applyCouponOnMerchant' => [
        'method' => 'POST',
        'url'    => '/coupons/apply',
        'content' => [
            'merchant_id' => '10000000000000',
            'code' =>  'RANDOM',
        ],
    ],
];
