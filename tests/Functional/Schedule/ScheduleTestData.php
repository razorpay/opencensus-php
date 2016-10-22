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

    'testScheduleBody' => [
        'name'       => 'Every Wednesday',
        'type'       => 'settlement',
        'period'     => 'weekly',
        'interval'   => 1,
        'anchor'     => 3,
        'delay'      => 86400,
        'next_run'   => 1452105000,
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
];
