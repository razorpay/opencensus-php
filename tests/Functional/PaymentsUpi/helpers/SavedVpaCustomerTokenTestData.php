<?php

return [
    'testGetCustomerTokensWithSaveVpaFeatureEnabled' => [
        'request' => [
            'url' => '/customers/status/9988776655',
            'method' => 'get',
            'content' => [
                'device_token' => '1000custdevice'
            ],
        ],
        'response' => [
            'content' => [
                'saved' => true,
                'email' => 'test@razorpay.com',
                'tokens' => [
                    'entity' => 'collection',
                    'count'  => 2,
                    'items'  => [
                    [
                        'id'           => 'token_10000custgcard',
                        'method'       => 'card',
                        'token'        => '1000gcardtoken',
                    ],
                    [
                        'id'            => 'token_100000custgupi',
                        'method'        => 'upi',
                        'token'         => '10000gupitoken',
                        'vpa'           => [
                            'username'  => 'globaluser',
                            'handle'    => 'icici',
                            'name'      => 'globaluser'
                        ]
                    ]
                    ],
                ]
            ]
        ],
    ],
    'testGetCustomerTokensWithSaveVpaFeatureDisabled' => [
        'request' => [
            'url' => '/customers/status/9988776655',
            'method' => 'get',
            'content' => [
                'device_token' => '1000custdevice'
            ],
        ],
        'response' => [
            'content' => [
                'saved' => true,
                'email' => 'test@razorpay.com',
                'tokens' => [
                    'entity' => 'collection',
                    'count'  => 1,
                    'items'  => [
                        [
                            'id'           => 'token_10000custgcard',
                            'method'       => 'card',
                            'token'        => '1000gcardtoken',
                        ],
                    ],
                ]
            ]
        ],
    ],
];
