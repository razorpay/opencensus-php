<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testBankingInvoiceEntityCreateForGivenMonthYear' => [
        'rx_transactions'      => [
            'amount'        => 500,
            'tax'           => 90,
        ],
    ],
    'testBankingInvoiceEntityCreateForMultipleAccountsForGivenMonthYear'  => [
        'rx_transactions'   =>[
            [
                "amount" => 900,
                "tax" => 162,
            ],
            [
                "amount" => 500,
                "tax" => 90,
            ],
        ],
    ],
    'testBankingInvoiceEntityCreateForMultipleAccountsForGivenMonthYearForMultipleMerchants' => [
        'rx_transactions'   =>[
            [
                'merchant_id' =>  "100000Razorpay",
                'amount' =>  500,
                'tax' =>  90,
            ],
            [
                'merchant_id' =>  "10000000000000",
                'amount' =>  900,
                'tax' =>  162,
            ],
            [
                'merchant_id' =>  "10000000000000",
                'amount' =>  500,
                'tax' =>  90,
            ],
        ],
    ],
    'testBankingInvoiceEntityCreateForMultipleAccountsForGivenMonthYearForGivenMerchantWithNoBankingTransaction'=>[
        'rx_transactions'   =>[
            [
                "amount" => 0,
                "tax" => 0,
            ],
            [
                "amount" => 0,
                "tax" => 0,
            ],
        ],
    ],
    'testBankingInvoiceEntityCreateForMultipleAccountsForGivenMonthYearWithPayoutReversed' => [
        'rx_transactions'   =>[
            [
                'type' =>   "rx_transactions",
                'amount' =>  0,
                'tax' =>  0,
            ],
            [
                'type' =>   "rx_transactions",
                'amount' =>  500,
                'tax' =>  90,
            ],
        ],
    ],
    'testBankingInvoiceEntityCreateForMultipleAccountsWithPayoutReversalInNextMonthAndNoPayoutsNextMonth' => [
        'rx_transactions'   =>[
            [
                'month' => 8,
                'year'  => 2019,
                'amount' =>  -900,
                'tax' =>  -162,
            ],
            [
                'month' => 8,
                'year'  => 2019,
                'amount' =>  0,
                'tax' =>  0,
            ],
            [
                'month' => 7,
                'year'  => 2019,
                'amount' =>  900,
                'tax' =>  162,
            ],
            [
                'month' => 7,
                'year'  => 2019,
                'amount' =>  500,
                'tax' =>  90,
            ],
        ],
    ],
    'testBankingInvoiceEntityCreateForMultipleAccountsWithPayoutReversalInNextMonthAndSomePayoutsNextMonthFromAnotherBankingBalance' => [
        'rx_transactions'   =>[
            [
                'month' => 8,
                'year'  => 2019,
                'amount' =>  -900,
                'tax' =>  -162,
            ],
            [
                'month' => 8,
                'year'  => 2019,
                'amount' =>  500,
                'tax' =>  90,
            ],
            [
                'month' => 7,
                'year'  => 2019,
                'amount' =>  900,
                'tax' =>  162,
            ],
            [
                'month' => 7,
                'year'  => 2019,
                'amount' =>  500,
                'tax' =>  90,
            ],
        ],
    ],
    'testFetchMultipleBankingInvoices' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchants/banking/invoices',
            'content' => [
                'month' => 7,
                'year'  => 2019,
                ],
            ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' =>  [
                    [
                        'month'          => 7,
                        'year'           => 2019,
                        'amount'         => 900,
                        'tax'            => 162,
                        'account_number' => '1234567',
                    ],
                    [
                        'month'          => 7,
                        'year'           => 2019,
                        'amount'         => 500,
                        'tax'            => 90,
                        'account_number' => '12345',
                    ],
                ],
            ],
        ],
    ],
    'testFetchMultipleBankingInvoicesGivenAccountNumber' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchants/banking/invoices',
            'content' => [
                'month'          => 7,
                'year'           => 2019,
                'account_number' => '1234567',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'month'          => 7,
                        'year'           => 2019,
                        'amount'         => 900,
                        'tax'            => 162,
                        'account_number' => '1234567',
                    ],
                ],
            ],
        ],
    ],
    'testFetchMultipleBankingInvoicesGivenNoInputs' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchants/banking/invoices',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 4,
                'items'  => [
                    [
                        'month'          => 8,
                        'year'           => 2019,
                        'amount'         => 0,
                        'tax'            => 0,
                        'account_number' => '1234567',
                    ],
                    [
                        'month'          => 8,
                        'year'           => 2019,
                        'amount'         => 0,
                        'tax'            => 0,
                        'account_number' => '12345',
                    ],
                    [
                        'month'          => 7,
                        'year'           => 2019,
                        'amount'         => 900,
                        'tax'            => 162,
                        'account_number' => '1234567',
                    ],
                    [
                        'month'          => 7,
                        'year'           => 2019,
                        'amount'         => 500,
                        'tax'            => 90,
                        'account_number' => '12345',
                    ],
                ],
            ],
        ],
    ],
    'testFetchMultipleBankingInvoicesGivenNoInputsAndNoBankingInvoiceGeneratedYet' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchants/banking/invoices',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 0,
                'items'  => [],
            ],
        ],
    ],

    'testFetchMultipleBankingInvoicesWithBusinessBankingNotEnabled' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchants/banking/invoices',
            'content' => [
                'month' => 7,
                'year'  => 2019,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FORBIDDEN_BUSINESS_BANKING_NOT_ENABLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN_BUSINESS_BANKING_NOT_ENABLED,
        ],
    ],
];
