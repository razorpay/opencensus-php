<?php

return [
    'testLive' => [
        'request'  => [
            'url'     => '/merchant/website/checker',
            'method'  => 'post',
            'content' => [
                'url' => 'https://razorpay.com',
            ]
        ],
        'response' => [
            'content' => [
                'url'     => 'https://razorpay.com',
                'result'  => 'Live',
                'comment' => 'Status Code = 200',
            ],
        ],
    ],
    'testNotLive' => [
        'request'  => [
            'url'     => '/merchant/website/checker',
            'method'  => 'post',
            'content' => [
                'url' => 'https://google.com/dummy',
            ]
        ],
        'response' => [
            'content' => [
                'url'     => 'https://google.com/dummy',
                'result'  => 'Not Live',
                'comment' => 'Status Code = 404',
            ],
        ],
    ],
    'testManualReview' => [
        'request'  => [
            'url'     => '/merchant/website/checker',
            'method'  => 'post',
            'content' => [
                'url' => 'https://razorpay2.com',
            ]
        ],
        'response' => [
            'content' => [
                'url'     => 'https://razorpay2.com',
                'result'  => 'Manual Review',
                'comment' => 'Error = cURL error 6: Could not resolve host: razorpay2.com',
            ],
        ],
    ],
    'testMilestoneCron' => [
        'request'  => [
            'url'     => '/merchant/website/checker/milestone/cron',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
    'testMilestoneCronNotLive' => [
        'request'  => [
            'url'     => '/merchant/website/checker/milestone/cron',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
    'testPeriodicCron' => [
        'request'  => [
            'url'     => '/merchant/website/checker/cron',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
    'testPeriodicCronNotLive' => [
        'request'  => [
            'url'     => '/merchant/website/checker/cron',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
    'testRetryCron' => [
        'request'  => [
            'url'     => '/merchant/website/checker/retry/cron',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
    'testRetryCronNotLive' => [
        'request'  => [
            'url'     => '/merchant/website/checker/retry/cron',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
    'testReminderCron' => [
        'request'  => [
            'url'     => '/merchant/website/checker/reminder/cron',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
    'testIgnoreReminderCron' => [
        'request'  => [
            'url'     => '/merchant/website/checker/reminder/cron',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
    'testFreshdeskWebhook' => [
        'request'  => [
            'url'     => '/fd/webhook/website_checker_reply',
            'method'  => 'post',
            'content' => [
                'ticket_id' => 'test_fd_123',
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
];
