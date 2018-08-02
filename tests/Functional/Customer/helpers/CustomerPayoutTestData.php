<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testAddCustomerBankAccount' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '1234567890',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
            ],
            'url' => '/customers/cust_100000customer/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'           => 'bank_account',
                'ifsc'             => 'ICIC0001206',
                'name'             => 'Test R4zorpay:',
                'account_number'   => '0002020000304030434',
            ]
        ]
    ],

    'testGetCustomerBankAccounts' => [
        'request' => [
            'content' => [
            ],
            'url' => '/customers/cust_100000customer/bank_account',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],
];
