<?php

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
];
