<?php

// @codingStandardsIgnoreStart
return [

    'dummyPayload' => [
        'key'       => 'api123',
        'context'   => [
            'mode'              => 'test',
            'payment_id'        => 'pay_7EBRk7756OuWqK',
            'library'           => 'checkoutjs',
            'library_version'   => '3846fgjb',
            'platform'          => 'mobile_sdk',
            'platform_version'  => '0.4.12',
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
                        'acquirer'  => null,
                        'category'  => null,
                        'shared'    => true,
                        'recurring' => 0
                    ]
                ]
            ],
            [
                'event'         => 'FIRST_PAYMENT_RESPONSE',
                'timestamp'     => 1486391787,
                'properties'    => [
                    'payment_id'    => 'pay_7EBMa55PnkI2ze',
                    'merchant_id'   => '10000000000000',
                    'merchant_name' => 'laboriosam',
                    'amount'        => '50000',
                    'terminal'      => [
                        'id'        => '1000AmexShared',
                        'gateway'   => 'amex',
                        'acquirer'  => null,
                        'category'  => null,
                        'shared'    => true,
                        'recurring' => 0
                    ],
                    'type'          => 'first',
                    'gateway'       => 'eyJpdiI6ImFCR3dsSFhtZWphRTVNbENzWkpKckE9PSIsInZhbHVlIjoiQlVNalpqS1BxRmdXeTBnU1RrWVZscUFVZ0JiYmp0dlB4dUFFXC93M09MdVk9IiwibWFjIjoiYjgyNjA5MGM4OGYxMzE4NDBmMmExODRhMDdhYzVmMjNkYTA1MzI1NDMwMGQyMTUzM2VjMmY5NDllYzM3MzkyNiJ9',
                    'image'         => null
                ]
            ]
        ]
    ],

    'responseLjFailed'  => '{"success":false}',

    'responseLjSuccess' => '{"success":true}',
];
// @codingStandardsIgnoreEnd
