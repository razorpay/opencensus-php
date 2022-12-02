<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\InvalidArgumentException;

return [
    'testFetchMultipleStatementsForDirectAccount' => [
        'request'  => [
            'url'     => '/transactions',
            'method'  => 'get',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 4,
                'has_more' => false,
                'items'    => [
                    [
                        'entity' => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount' => 1000,
                        'currency' => 'INR',
                        'credit' => 1000,
                        'debit' => 0,
                        'balance' => 10001000,
                        'source' => [
                            'utr' => '211708954836',
                            'amount' => 1000,
                            'entity' => 'external',
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 0,
                        'credit'         => 1000,
                        'currency'       => 'INR',
                        'balance'        => 10001000,
                        'source'         => [
                            'entity'   => 'reversal',
                            //'payout_id'  => 'pout_JO8gIIQMhUfn52',
                            'amount'   => 1000,
                            'fee'      => 0,
                            'tax'      => 0,
                            'currency' => 'INR',
                            'utr'      => null,
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' =>
                                [
                                    'entity'            => 'fund_account',
                                    'contact'           => [
                                        'entity'   => 'contact',
                                        'name'     => 'tester',
                                        'contact'  => '9123456789',
                                        'batch_id' => null,
                                        'notes'    => [],
                                    ],
                                    'account_type'      => 'bank_account',
                                    'merchant_disabled' => false,
                                    'bank_account'      => [
                                        'ifsc'           => 'SBIN0007105',
                                        'bank_name'      => 'State Bank of India',
                                        'name'           => 'test',
                                        'notes'          => [],
                                        'account_number' => '111000',
                                    ],
                                    'batch_id'          => null,
                                ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'reversed',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => [
                                'entity'   => 'reversal',
                                //'payout_id'  => 'pout_JO8gIIQMhUfn52',
                                'amount'   => 1000,
                                'fee'      => 0,
                                'tax'      => 0,
                                'currency' => 'INR',
                                'utr'      => null,
                            ],
                            'fee_type'     => null,
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'created_at'     => 1650628947,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'            => 'fund_account',
                                'contact'           => [
                                    'id'           => 'cont_1000010contact',
                                    'name'         => 'test user',
                                    'contact'      => '8888888888',
                                    'email'        => 'contact@razorpay.com',
                                    'batch_id'     => null,
                                    'notes'        => [],
                                ],
                                'account_type'      => 'bank_account',
                                'merchant_disabled' => false,
                                'bank_account'      => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [],
                                    'account_number' => '111000',
                                ],
                                'batch_id'          => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testStatementsFetchLogicForBankingForDirectAccount' => [
        'request' => [
            'url'    => '/transactions_banking',
            'method' => 'get',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
            'content' => [
                'count' => 10,
                'skip'  => 0,
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchMultipleStatementsForBankingForDirectAccount' => [
        'request' => [
            'url'    => '/transactions_banking',
            'method' => 'get',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
            'content' => [
                'count' => 10,
                'skip'  => 0,
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 3,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 0,
                        'credit'         => 1000,
                        'currency'       => 'INR',
                        'balance'        => 10001000,
                        'source'         => [
                            'entity'   => 'reversal',
                            //'payout_id'  => 'pout_JO8gIIQMhUfn52',
                            'amount'   => 1000,
                            'fee'      => 0,
                            'tax'      => 0,
                            'currency' => 'INR',
                            'utr'      => null,
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' =>
                                [
                                    'entity'            => 'fund_account',
                                    'contact'           => [
                                        'entity'   => 'contact',
                                        'name'     => 'tester',
                                        'contact'  => '9123456789',
                                        'batch_id' => null,
                                        'notes'    => [],
                                    ],
                                    'account_type'      => 'bank_account',
                                    'merchant_disabled' => false,
                                    'bank_account'      => [
                                        'ifsc'           => 'SBIN0007105',
                                        'bank_name'      => 'State Bank of India',
                                        'name'           => 'test',
                                        'notes'          => [],
                                        'account_number' => '111000',
                                    ],
                                    'batch_id'          => null,
                                ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'reversed',
                            'utr'          => null,
                            'mode'         => null,
                            'fee_type'     => null,
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'created_at'     => 1650628947,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'            => 'fund_account',
                                'contact'           => [
                                    'id'           => 'cont_1000010contact',
                                    'name'         => 'test user',
                                    'contact'      => '8888888888',
                                    'email'        => 'contact@razorpay.com',
                                    'batch_id'     => null,
                                    'notes'        => [],
                                ],
                                'account_type'      => 'bank_account',
                                'merchant_disabled' => false,
                                'bank_account'      => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [],
                                    'account_number' => '111000',
                                ],
                                'batch_id'          => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleStatementsWithMerchantRules' => [
        'request'  => [
            'url'     => '/transactions',
            'method'  => 'get',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 3,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 0,
                        'credit'         => 1000,
                        'currency'       => 'INR',
                        'balance'        => 10001000,
                        'source'         => [
                            'entity'   => 'reversal',
                            //'payout_id'  => 'pout_JO8gIIQMhUfn52',
                            'amount'   => 1000,
                            'fee'      => 0,
                            'tax'      => 0,
                            'currency' => 'INR',
                            'utr'      => null,
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' =>
                                [
                                    'entity'            => 'fund_account',
                                    'contact'           => [
                                        'entity'   => 'contact',
                                        'name'     => 'tester',
                                        'contact'  => '9123456789',
                                        'batch_id' => null,
                                        'notes'    => [],
                                    ],
                                    'account_type'      => 'bank_account',
                                    'merchant_disabled' => false,
                                    'bank_account'      => [
                                        'ifsc'           => 'SBIN0007105',
                                        'bank_name'      => 'State Bank of India',
                                        'name'           => 'test',
                                        'notes'          => [],
                                        'account_number' => '111000',
                                    ],
                                    'batch_id'          => null,
                                ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'reversed',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => [
                                'entity'   => 'reversal',
                                //'payout_id'  => 'pout_JO8gIIQMhUfn52',
                                'amount'   => 1000,
                                'fee'      => 0,
                                'tax'      => 0,
                                'currency' => 'INR',
                                'utr'      => null,
                            ],
                            'fee_type'     => null,
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'created_at'     => 1650628947,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'            => 'fund_account',
                                'contact'           => [
                                    'id'           => 'cont_1000010contact',
                                    'name'         => 'test user',
                                    'contact'      => '8888888888',
                                    'email'        => 'contact@razorpay.com',
                                    'batch_id'     => null,
                                    'notes'        => [],
                                ],
                                'account_type'      => 'bank_account',
                                'merchant_disabled' => false,
                                'bank_account'      => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [],
                                    'account_number' => '111000',
                                ],
                                'batch_id'          => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchWithDateFilter' => [
        'request'  => [
            'url'     => '/transactions',
            'method'  => 'get',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 3,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 1000,
                        'debit'          => 0,
                        'balance'        => 10001000,
                        'source'         => [
                            'id'     => 'ext_testExternal00',
                            'utr'    => '211708954836',
                            'amount' => 1000,
                            'entity' => 'external',
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 1000,
                        'debit'          => 0,
                        'balance'        => 10001000,
                        'transaction_id' => NULL,
                        'source'         => [
                            'entity'   => 'reversal',
                            'amount'   => 1000,
                            'fee'      => 0,
                            'tax'      => 0,
                            'currency' => 'INR',
                            'utr'      => NULL,
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1000,
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'            => 'fund_account',
                                'contact_id'        => 'cont_1000010contact',
                                'contact'           => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => NULL,
                                    'active'   => true,
                                    'notes'    => [
                                    ],
                                ],
                                'account_type'      => 'bank_account',
                                'merchant_disabled' => false,
                                'bank_account'      => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'          => NULL,
                                'active'            => true,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => NULL,
                            'mode'         => NULL,
                            'reversal'     => NULL,
                            'fee_type'     => NULL,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactId' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 1,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'       => 'fund_account',
                                'contact'      => [
                                    'entity'   => 'contact',
                                    'name'     => 'tester',
                                    'contact'  => '9123456789',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [],
                                    'account_number' => '111000',
                                ],
                                'batch_id'     => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'reversed',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => [
                                'entity'    => 'reversal',
                                'amount'    => 1000,
                                'fee'       => 0,
                                'tax'       => 0,
                                'currency'  => 'INR',
                                'utr'       => null,
                            ],
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByPayoutId' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 1,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'       => 'fund_account',
                                'contact'      => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'     => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByUtr' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 1,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'       => 'fund_account',
                                'contact'      => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'     => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => 'Dq3XuFEay83Zlo',
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByType' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 2,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'       => 'fund_account',
                                'contact'      => [
                                    'entity'   => 'contact',
                                    'name'     => 'tester',
                                    'contact'  => '9123456789',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'     => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'reversed',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => [
                                'entity'   => 'reversal',
                                'amount'   => 1000,
                                'fee'      => 0,
                                'tax'      => 0,
                                'currency' => 'INR',
                                'utr'      => null,
                            ],
                            'fee_type'     => null,
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'       => 'fund_account',
                                'contact'      => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'     => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByInvalidType' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\InvalidArgumentException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        ],
    ],

    'testFetchByContactName' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'            => 'fund_account',
                                'contact'           => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => null,
                                    'active'   => true,
                                    'notes'    => [],
                                ],
                                'account_type'      => 'bank_account',
                                'merchant_disabled' => false,
                                'bank_account'      => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'          => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactNameExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'contact_name' => [
                                    'query'                =>'test user',
                                    'boost'                => 2,
                                    'minimum_should_match' => '75%',
                                ],
                            ],
                        ],
                    ],
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'balance_id' => [
                                            'value' => 'BfCGvMZswckZl8',
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testFetchByContactNameExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByContactEmail' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'            => 'fund_account',
                                'contact'           => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact2@razorpay.com',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type'      => 'bank_account',
                                'merchant_disabled' => false,
                                'bank_account'      => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'          => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactEmailExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'contact_email.raw' => [
                                            'value'                => 'contact@razorpay.com',
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'balance_id' => [
                                            'value' => 'BfCGvMZswckZl8',
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testFetchByContactEmailExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByContactEmailPartial' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'            => 'fund_account',
                                'contact'           => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact2@razorpay.com',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type'      => 'bank_account',
                                'merchant_disabled' => false,
                                'bank_account'      => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'          => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactEmailPartialExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'contact_email.partial_search' => [
                                            'value'                => 'contact@',
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'balance_id' => [
                                            'value' => 'BfCGvMZswckZl8',
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testFetchByContactEmailPartialExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByContactPhonePartial' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'            => 'fund_account',
                                'contact'           => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '9888888888',
                                    'email'    => 'contact2@razorpay.com',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type'      => 'bank_account',
                                'merchant_disabled' => false,
                                'bank_account'      => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'          => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactPhonePartialExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'contact_phone' => [
                                            'value'                => '988888',
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'balance_id' => [
                                            'value' => 'BfCGvMZswckZl8',
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testFetchByContactPhonePartialExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByFundAccountNumber' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'            => 'fund_account',
                                'contact'           => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => null,
                                    'active'   => true,
                                    'notes'    => [],
                                ],
                                'account_type'      => 'bank_account',
                                'merchant_disabled' => false,
                                'bank_account'      => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'          => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'            => 'fund_account',
                                'contact'           => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => null,
                                    'active'   => true,
                                    'notes'    => [],
                                ],
                                'account_type'      => 'bank_account',
                                'merchant_disabled' => false,
                                'bank_account'      => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'          => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByFundAccountNumberExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'fund_account_number' => [
                                    'query'                => '111000',
                                    'boost'                => 2,
                                    'minimum_should_match' => '75%',
                                ],
                            ],
                        ],
                    ],
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'balance_id' => [
                                            'value' => 'BfCGvMZswckZl8',
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testFetchByFundAccountNumberExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByNotes' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'            => 'fund_account',
                                'contact'           => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => null,
                                    'active'   => true,
                                    'notes'    => [],
                                ],
                                'account_type'      => 'bank_account',
                                'merchant_disabled' => false,
                                'bank_account'      => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'          => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'text' => 'testPayout',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByNotesExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'notes.value' => [
                                    'query'                => 'testPayout',
                                ],
                            ],
                        ],
                    ],
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'balance_id' => [
                                            'value' => 'BfCGvMZswckZl8',
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testFetchByNotesExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByContactPhone' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 1,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'       => 'fund_account',
                                'contact'      => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [],
                                    'account_number' => '111000',
                                ],
                                'batch_id'     => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByFundAccountId' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 1,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'debit'          => 1000,
                        'credit'         => 0,
                        'currency'       => 'INR',
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'            => 'fund_account',
                                'contact'           => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => null,
                                    'active'   => true,
                                    'notes'    => [],
                                ],
                                'account_type'      => 'bank_account',
                                'merchant_disabled' => false,
                                'bank_account'      => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [],
                                    'account_number' => '111000',
                                ],
                                'batch_id'          => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testActionFilter' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testActionFilterFailedPrivateAuth' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'action is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testFetchByPayoutMode' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 2,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1000,
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'       => 'fund_account',
                                'contact'      => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'     => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => 'IMPS',
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1000,
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'       => 'fund_account',
                                'contact'      => [
                                    'entity'   => 'contact',
                                    'name'     => 'tester',
                                    'contact'  => '9123456789',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'     => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'reversed',
                            'utr'          => null,
                            'mode'         => 'IMPS',
                            'reversal'     => [
                                'entity'   => 'reversal',
                                'amount'   => 1000,
                                'fee'      => 0,
                                'tax'      => 0,
                                'currency' => 'INR',
                                'utr'      => null,
                            ],
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactType' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 1,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1000,
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'       => 'fund_account',
                                'contact_id'   => 'cont_1000012contact',
                                'contact'      => [
                                    'id'       => 'cont_1000012contact',
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'type'     => 'employee',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'     => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => 'IMPS',
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByPayoutPurpose' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 1,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1000,
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'       => 'fund_account',
                                'contact'      => [
                                    'id'         => 'cont_1000010contact',
                                    'entity'     => 'contact',
                                    'name'       => 'test user',
                                    'contact'    => '8888888888',
                                    'email'      => 'contact@razorpay.com',
                                    'batch_id'   => null,
                                    'active'     => true,
                                    'notes'      => [
                                    ],
                                ],
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'     => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleByTransactionId' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 1,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1000,
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'       => 'fund_account',
                                'contact_id'   => 'cont_1000010contact',
                                'contact'      => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'     => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleByBasId' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 1,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1000,
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'       => 'fund_account',
                                'contact_id'   => 'cont_1000010contact',
                                'contact'      => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'     => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleWhenBasHasNoTxnId' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 1,
                'has_more' => false,
                'items'    => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1000,
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'       => 'payout',
                            'fund_account' => [
                                'entity'       => 'fund_account',
                                'contact_id'   => 'cont_1000010contact',
                                'contact'      => [
                                    'entity'   => 'contact',
                                    'name'     => 'test user',
                                    'contact'  => '8888888888',
                                    'email'    => 'contact@razorpay.com',
                                    'batch_id' => null,
                                    'notes'    => [],
                                ],
                                'account_type' => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'SBIN0007105',
                                    'bank_name'      => 'State Bank of India',
                                    'name'           => 'test',
                                    'notes'          => [
                                    ],
                                    'account_number' => '111000',
                                ],
                                'batch_id'     => null,
                            ],
                            'amount'       => 1000,
                            'notes'        => [
                                'abc' => 'xyz',
                            ],
                            'fees'         => 590,
                            'tax'          => 90,
                            'status'       => 'processing',
                            'utr'          => null,
                            'mode'         => null,
                            'reversal'     => null,
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleStatementsForDirectAccountWithPrivateAuth' => [
        'request'  => [
            'url'     => '/transactions',
            'method'  => 'get',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 4,
                'items'  => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 1000,
                        'debit'          => 0,
                        'balance'        => 10001000,
                        'source'         => [
                            'utr'    => '211708954836',
                            'amount' => 1000,
                            'entity' => 'external',
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 1000,
                        'debit'          => 0,
                        'balance'        => 10001000,
                        'transaction_id' => null,
                        'source'         => [
                            'entity'     => 'reversal',
                            'amount'     => 1000,
                            'fee'        => 0,
                            'tax'        => 0,
                            'currency'   => 'INR',
                            'utr'        => null,
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1000,
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'     => 'payout',
                            'amount'     => 1000,
                            'notes'      => [
                                'abc' => 'xyz',
                            ],
                            'fees'       => 590,
                            'tax'        => 90,
                            'status'     => 'reversed',
                            'utr'        => null,
                            'mode'       => null,
                            'fee_type'   => null,
                        ],
                    ],
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1000,
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'     => 'payout',
                            'amount'     => 1000,
                            'notes'      => [
                                'abc' => 'xyz',
                            ],
                            'fees'       => 590,
                            'tax'        => 90,
                            'status'     => 'processing',
                            'utr'        => null,
                            'mode'       => null,
                            'fee_type'   => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactIdWithPrivateAuth' => [
        'request'  => [
            'url'     => '/transactions',
            'method'  => 'get',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity' => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount' => 1000,
                        'currency' => 'INR',
                        'credit' => 0,
                        'debit' => 1000,
                        'balance' => 9999000,
                        'source' => [
                            'entity' => 'payout',
                            'amount' => 1000,
                            'notes' => [
                                'abc' => 'xyz',
                            ],
                            'fees' => 590,
                            'tax' => 90,
                            'status' => 'reversed',
                            'utr' => NULL,
                            'mode' => NULL,
                            'fee_type' => NULL,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactNameWithPrivateAuth' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity' => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount' => 1000,
                        'currency' => 'INR',
                        'credit' => 0,
                        'debit' => 1000,
                        'balance' => 9999000,
                        'source' => [
                            'entity' => 'payout',
                            'amount' => 1000,
                            'notes' => [
                                'abc' => 'xyz',
                            ],
                            'fees' => 590,
                            'tax' => 90,
                            'status' => 'processing',
                            'utr' => NULL,
                            'mode' => NULL,
                            'fee_type' => NULL,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactNameWithPrivateAuthExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'contact_name' => [
                                    'query'                =>'test user',
                                    'boost'                => 2,
                                    'minimum_should_match' => '75%',
                                ],
                            ],
                        ],
                    ],
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'balance_id' => [
                                            'value' => 'BfCGvMZswckZl8',
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testFetchByContactNameWithPrivateAuthExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchMultipleByBasIdWithPrivateAuth' => [
        'request'  => [
            'url'     => '/transactions',
            'method'  => 'get',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1000,
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'     => 'payout',
                            'amount'     => 1000,
                            'notes'      => [
                                'abc' => 'xyz',
                            ],
                            'fees'       => 590,
                            'tax'        => 90,
                            'status'     => 'processing',
                            'utr'        => null,
                            'mode'       => null,
                            'fee_type'   => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleWhenBasHasNoTxnIdWithPrivateAuth' => [
        'request'  => [
            'url'     => '/transactions',
            'method'  => 'get',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1000,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1000,
                        'balance'        => 9999000,
                        'source'         => [
                            'entity'     => 'payout',
                            'amount'     => 1000,
                            'notes'      => [
                                'abc' => 'xyz',
                            ],
                            'fees'       => 590,
                            'tax'        => 90,
                            'status'     => 'processing',
                            'utr'        => null,
                            'mode'       => null,
                            'fee_type'   => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchTransactionByPublicBasIdForPayout' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
        ],
        'response' => [
            'content' => [
                'entity'         => 'transaction',
                'account_number' => '2224440041626905',
                'amount'         => 1000,
                'currency'       => 'INR',
                'credit'         => 0,
                'debit'          => 1000,
                'balance'        => 9999000,
                'source'         => [
                    'entity'   => 'payout',
                    'amount'   => 1000,
                    'fees'     => 590,
                    'tax'      => 90,
                    'status'   => 'processing',
                    'fee_type' => null,
                    'mode'     => null,
                ],
            ],
        ],
    ],

    'testFetchTransactionByPublicBasIdForPayoutWhenBasHasNoTxnLinked' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
        ],
        'response' => [
            'content' => [
                'entity'         => 'transaction',
                'account_number' => '2224440041626905',
                'amount'         => 1000,
                'currency'       => 'INR',
                'credit'         => 0,
                'debit'          => 1000,
                'balance'        => 9999000,
                'source'         => [
                    'entity'   => 'payout',
                    'amount'   => 1000,
                    'fees'     => 590,
                    'tax'      => 90,
                    'status'   => 'processing',
                    'fee_type' => null,
                    'mode'     => null,
                ],
            ],
        ],
    ],

    'testFetchTransactionByPublicBasIdForReversalWhenBasHasNoTxnLinked' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
        ],
        'response' => [
            'content' => [
                'entity'         => 'transaction',
                'account_number' => '2224440041626905',
                'amount'         => 1000,
                'currency'       => 'INR',
                'credit'         => 1000,
                'debit'          => 0,
                'balance'        => 10001000,
                'source'         => [
                    'entity'   => 'reversal',
                    'amount'   => 1000,
                    'fee'     => 0,
                    'tax'      => 0,
                    'currency' => 'INR',
                ],
            ],
        ],
    ],

    'testFetchTransactionByPublicBasIdForExternalEntityWhenBasHasNoTxnLinked' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
        ],
        'response' => [
            'content' => [
                'entity'         => 'transaction',
                'account_number' => '2224440041626905',
                'amount'         => 1000,
                'currency'       => 'INR',
                'credit'         => 1000,
                'debit'          => 0,
                'balance'        => 10001000,
                'source'         => [
                    'entity'   => 'external',
                    'amount'   => 1000,
                    'utr'      => '211708954836',
                ],
            ],
        ],
    ],
];
