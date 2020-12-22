<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\Admin\ConfigKey;

return [
    'testTrimMerchantData' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/pagination/trim_space/start'
        ],
        'response' => [
            'content'     => [
                'status'        => 'updated'
            ],
            'status_code' => 200,
        ],
    ],

    'testSetPaginationParameters' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                ConfigKey::PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE => [
                    'start_time'             => 12,
                    'end_time'               => 12,
                    'duration'               => 86400, // 1 day in second
                    'limit'                  => 1000,
                    'whitelist_merchant_ids' => [
                        '10000000000000'
                    ]
                ],
            ],
        ],
        'response' => [
            'content'     => [
                [
                    'key' => ConfigKey::PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE,
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testSetPaginationParametersStartTimeGreaterThanEndTime' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/pagination/trim_space/start'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Given start time is greater than or equal to end time.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testTrimMerchantDataAfterStartTimeReachEndTime' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/pagination/trim_space/start'
        ],
        'response'  => [
            'content'     => [
                'message' => "Job is complete please stop cron."
            ],
            'status_code' => 200,
        ],
    ],
];
