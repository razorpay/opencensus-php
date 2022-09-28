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
                    'count'  => 1,
                    'items'  => [
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
                            'id'           => 'token_100gcustltoken',
                            'method'       => 'card',
                            'token'        => '1000lcardtoken',
                        ],
                    ],
                ]
            ]
        ],
    ],
    'testGetAllCustomerTokensWithSavedVpaFeature' => [
        'request' => [
            'url' => '/customers/cust_100000customer/tokens',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'token'         => '10001emantoken',
                        'method'        => 'emandate',
                        'bank'          => 'HDFC',
                        'max_amount'    =>  105,
                    ],
                    [
                        'token'         => '10001cardtoken',
                        'method'        => 'card',
                        'card'          => [
                            'last4'         => '1111',
                            'network'       => 'Visa',
                        ]
                    ],
                    [
                        'token'         => '10002cardtoken',
                        'method'        => 'card',
                        'card'          => [
                            'last4'         => '1111',
                            'network'       => 'RuPay',
                        ]
                    ],
                    [
                        'token'         => '10000cardtoken',
                        'method'        => 'card',
                        'card'          => [
                            'last4'         => '1111',
                            'network'       => 'Visa',
                        ]
                    ],
                    [
                        'token'         => '10000upitoken',
                        'method'        => 'upi',
                        'vpa'           => [
                            'username'    => 'localuser',
                            'handle'      => 'icici',
                        ]
                    ],
                    [
                        'token'         => '10000banktoken',
                        'method'        => 'netbanking',
                    ],
                    [
                        'token'         => '100wallettoken',
                        'method'        => 'wallet',
                        'wallet'        => 'paytm',
                    ],
                ]
            ],
        ],
    ],
];
