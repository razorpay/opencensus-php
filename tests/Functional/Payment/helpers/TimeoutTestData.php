<?php

return [
    'testTimeoutInvalidId' => [
        'request' => [
            'url'    => '/payments/ABCDEFGHIJKLMN/timeout_new',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'retry_timeout' => false,
            ],
        ],
    ],
    'testTimeoutPaymentStatusNotCreated' => [
        'request' => [
            'url'    => '/payments/%s/timeout_new',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'retry_timeout' => false,
                'payment' => [
                    'status' => 'captured'
                ]
            ],
        ],
    ],
    'testTimeoutPaymentShouldNotTimeout' => [
        'request' => [
            'url'    => '/payments/%s/timeout_new',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'retry_timeout' => false,
                'payment' => [
                    'status' => 'created'
                ]
            ],
        ],
    ],
    'testTimeoutPaymentOldPayment' => [
        'request' => [
            'url'    => '/payments/%s/timeout_new',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'retry_timeout' => false,
                'payment' => [
                    'status'        => 'failed',
                    'error_reason'  => 'payment_timed_out'
                ]
            ],
        ],
    ]
];
