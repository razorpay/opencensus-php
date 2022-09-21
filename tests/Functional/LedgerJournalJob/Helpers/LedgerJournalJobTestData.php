<?php

namespace RZP\Tests\Functional\LedgerJournalJob;

return [
    'testPayoutTransactionCreation' => [
        'payload' => [
            "id"                => "HNjsypA96SgJKJ",
            "created_at"        => "1623848289",
            "updated_at"        => "1632368730",
            "amount"            => "130.000000",
            "base_amount"       => "130.000000",
            "currency"          => "INR",
            "tenant"            => "X",
            "transactor_id"     => "pout_SamplePoutId12",
            "transactor_event"  => "payout_initiated",
            "transaction_date"  => "1611132045",
            "ledger_entry" => [
                [
                    "id"          => "HNjsypHNXdSiei",
                    "created_at"  => "1623848289",
                    "updated_at"  => "1623848289",
                    "merchant_id" => "HN59oOIDACOXt3",
                    "journal_id"  => "HNjsypA96SgJKJ",
                    "account_id"  => "GoRNyEuu9Hl0OZ",
                    "amount"      => "130.000000",
                    "base_amount" => "130.000000",
                    "type"        => "debit",
                    "currency"    => "INR",
                    "balance"     => "24500",
                    'account_entities' => [
                        'account_type'       => ['payable'],
                        'banking_account_id' => ['bacc_sampleBankngId'],
                        'fund_account_type'  => ['merchant_va'],
                        'transactor'         => ['X'],
                    ],
                ],
                [
                    "id"          => "HNjsypHPOUlxDR",
                    "created_at"  => "1623848289",
                    "updated_at"  => "1623848289",
                    "merchant_id" => "HN59oOIDACOXt3",
                    "journal_id"  => "HNjsypA96SgJKJ",
                    "account_id"  => "HN5AGgmKu0ki13",
                    "amount"      => "130.000000",
                    "base_amount" => "130.000000",
                    "type"        => "credit",
                    "currency"    => "INR",
                    "balance"     => "",
                    'account_entities' => [
                        'account_type'       => ['payable'],
                        'banking_account_id' => ['bacc_sampleBankngId'],
                        'fund_account_type'  => ['merchant_va_vendor'],
                        'transactor'         => ['X'],
                    ],
                ]
            ]
        ]
    ],

    'testReversalTransactionCreation' => [
        'payload' => [
            "id"                => "HNjsypA96SgJKJ",
            "created_at"        => "1623848289",
            "updated_at"        => "1632368730",
            "amount"            => "130.000000",
            "base_amount"       => "130.000000",
            "currency"          => "INR",
            "tenant"            => "X",
            "transactor_id"     => "rvrsl_SampleRvrslId2",
            "transactor_event"  => "payout_reversed",
            "transaction_date"  => "1611132045",
            "ledger_entry" => [
                [
                    "id"          => "HNjsypHNXdSiei",
                    "created_at"  => "1623848289",
                    "updated_at"  => "1623848289",
                    "merchant_id" => "HN59oOIDACOXt3",
                    "journal_id"  => "HNjsypA96SgJKJ",
                    "account_id"  => "GoRNyEuu9Hl0OZ",
                    "amount"      => "130.000000",
                    "base_amount" => "130.000000",
                    "type"        => "debit",
                    "currency"    => "INR",
                    "balance"     => "21200",
                    'account_entities' => [
                        'account_type'       => ['payable'],
                        'banking_account_id' => ['bacc_sampleBankngId'],
                        'fund_account_type'  => ['merchant_va'],
                        'transactor'         => ['X'],
                    ],
                ],
                [
                    "id"          => "HNjsypHPOUlxDR",
                    "created_at"  => "1623848289",
                    "updated_at"  => "1623848289",
                    "merchant_id" => "HN59oOIDACOXt3",
                    "journal_id"  => "HNjsypA96SgJKJ",
                    "account_id"  => "HN5AGgmKu0ki13",
                    "amount"      => "130.000000",
                    "base_amount" => "130.000000",
                    "type"        => "credit",
                    "currency"    => "INR",
                    "balance"     => "",
                    'account_entities' => [
                        'account_type'       => ['payable'],
                        'banking_account_id' => ['bacc_sampleBankngId'],
                        'fund_account_type'  => ['merchant_va_vendor'],
                        'transactor'         => ['X'],
                    ],
                ]
            ]
        ]
    ]
];
