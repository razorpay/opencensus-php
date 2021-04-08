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
];
