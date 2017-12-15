<?php

return [
    'testRetrieveAccount' => [
        'request' => [
            'url' => '/accounts/acc_10000000000001',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'id' => 'acc_10000000000001',
            ],
        ],
    ],

    'testRetrieveAccounts' => [
        'request' => [
            'url' => '/accounts',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'id' => 'acc_10000000000001',
                    ]
                ],
            ],
        ],
    ],

    'testCreateLinkedAccount' => [
        'request' => [
            'url' => '/accounts',
            'method' => 'post',
            'content' => [
                'name' => 'Linked Account 1',
                'email' => 'linked1@account.com',
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'Linked Account 1',
                'email' => 'linked1@account.com',
            ],
        ],
    ],

    'testAddSettlementDestination' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
            ],
            'url' => '/accounts/10000000000000/settlement-destinations',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id'          => '10000000000000',
                'ifsc_code'            => 'ICIC0001206',
                'account_number'       => '0002020000304030434',
                'beneficiary_name'     => 'Test R4zorpay',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_city'     => 'Kolkata',
                'beneficiary_state'    => 'WB',
                'beneficiary_country'  => 'IN',
                'beneficiary_pin'      => '123456',
                'beneficiary_email'    => 'random@email.com',
                'beneficiary_mobile'   => '9988776655',
            ]
        ]
    ],

    'fetchSettlementDestinations' => [
        'request' => [
            'content' => [ ],
            'url' => '/accounts/10000000000000/settlement-destinations',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'merchant_id' => '10000000000000'
                ]
            ]
        ]
    ],
];
