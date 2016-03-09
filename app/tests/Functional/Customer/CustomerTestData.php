<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testCreateCustomer' => [
        'request' => [
            'url' => '/customers',
            'method' => 'post',
            'content' => [
                'name'    => 'testc',
                'email'   => 'testc@razorpay.com',
                'contact' => '1234567899',
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'testc',
                'email' => 'testc@razorpay.com',
                'contact' => '1234567899',
            ],
        ],
    ],

    'testUpdateCustomer' => [
        'request' => [
            'url' => '/customers/100000customer',
            'method' => 'put',
            'content' => [
                'name'    => 'test1',
                'email'   => 'test1@razorpay.com',
                'contact' => '1234567809',
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'test1',
                'email' => 'test1@razorpay.com',
                'contact' => '1234567809',
            ],
        ],
    ],

    'testGetCustomer' => [
        'request' => [
            'url' => '/customers/100000customer',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'id' => '100000customer',
                'name'    => 'test',
                'email' => 'test@razorpay.com',
                'contact' => '1234567890',
            ],
        ],
    ],

    'testDeleteCustomer' => [
        'request' => [
            'url' => '/customers/100000customer',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCustomerMethods' => [
        'request' => [
            'url' => '/customers/100000customer/methods',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'id'            => '100000custbank',
                        'customer_id'   => '100000customer',
                        'method'        => 'netbanking',
                        'bank'          => 'HDFC',
                    ],
                    [
                        'id'            => '1000custwallet',
                        'customer_id'   => '100000customer',
                        'method'        => 'wallet',
                        'wallet'        => 'paytm',
                    ],

                ]

            ],
        ],
    ],

    'testGetCustomerMethod' => [
        'request' => [
            'url' => '/customers/100000customer/methods/1000custwallet',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'id'            => '1000custwallet',
                'customer_id'   => '100000customer',
                'method'        => 'wallet',
                'wallet'        => 'paytm',
            ],
        ],
    ],

    'testDeleteCustomerMethod' => [
        'request' => [
            'url' => '/customers/100000customer/methods/1000custwallet',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAddCustomerMethodCard' => [
        'request' => [
            'url' => '/customers/100000customer/methods',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card_id' => '10000savedcard',
            ],
        ],
        'response' => [
            'content' => [
                'method' => 'card',
                'card_id' => '10000savedcard',
                'wallet' => null,
                'bank' => null
            ],
        ],
    ],

    'testAddCustomerMethodWallet' => [
        'request' => [
            'url' => '/customers/100000customer/methods',
            'method' => 'post',
            'content' => [
                'method' => 'wallet',
                'wallet' => 'mobikwik',
                'account_key' => "dj83hd9j4jd=="
            ],
        ],
        'response' => [
            'content' => [
                'method' => 'wallet',
                'card_id' => null,
                'wallet' => 'mobikwik',
                'bank' => null,
                'account_key' => "dj83hd9j4jd==",
            ],
        ],
    ],

    'testAddCustomerMethodNetbanking' => [
        'request' => [
            'url' => '/customers/100000customer/methods',
            'method' => 'post',
            'content' => [
                'method' => 'netbanking',
                'bank' => 'KKBK',
                'account_key' => '23881822'
            ],
        ],
        'response' => [
            'content' => [
                'method' => 'netbanking',
                'card_id' => null,
                'wallet' => null,
                'bank' => 'KKBK',
                'account_key' => '23881822',
            ],
        ],
    ]
];