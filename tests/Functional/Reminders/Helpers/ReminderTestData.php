<?php

namespace RZP\Tests\Functional\Reminders;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;

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
    ],

    'testSendNegativeBalanceReminderWithReminderCountAndChannels' => [
        'request' => [
            'url' => '/reminders/send/test/merchant/negative_balance/100ghi000ghi00',
            'method' => 'post',
            'content' => [
                'reminder_count' => 1,
                'channels' => [
                    'email',
                ]
            ]
        ],

        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],

    'testSendNegativeBalanceReminderWithoutChannels' => [
        'request' => [
            'url' => '/reminders/send/test/merchant/negative_balance/100ghi000ghi00',
            'method' => 'post',
            'content' => [
                'reminder_count' => 1,
            ]
        ],

        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],

    'testSendNegativeBalanceReminderBalanceIsPositive' => [
        'request' => [
            'url' => '/reminders/send/test/merchant/negative_balance/100ghi000ghi00',
            'method' => 'post',
            'content' => [
                'reminder_count' => 1,
                'channels' => [
                    'email',
                ]
            ]
        ],

        'response' => [
            'content' => [],
            'status_code' => 400,
        ],

        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE,
        ],
    ],

    'testSendNegativeBalanceReminderReminderCountMax' => [
        'request' => [
            'url' => '/reminders/send/test/merchant/negative_balance/100ghi000ghi00',
            'method' => 'post',
            'content' => [
                'reminder_count' => 8,
                'channels' => [
                    'email',
                ]
            ]
        ],

        'response' => [
            'content' => [],
            'status_code' => 400,
        ],

        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE,
        ],
    ],

    'testSendNegativeBalanceReminderDisabled' => [
        'request' => [
            'url' => '/reminders/send/test/merchant/negative_balance/100ghi000ghi00',
            'method' => 'post',
            'content' => [
                'reminder_count' => 1,
                'channels' => [
                    'email',
                ]
            ]
        ],

        'response' => [
            'content' => [],
            'status_code' => 400,
        ],

        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE,
        ],
    ],

    'testSendNegativeBalanceReminderCompleted' => [
        'request' => [
            'url' => '/reminders/send/test/merchant/negative_balance/100ghi000ghi00',
            'method' => 'post',
            'content' => [
                'reminder_count' => 1,
                'channels' => [
                    'email',
                ]
            ]
        ],

        'response' => [
            'content' => [],
            'status_code' => 400,
        ],

        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE,
        ],
    ],

    'testSendNegativeBalanceReminderWithoutReminderCountWithChannels' => [
        'request' => [
            'url' => '/reminders/send/test/merchant/negative_balance/100ghi000ghi00',
            'method' => 'post',
            'content' => [
                'channels' => [
                    'email',
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

    'testSendReminderTerminalCreatedWebhook'    =>  [
        'request' => [
            'url' => '/reminders/send/test/terminal/terminal_created_webhook/<terminalId>',
            'method' => 'post',
            'content' => [
            ]
        ],
        'response' => [
            'content'  => [
                'success'   => true
            ],
            'status_code' => 200
        ]   
    ]

];
