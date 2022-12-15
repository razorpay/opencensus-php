<?php

return [
    'testLive' => [
        'request'  => [
            'url'     => '/merchant/app/checker',
            'method'  => 'post',
            'content' => [
                'url' => 'https://play.google.com/store/apps/details?id=com.whatsapp',
            ]
        ],
        'response' => [
            'content' => [
                'url'     => 'https://play.google.com/store/apps/details?id=com.whatsapp',
                'result'  => 'Live',
                'comment' => 'Status Code = 200',
            ],
        ],
    ],
    'testNotLive' => [
        'request'  => [
            'url'     => '/merchant/app/checker',
            'method'  => 'post',
            'content' => [
                'url' => 'https://play.google.com/store/apps/details?id=com.whatsapp.dummy',
            ]
        ],
        'response' => [
            'content' => [
                'url'     => 'https://play.google.com/store/apps/details?id=com.whatsapp.dummy',
                'result'  => 'Not Live',
                'comment' => 'Status Code = 404',
            ],
        ],
    ],
    'testManualReview' => [
        'request'  => [
            'url'     => '/merchant/app/checker',
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
    'testMilestoneCronLive' => [
        'request'  => [
            'url'     => '/merchant/app/checker/milestone/cron',
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
            'url'     => '/merchant/app/checker/milestone/cron',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
    'testPeriodicCronLiveWithTxnUrls' => [
        'request'  => [
            'url'     => '/merchant/app/checker/cron',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
    'testPeriodicCronLive' => [
        'request'  => [
            'url'     => '/merchant/app/checker/cron',
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
            'url'     => '/merchant/app/checker/cron',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
    'testRetryCronLive' => [
        'request'  => [
            'url'     => '/merchant/app/checker/retry/cron',
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
            'url'     => '/merchant/app/checker/retry/cron',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
    'testReminderCronLive' => [
        'request'  => [
            'url'     => '/merchant/app/checker/reminder/cron',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
    'testIgnoreReminderCronNotLive' => [
        'request'  => [
            'url'     => '/merchant/app/checker/reminder/cron',
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
                'ticket_id' => 'test_fd_124',
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],
];
