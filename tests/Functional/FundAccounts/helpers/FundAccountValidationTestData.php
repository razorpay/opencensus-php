<?php

USE RZP\Models\FundAccount\Entity as FundAccount;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\FundAccount\Validation\Entity as Validation;

return [
    'testCreateValidationWithFundAccountId' => [
        'request' => [
            'url'     => '/fund_accounts/validations/create',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID   => '',
                ],
                Validation::CURRENCY => 'INR',
                Validation::NOTES => [],
                Validation::RECEIPT => '12345667',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateValidationWithWrongFundAccountId' => [
        'request' => [
            'url'     => '/fund_accounts/validations/create',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID   => '',
                ],
                Validation::AMOUNT => '100',
                Validation::CURRENCY => 'INR',
                Validation::NOTES => [],
                Validation::RECEIPT => '12345667',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateValidationWithFundAccountEntity' => [
        'request' => [
            'url'     => '/fund_accounts/validations/create',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ACCOUNT_TYPE   => 'bank_account',
                        FundAccount::DETAILS   => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'SBIN0010411',
                    ],
                ],
                Validation::AMOUNT => '100',
                Validation::CURRENCY => 'INR',
                Validation::NOTES => [],
                Validation::RECEIPT => '12345667',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    /*'testGetMultipleValidations' => [
        'request' => [
            'url' => '/fund_accounts/validations',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],*/
];
