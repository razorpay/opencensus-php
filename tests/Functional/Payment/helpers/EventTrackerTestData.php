<?php

// @codingStandardsIgnoreStart
return [

    'testEventTrackSuccess' => [
        'request' => [
            'url'       => '/mock/track',
            'method'    => 'post',
            'server'    => [],
            'content'   => [
                'context'   => [
                    'mode'              => 'test',
                    'payment_id'        => 'pay_7EBRk7756OuWqK',
                    'user_agent'        => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36'
                ],
                'events' => [
                    [
                        'event'         => 'PAYMENT_CREATED',
                        'timestamp'     => 1486390787,
                        'properties'    => [
                            'payment_id'    => 'pay_7EBMa55PnkI2ze',
                            'merchant_id'   => '10000000000000',
                            'merchant_name' => 'laboriosam',
                            'amount'        => '50000',
                            'terminal'      => [
                                'id'        => '1000AmexShared',
                                'gateway'   => 'amex',
                                'recurring' => 1
                            ]
                        ]
                    ]
                ]
            ]
        ],

        'response' => [
            'content' => [
                'success' => true
            ],
            'status_code' => 200,
        ]
    ],

    'testEventTrackFailed' => [
        'request' => [
            'url'       => '/mock/track',
            'method'    => 'post',
            'server'    => [],
            'content'   => [
                'context'   => [
                    'mode'              => 'test',
                    'payment_id'        => 'pay_7EBRk7756OuWqK',
                    'user_agent'        => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36'
                ],
                'events' => [
                    [
                        'event'         => 'PAYMENT_CREATED',
                        'timestamp'     => 1486390787,
                        'properties'    => [
                            'payment_id'    => 'pay_7EBMa55PnkI2ze',
                            'merchant_id'   => '10000000000000',
                            'merchant_name' => 'laboriosam',
                            'amount'        => '50000',
                            'terminal'      => [
                                'id'        => '1000AmexShared',
                                'gateway'   => 'amex',
                                'recurring' => 1
                            ]
                        ]
                    ]
                ]
            ]
        ],

        'response' => [
            'content' => [
                'success' => false
            ],
            'status_code' => 401,
        ]
    ],
];
// @codingStandardsIgnoreEnd
