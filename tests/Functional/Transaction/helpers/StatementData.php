<?php

return [
    'testFetchStatements' => [
        'request' => [
            'url' => '/statements',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testFetchStatementPayout' => [
        'request' => [
            'url' => '/statements',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testFetchStatementBankTransfer' => [
        'request' => [
            'url' => '/statements',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],


    'createVirtualAccount' => [
        'url'     => '/virtual_accounts',
        'method'  => 'post',
        'content' => [
            'receivers' => [
                'types' => [
                    'bank_account',
                ],
            ],
        ],
    ],

    'processBankTransfer' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
         'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => 'utr_thisisbestutr',
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'NEFT payment of 50,000 rupees',
        ],
    ],
];
