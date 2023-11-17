<?php

return [
    'testGetContactDetailsForCheckout' => [
        'request'  => [
            'url'    => '/checkout/contacts/cont_1000000contact?expand[]=fund_accounts',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id'   => 'cont_1000000contact',
                'name' => 'eum',
                'fund_accounts' => [
                    [
                        'account_type' => 'bank_account',
                        'bank_account' => [
                            'ifsc' => 'SBIN0007105',
                            'bank_name' => 'State Bank of India',
                            'name' => 'test',
                            'notes' => [],
                            'account_number' => 'XX1000',
                        ],
                    ],
                ]
            ],
        ],
    ],
];
