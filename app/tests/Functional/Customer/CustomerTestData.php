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
                'contact' => '1234567899',
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'testc',
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
                'contact' => '1234567809',
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'test1',
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
                'contact' => '1234567890',
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
                        'id'            => '100000custcard',
                        'customer_id'   => '100000customer',
                        'method'        => 'card',
                        'card_id'       => '1000000000card',
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