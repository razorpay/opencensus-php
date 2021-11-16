<?php

use RZP\Error\ErrorCode;
use \RZP\Models\Merchant;
use RZP\Error\PublicErrorCode;
use RZP\Models\FundAccount\Entity as FundAccount;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\FundAccount\Validation\Entity as Validation;

return [
    'testCreateFav' => [
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

    'testFAVCreditforBankingBalance' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                'account_number'         => '',
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::AMOUNT       => 100,
                Validation::CURRENCY     => "INR",
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
                    'utr'             => null,
                    'account_status'  => null,
                    'registered_name' => null,
                ],
            ],
        ],
    ],

    'testWebhookFiringFundAccountValidationFailed' => [
        'entity' => 'event',
        'event'  => 'fund_account.validation.failed',
        'contains' => [
            'fund_account.validation',
        ],
        'payload' => [
            'fund_account.validation' => [
                'entity' => [
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
                    'status'       => 'failed',
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

    'testFiringOfWebhookOnFAVCompletionWithStork' => [
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
                    'status'       => 'completed',
                    'amount'       => 100,
                    'currency'     => 'INR',
                    'notes'        => [],
                    'results'      => [
                        'account_status'  => 'active',
                        'registered_name' => null,
                    ],
                ],
            ],
        ],
    ],
];
