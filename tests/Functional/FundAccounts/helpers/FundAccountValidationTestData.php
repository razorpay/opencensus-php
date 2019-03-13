<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\FundAccount\Entity as FundAccount;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\FundAccount\Validation\Entity as Validation;

return [
    'testGetValidations' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity' => 'fund_account.validation',
                        'fund_account' => [
                            'entity' => 'fund_account',
                            'account_type' => 'bank_account',
                            'details' => [
                                'ifsc' => 'SBIN0010411',
                                'bank_name' => 'State Bank of India',
                                'name' => 'Rohit Keshwani',
                                'account_number' => '123456789',
                            ],
                            'bank_account' => [
                                'ifsc' => 'SBIN0010411',
                                'bank_name' => 'State Bank of India',
                                'name' => 'Rohit Keshwani',
                                'account_number' => '123456789',
                            ],
                            'active' => true,
                        ],
                        'status' => 'completed',
                        'amount' => 100,
                        'currency' => 'INR',
                        'notes' => [],
                        'results' => [
                            'account_status' => 'active',
                            'registered_name' => 'Someone',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateValidationWithFundAccountId' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Amit M',
                        'account_number' => '111000111',
                    ],
                ],
                'status'       => 'created',
                'amount'       => 100,
                'currency'     => 'INR',
                'notes'        => [],
                'results'      => [
                    'account_status'  => null,
                    'registered_name' => null,
                ],
            ],
        ],
    ],

    'testCreateValidationWithWrongFundAccountId' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => 'fa_ToteWrongIdLol',
                ],
                Validation::AMOUNT       => '100',
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                    'field'       => 'fund_account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'createValidationWithFundAccountEntity' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'SBIN0010411',
                    ],
                ],
                Validation::AMOUNT        => '100',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'account_number' => '123456789',
                        'name'           => 'Rohit Keshwani',
                        'ifsc'           => 'SBIN0010411',
                        'bank_name'      => 'State Bank of India',
                    ],
                ],
                'status'       => 'created',
                'amount'       => 100,
                'currency'     => 'INR',
                'notes'        => [],
                'results'      => [
                    'account_status'  => null,
                    'registered_name' => null,
                ],
            ],
        ],
    ],

    'testCreateValidationWithWrongFundAccountEntity' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '!!!$$$##',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'SBIN0010411',
                    ],
                ],
                Validation::AMOUNT        => '100',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account number may only contain letters and numbers.',
                    'field'       => 'fund_account.account_number',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFundAccValidationOnPrepaidModelWithNoFeeCreditsAndNoBalance' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'SBIN0010411',
                    ],
                ],
                Validation::AMOUNT        => '100',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The fees calculated for fund account validation is greater than available fee credits or balance.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INSUFFICIENT_BALANCE,
        ],
    ],

    'testWebhookFundAccountValidationCompleted' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event'  => 'fund_account.validation.completed',
            'contains' => [
                'fund_account.validation',
            ],
            'payload' => [
                'fund_account.validation' => [
                    'entity' => [
                        'entity'       => 'fund_account.validation',
                         'fund_account' => [
                             'entity'       => 'fund_account',
                             'account_type' => 'bank_account',
                             'active'       => true,
                             'details'      => [
                                 'ifsc'           => 'SBIN0010411',
                                 'bank_name'      => 'State Bank of India',
                                 'name'           => 'Rohit Keshwani',
                                 'account_number' => '123456789',
                             ],
                         ],
                        'status'       => 'completed',
                        'amount'       => 100,
                        'currency'     => 'INR',
                        'notes'        => [],
                        'results'      => [
                            'account_status'  => 'active',
                            'registered_name' => 'Someone',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
