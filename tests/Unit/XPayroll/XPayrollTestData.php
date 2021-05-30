<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Exception\BadRequestValidationFailureException;

return [
    'testGetBankingAccountsInternal' => [
        'request'  => [
            'url'    => '/banking_accounts_internal',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [

            ],
        ],
    ],

    'testCreatePayout' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'mode'                 => 'NEFT',
                'amount'               => 2000000,
                'origin'               => 'dashboard',
                'purpose'              => 'salary',
                'currency'             => 'INR',
                'narration'            => 'RameshParekh Salary Jan 2021',
                'fund_account'         => [
                    'contact'      => [
                        'name'         => 'Manpreet Balan',
                        'type'         => 'vendor',
                        'email'        => 'manpreet.balan@mailinator.com',
                        'notes'        => [
                            'notes_key_1' => 'Ramesh-Parekh Salary Jan 2021',
                        ],
                        'contact'      => '8147309514',
                        'reference_id' => '2149',
                    ],
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'ifsc'           => 'SBIN0005943',
                        'name'           => 'Manpreet Balan',
                        'account_number' => '2598294895',
                    ],
                ],
                'reference_id'         => 'xpayroll_merchant_payouts_161',
                'account_number'       => '2224440041626905',
                'source_details'       => [
                    0 => [
                        'priority'    => '1',
                        'source_id'   => 'xpayroll_merchant_payouts_161',
                        'source_type' => 'xpayroll',
                    ],
                ],
                'queue_if_low_balance' => true,
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'entity'       => 'payout',
                'mode'         => 'NEFT',
                'amount'       => 2000000,
                'origin'       => 'dashboard',
                'purpose'      => 'salary',
                'currency'     => 'INR',
                'narration'    => 'RameshParekh Salary Jan 2021',
                'fund_account' => [
                    'contact'      => [
                        'name'         => 'Manpreet Balan',
                        'type'         => 'vendor',
                        'email'        => 'manpreet.balan@mailinator.com',
                        'notes'        => [
                            'notes_key_1' => 'Ramesh-Parekh Salary Jan 2021',
                        ],
                        'contact'      => '8147309514',
                        'reference_id' => '2149',
                    ],
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'ifsc'           => 'SBIN0005943',
                        'name'           => 'Manpreet Balan',
                        'account_number' => '2598294895',
                    ],
                ],
                'reference_id' => 'xpayroll_merchant_payouts_161',

                'source_details' => [
                    0 => [
                        'priority'    => 1,
                        'source_id'   => 'xpayroll_merchant_payouts_161',
                        'source_type' => 'xpayroll',
                    ],
                ]
            ]
        ]
    ]
];
