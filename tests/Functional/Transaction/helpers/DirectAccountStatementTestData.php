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

    'testFetchByContactEmailExpectedSearchResponse' => [
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
];
