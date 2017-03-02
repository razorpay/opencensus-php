<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAssignSchedule' => [
        'method'  => 'POST',
        'url'     => '/merchants/10000000000000/schedules',
        'content' => [],
    ],

    'testAssignScheduleById' => [
        'method'  => 'POST',
        'url'     => '/merchants/10000000000000/schedules',
        'content' => [
            'settlement_schedule_id' => null,
        ],
    ],

    'createSchedule' => [
        'method'  => 'POST',
        'url'     => '/schedules',
        'content' => [],
    ],

    'fetchSchedule' => [
        'method'  => 'GET',
        'url'     => '/schedules/',
        'content' => [],
    ],

    'deleteSchedule' => [
        'method'  => 'DELETE',
        'url'     => '/schedules/',
        'content' => [],
    ],

    'capturePayment' => [
        'method'  => 'POST',
        'url'     => '/payments/',
        'content' => [],
    ],

    'testEditSchedule' => [
        'method'  => 'PUT',
        'url'     => '/schedules/',
        'content' => [
            "next_run" => 1451586600,
        ],
    ],

    'testMerchantSettlementScheduleSync' => [
        'method'  => 'POST',
        'url'     => '/merchants/10000000000000/schedules',
        'content' => [
            'name'       => 'Basic T5',
            'type'       => 'settlement',
            'period'     => 'daily',
            'interval'   => 1,
            'delay'      => 5,
        ],
    ],

    'testScheduleBody' => [
        'name'       => 'Every Wednesday',
        'type'       => 'settlement',
        'period'     => 'weekly',
        'interval'   => 1,
        'anchor'     => 3,
        'delay'      => 1,
        'next_run'   => 1452105000,
    ],

    'timedScheduleBody' => [
        'name'       => 'Timed Schedule',
        'type'       => 'settlement',
        'period'     => 'daily',
        'interval'   => 5,
        'hour'       => 12,
        'delay'      => 1,
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

    'testScheduleInvalidWeeklyAnchor' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_SCHEDULE_WEEKEND_ANCHOR_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_SCHEDULE_WEEKEND_ANCHOR_NOT_PERMITTED,
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
            'class'                 => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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
];
