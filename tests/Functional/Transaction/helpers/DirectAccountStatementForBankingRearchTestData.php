<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\InvalidArgumentException;

return [
    'testFetchMultipleStatementsForDirectAccountRearch' => [
        'request'  => [
            'url'     => '/transactions_banking',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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

    'testFetchMultipleStatementsForDirectAccountWithPrivateAuthRearch' => [
        'request'  => [
            'url'     => '/transactions_banking',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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

    'testFetchMultipleStatementsWithMerchantRulesRearch' => [
        'request'  => [
            'url'     => '/transactions_banking',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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

    'testFetchWithDateFilterRearch' => [
        'request'  => [
            'url'     => '/transactions_banking',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => NULL,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactIdRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactIdWithPrivateAuthRearch' => [
        'request'  => [
            'url'     => '/transactions_banking',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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

    'testFetchByPayoutIdRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByUtrRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByTypeRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByInvalidTypeRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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

    'testFetchByContactNameRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactNameRearchExpectedSearchParams' => [
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

    'testFetchByContactNameRearchExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByContactEmailRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactEmailRearchExpectedSearchParams' => [
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

    'testFetchByContactEmailRearchExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByContactEmailPartialRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactEmailPartialRearchExpectedSearchParams' => [
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

    'testFetchByContactEmailPartialRearchExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByContactPhonePartialRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactPhonePartialRearchExpectedSearchParams' => [
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

    'testFetchByContactPhonePartialRearchExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByFundAccountNumberRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByFundAccountNumberRearchExpectedSearchParams' => [
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

    'testFetchByFundAccountNumberRearchExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByNotesRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByNotesRearchExpectedSearchParams' => [
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

    'testFetchByNotesRearchExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByContactPhoneRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByFundAccountIdRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testActionFilterFailedPrivateAuthRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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

    'testFetchByPayoutModeRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactTypeRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByPayoutPurposeRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleByTransactionIdRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleByBasIdRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleWhenBasHasNoTxnIdRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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
                            'fee_type'     => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByContactNameWithPrivateAuthRearch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions_banking',
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

    'testFetchByContactNameWithPrivateAuthRearchExpectedSearchParams' =>[
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

    'testFetchByContactNameWithPrivateAuthRearchExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchMultipleByBasIdWithPrivateAuthRearch' => [
        'request'  => [
            'url'     => '/transactions_banking',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
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

    'testFetchMultipleWhenBasHasNoTxnIdWithPrivateAuthRearch' => [
        'request'  => [
            'url'     => '/transactions_banking',
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
];
