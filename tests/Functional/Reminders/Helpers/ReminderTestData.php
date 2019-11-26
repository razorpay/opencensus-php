<?php

namespace RZP\Tests\Functional\Reminders;

use RZP\Error\ErrorCode;


return [
    'testSendReminderWithReminderCountAndChannels' => [
        'request' => [
            'url' => '/reminders/send/test/invoice/payment_link/1000000invoice',
            'method' => 'post',
            'content' => [
                'reminder_count' => 1,
                'channels' => [
                    'email',
                    'sms'
                ]
            ]
        ],

        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],

    'testSendReminderWithoutReminderCountWithChannels' => [
        'request' => [
            'url' => '/reminders/send/test/invoice/payment_link/1000000invoice',
            'method' => 'post',
            'content' => [
                'channels' => [
                    'email',
                    'sms'
                ]
            ]
        ],

        'response' => [
            'content' => [],
            'status_code' => 400,
        ],

        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendReminderWithReminderCountWithoutChannels' => [
        'request' => [
            'url' => '/reminders/send/test/invoice/payment_link/1000000invoice',
            'method' => 'post',
            'content' => [
                'reminder_count' => 1,
            ]
        ],

        'response' => [
            'content' => [],
            'status_code' => 400,
        ],

        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ]
];
