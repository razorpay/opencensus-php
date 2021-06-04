<?php

return [
    'testExtractReport' => [
        'request' => [
            'url'       => '/freshchat/extract_report',
            'method'    => 'POST',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
            'status_code'   => 200,
        ],
    ],

    'testRetrieveReport' => [
        'request' => [
            'url'       => '/freshchat/retrieve_report/',
            'method'    => 'POST',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
            'status_code'   => 200,
        ],
    ],

    'testPutChatTimingsConfig' => [
        'request'  => [
            'url'     => '/chat/timings_config/',
            'method'  => 'PUT',
            'content' => [
                0 => ['start' => 1, 'end' => 2],
                1 => ['start' => 540, 'end' => 1260],
                2 => ['start' => 540, 'end' => 1260],
                3 => ['start' => 540, 'end' => 1260],
                4 => ['start' => 540, 'end' => 1260],
                5 => ['start' => 0, 'end' => 0],
                6 => ['start' => 0, 'end' => 0],
            ],
        ],
        'response' => [
            'content' => [
                0 => ['start' => 1, 'end' => 2],
                1 => ['start' => 540, 'end' => 1260],
                2 => ['start' => 540, 'end' => 1260],
                3 => ['start' => 540, 'end' => 1260],
                4 => ['start' => 540, 'end' => 1260],
                5 => ['start' => 0, 'end' => 0],
                6 => ['start' => 0, 'end' => 0],
            ],
        ],
    ],

    'testPutChatTimingsConfigInvalid' => [
        'request'  => [
            'url'     => '/chat/timings_config/',
            'method'  => 'PUT',
            'content' => [
                0 => ['start' => 3, 'end' => 2],
                1 => ['start' => 540, 'end' => 1260],
                2 => ['start' => 540, 'end' => 1260],
                3 => ['start' => 540, 'end' => 1260],
                4 => ['start' => 540, 'end' => 1260],
                5 => ['start' => 0, 'end' => 0],
                6 => ['start' => 0, 'end' => 0],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE'
        ],
    ],

    'testGetChatTimingsConfigProxyAuth' => [
        'request'  => [
            'url'     => '/merchants/chat/timings_config/',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                0 => ['start' => 540, 'end' => 1260],
                1 => ['start' => 540, 'end' => 1260],
                2 => ['start' => 540, 'end' => 1260],
                3 => ['start' => 540, 'end' => 1260],
                4 => ['start' => 540, 'end' => 1260],
                5 => ['start' => 0, 'end' => 0],
                6 => ['start' => 0, 'end' => 0],
            ],
        ],
    ],

    'testGetChatTimingsConfigDefault' => [
        'request'  => [
            'url'     => '/chat/timings_config/',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                0 => ['start' => 600, 'end' => 1320],
                1 => ['start' => 600, 'end' => 1320],
                2 => ['start' => 600, 'end' => 1320],
                3 => ['start' => 600, 'end' => 1320],
                4 => ['start' => 600, 'end' => 1320],
                5 => ['start' => 600, 'end' => 1320],
                6 => ['start' => 600, 'end' => 1320],
            ],
        ],
    ],

    'testPutChatHoliday' => [
        'request'  => [
            'url'     => '/chat/holidays_config/',
            'method'  => 'PUT',
            'content' => [
                [
                    'day'   => 4,
                    'month' => 3,
                    'year'  => 2021,
                ],
                [
                    'day'   => 4,
                    'month' => 4,
                    'year'  => 2021,
                ],
                [
                    'day'   => 4,
                    'month' => 5,
                    'year'  => 2021,
                ],
            ],
        ],
        'response' => [
            'content' => [
                [
                    'day'   => 4,
                    'month' => 3,
                    'year'  => 2021,
                ],
                [
                    'day'   => 4,
                    'month' => 4,
                    'year'  => 2021,
                ],
                [
                    'day'   => 4,
                    'month' => 5,
                    'year'  => 2021,
                ],
            ],
        ],
    ],

    'testPutChatHolidayInvalid' => [
        'request'   => [
            'url'     => '/chat/holidays_config/',
            'method'  => 'PUT',
            'content' => [
                [
                    'day'   => 4,
                    'month' => 3,
                    'year'  => 2021,
                ],
                [
                    'day'   => 4,
                    'month' => 31,
                    'year'  => 2021,
                ],
                [
                    'day'   => 4,
                    'month' => 5,
                    'year'  => 2021,
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testGetChatHoliday' => [
        'request'  => [
            'url'    => '/chat/holidays_config/',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                [
                    'day'   => 4,
                    'month' => 3,
                    'year'  => 2021,
                ],
                [
                    'day'   => 4,
                    'month' => 4,
                    'year'  => 2021,
                ],
                [
                    'day'   => 4,
                    'month' => 5,
                    'year'  => 2021,
                ],
            ],
        ],
    ],

    'testGetChatHolidaysConfigProxyAuth' => [
        'request'  => [
            'url'    => '/merchants/chat/holidays_config/',
            'method' => 'GET',
        ],
        'response' => [
            'content'   => [
                [
                    'day'   => 4,
                    'month' => 3,
                    'year'  => 2021,
                ],
            ],
        ],
    ],

    'testGetChatHolidaysDefault' => [
        'request'  => [
            'url'    => '/chat/holidays_config/',
            'method' => 'GET',
        ],
        'response' => [
            'content'   => [
                [
                    'day'   => 13,
                    'month' => 4,
                    'year'  => 2021,
                ],
            ],
        ],
    ],

];
