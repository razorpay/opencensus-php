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
                'email'   => 'test@razorpay.com',
                'contact' => '1234567899',
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'testc',
                'email'   => 'test@razorpay.com',
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
                'id'      => 'cust_100000customer',
                'name'    => 'test',
                'contact' => '1234567890',
            ],
        ],
    ],

    'testGetCustomerTokens' => [
        'request' => [
            'url' => '/customers/100000customer/tokens',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'id'            => '100000custbank',
                        'method'        => 'netbanking',
                        'bank'          => 'HDFC',
                    ],
                    [
                        'id'            => '100000custcard',
                        'method'        => 'card',
                        'card'          =>  [
                            'last4'         => '1111',
                            'network'       => 'Visa',
                        ]
                    ],
                    [
                        'id'            => '1000custwallet',
                        'method'        => 'wallet',
                        'wallet'        => 'paytm',
                    ],
                ]
            ],
        ],
    ],

    'testGetCustomerToken' => [
        'request' => [
            'url' => '/customers/100000customer/tokens/1000custwallet',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'id'            => '1000custwallet',
                'method'        => 'wallet',
                'wallet'        => 'paytm',
            ],
        ],
    ],

    'testDeleteCustomerToken' => [
        'request' => [
            'url' => '/customers/100000customer/tokens/1000custwallet',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAddCustomerTokenCard' => [
        'request' => [
            'url' => '/customers/100000customer/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card_id' => '10000savedcard',
            ],
        ],
        'response' => [
            'content' => [
                'method' => 'card',
                'card'   =>  [
                    'last4'   => '1111',
                    'network' => 'Visa',
                ],
                'wallet'    => null,
                'bank'      => null
            ],
        ],
    ],

    'testAddCustomerTokenWallet' => [
        'request' => [
            'url' => '/customers/100000customer/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'wallet',
                'wallet' => 'mobikwik',
                'gateway_token' => "dj83hd9j4jd=="
            ],
        ],
        'response' => [
            'content' => [
                'method' => 'wallet',
                'wallet' => 'mobikwik',
                'bank' => null,
            ],
        ],
    ],

    'testAddCustomerTokenNetbanking' => [
        'request' => [
            'url' => '/customers/100000customer/tokens',
            'method' => 'post',
            'content' => [
                'method'        => 'netbanking',
                'bank'          => 'KKBK',
                'gateway_token' => '23881822'
            ],
        ],
        'response' => [
            'content' => [
                'method'        => 'netbanking',
                'wallet'        => null,
                'bank'          => 'KKBK',
            ],
        ],
    ],

    'testGetCustomerTokensByAppId' => [
        'request' => [
            'url' => '/apps/1000000custapp/tokens',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'items'     =>  [
                    [
                        'card'  => [
                            'last4'     => '1111',
                            'network'   => 'Visa'
                        ],
                    ]
                 ],
            ],
        ],
    ],

    'testFetchSavedTokensStatusSaved'   => [
        'request' => [
                'url' => '/customer/status/1234567890',
                'method' => 'get',
                'content' => [
                ],
            ],
            'response' => [
                'content' => [
                    'saved' => true
                ],
            ],
    ],

    'testFetchSavedTokensStatusNotSaved'   => [
        'request' => [
                'url' => '/customer/status/1234567899',
                'method' => 'get',
                'content' => [
                ],
            ],
            'response' => [
                'content' => [
                    'saved' => false
                ],
            ],
    ],

    'testDeleteAppToken' => [
        'request' => [
            'url' => '/apps/1000000custapp/tokens/10000custgcard',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
];