<?php

return [
    'testCustomerTransferWalletAPILedgerPositive' => [
        'payload' => [
            "transactor_id" => "trf_PHYFBK0zckpq4l",
            "updated_at" => 1730793943,
            "transactor_event" => "customer_wallet_loading",
            "tenant" => "PG",
            "created_at" => 1730793943,
            "id" => "PHYFBdSfkJNuox",
            "base_amount" => "10017",
            "amount" => "10017",
            "currency" => "INR",
            "transaction_date" => 1730793942,
            "ledger_entry" => [
                [
                    "type" => "credit",
                    "balance" => "9090958.000000",
                    "base_amount" => "15",
                    "balance_updated" => true,
                    "currency" => "INR",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUpiAT02B",
                    "account_id" => "KXvs9rIgC6dKW6",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "15",
                    "updated_at" => 1730793943,
                    "account_entities" => [
                        "fund_account_type" => ["rzp_transfer_fee"],
                        "account_type" => ["cash"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "credit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "2",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUqgMYCBm",
                    "account_id" => "KXvs9q3KecMJp9",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "2",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "account_entities" => [
                        "fund_account_type" => ["rzp_gst"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "debit",
                    "balance" => "967363335.000000",
                    "base_amount" => "10017",
                    "balance_updated" => true,
                    "currency" => "INR",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUr6VwJa3",
                    "account_id" => "KXvs9ocdYKc5AB",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10017",
                    "updated_at" => 1730793943,
                    "account_entities" => [
                        "fund_account_type" => ["merchant_balance"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "credit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "10000",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUrOEyEDb",
                    "account_id" => "KYiD09b8ThHpOG",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "account_entities" => [
                        "fund_account_type" => ["customer_wallet"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ]
            ]
        ],
        'transfer' => [
            'id'              => 'PHYFBK0zckpq4l',
            'to_type'         => 'customer',
            'to_id'           => 'PHYFBK0zckpq4l',
            'amount'          => '10000',
            'currency'        => 'INR',
            'merchant_id'     => '10000000000000',
            'source_id'       => 'PHYFBK0zckpq4l',
            'source_type'     => 'payment'
        ],
        'expected' => [
            'id'              => 'PHYFBdSfkJNuox',
            'type'            => 'transfer',
            'entity_id'       => 'PHYFBK0zckpq4l',
            'amount'          => 10000,
            'credit'          => 0,
            'debit'           => 10017,
            'currency'        => 'INR',
            'credit_type'     => 'default',
            'reconciled_type' => 'na',
            'fee'             => 17,
            'tax'             => 2,
            'channel'         => 'axis',
            'fee_model'       => 'prepaid',
            'merchant_id'     => '10000000000000'
        ]
    ],
    'testCustomerTransferWalletAPILedgerPostpaid' => [
        'payload' => [
            "transactor_id" => "trf_PHYFBK0zckpq4l",
            "updated_at" => 1730793943,
            "transactor_event" => "customer_wallet_loading",
            "tenant" => "PG",
            "created_at" => 1730793943,
            "id" => "PHYFBdSfkJNuox",
            "base_amount" => "10017",
            "amount" => "10017",
            "currency" => "INR",
            "transaction_date" => 1730793942,
            "ledger_entry" => [
                [
                    "type" => "credit",
                    "balance" => "9090958.000000",
                    "base_amount" => "15",
                    "balance_updated" => true,
                    "currency" => "INR",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUpiAT02B",
                    "account_id" => "KXvs9rIgC6dKW6",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "15",
                    "updated_at" => 1730793943,
                    "account_entities" => [
                        "fund_account_type" => ["rzp_transfer_fee"],
                        "account_type" => ["cash"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "credit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "2",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUqgMYCBm",
                    "account_id" => "KXvs9q3KecMJp9",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "2",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "account_entities" => [
                        "fund_account_type" => ["rzp_gst"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "debit",
                    "balance" => "967363335.000000",
                    "base_amount" => "10000",
                    "balance_updated" => true,
                    "currency" => "INR",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUr6VwJa3",
                    "account_id" => "KXvs9ocdYKc5AB",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "updated_at" => 1730793943,
                    "account_entities" => [
                        "fund_account_type" => ["merchant_balance"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "credit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "10000",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUrOEyEDb",
                    "account_id" => "KYiD09b8ThHpOG",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "account_entities" => [
                        "fund_account_type" => ["customer_wallet"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "credit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "17",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUrOEyEDc",
                    "account_id" => "KYiD09b8ThHpOH",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "journal_id" => "PHYFBdSfkJNuoy",
                    "account_entities" => [
                        "fund_account_type" => ["merchant_invoice"],
                        "account_type" => ["receivable"]
                    ],
                    "merchant_id" => "10000000000000"
                ]
            ]
        ],
        'transfer' => [
            'id'              => 'PHYFBK0zckpq4l',
            'to_type'         => 'customer',
            'to_id'           => 'PHYFBK0zckpq4l',
            'amount'          => '10000',
            'currency'        => 'INR',
            'merchant_id'     => '10000000000000',
            'source_id'       => 'PHYFBK0zckpq4l',
            'source_type'     => 'payment'
        ],
        'expected' => [
            'id'              => 'PHYFBdSfkJNuox',
            'type'            => 'transfer',
            'entity_id'       => 'PHYFBK0zckpq4l',
            'amount'          => 10000,
            'credit'          => 0,
            'debit'           => 10000,
            'currency'        => 'INR',
            'credit_type'     => 'default',
            'reconciled_type' => 'na',
            'fee'             => 17,
            'tax'             => 2,
            'channel'         => 'axis',
            'fee_model'       => 'postpaid',
            'merchant_id'     => '10000000000000'
        ]
    ],
    'testCustomerTransferWalletAPILedgerFeeCredits' => [
        'payload' => [
            "transactor_id" => "trf_PHYFBK0zckpq4l",
            "updated_at" => 1730793943,
            "transactor_event" => "customer_wallet_loading",
            "tenant" => "PG",
            "created_at" => 1730793943,
            "id" => "PHYFBdSfkJNuox",
            "base_amount" => "10017",
            "amount" => "10017",
            "currency" => "INR",
            "transaction_date" => 1730793942,
            "ledger_entry" => [
                [
                    "type" => "credit",
                    "balance" => "9090958.000000",
                    "base_amount" => "15",
                    "balance_updated" => true,
                    "currency" => "INR",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUpiAT02B",
                    "account_id" => "KXvs9rIgC6dKW6",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "15",
                    "updated_at" => 1730793943,
                    "account_entities" => [
                        "fund_account_type" => ["rzp_transfer_fee"],
                        "account_type" => ["cash"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "credit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "2",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUqgMYCBm",
                    "account_id" => "KXvs9q3KecMJp9",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "2",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "account_entities" => [
                        "fund_account_type" => ["rzp_gst"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "debit",
                    "balance" => "967363335.000000",
                    "base_amount" => "10000",
                    "balance_updated" => true,
                    "currency" => "INR",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUr6VwJa3",
                    "account_id" => "KXvs9ocdYKc5AB",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "updated_at" => 1730793943,
                    "account_entities" => [
                        "fund_account_type" => ["merchant_balance"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "credit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "10000",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUrOEyEDb",
                    "account_id" => "KYiD09b8ThHpOG",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "account_entities" => [
                        "fund_account_type" => ["customer_wallet"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "debit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "17",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUrOEyEDc",
                    "account_id" => "KYiD09b8ThHpOH",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "journal_id" => "PHYFBdSfkJNuoy",
                    "account_entities" => [
                        "fund_account_type" => ["merchant_fee_credits"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ]
            ]
        ],
        'transfer' => [
            'id'              => 'PHYFBK0zckpq4l',
            'to_type'         => 'customer',
            'to_id'           => 'PHYFBK0zckpq4l',
            'amount'          => '10000',
            'currency'        => 'INR',
            'merchant_id'     => '10000000000000',
            'source_id'       => 'PHYFBK0zckpq4l',
            'source_type'     => 'payment'
        ],
        'expected' => [
            'id'              => 'PHYFBdSfkJNuox',
            'type'            => 'transfer',
            'entity_id'       => 'PHYFBK0zckpq4l',
            'amount'          => 10000,
            'credit'          => 0,
            'debit'           => 10000,
            'currency'        => 'INR',
            'credit_type'     => 'fee',
            'reconciled_type' => 'na',
            'fee'             => 17,
            'tax'             => 2,
            'channel'         => 'axis',
            'fee_model'       => 'prepaid',
            'merchant_id'     => '10000000000000'
        ]
    ],
    'testCustomerTransferWalletAPILedgerFeeCreditsNegative' => [
        'payload' => [
            "transactor_id" => "trf_PHYFBK0zckpq4l",
            "updated_at" => 1730793943,
            "transactor_event" => "customer_wallet_loading",
            "tenant" => "PG",
            "created_at" => 1730793943,
            "id" => "PHYFBdSfkJNuox",
            "base_amount" => "10017",
            "amount" => "10017",
            "currency" => "INR",
            "transaction_date" => 1730793942,
            "ledger_entry" => [
                [
                    "type" => "credit",
                    "balance" => "9090958.000000",
                    "base_amount" => "15",
                    "balance_updated" => true,
                    "currency" => "INR",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUpiAT02B",
                    "account_id" => "KXvs9rIgC6dKW6",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "15",
                    "updated_at" => 1730793943,
                    "account_entities" => [
                        "fund_account_type" => ["rzp_transfer_fee"],
                        "account_type" => ["cash"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "credit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "2",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUqgMYCBm",
                    "account_id" => "KXvs9q3KecMJp9",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "2",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "account_entities" => [
                        "fund_account_type" => ["rzp_gst"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "debit",
                    "balance" => "967363335.000000",
                    "base_amount" => "10000",
                    "balance_updated" => true,
                    "currency" => "INR",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUr6VwJa3",
                    "account_id" => "KXvs9ocdYKc5AB",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "updated_at" => 1730793943,
                    "account_entities" => [
                        "fund_account_type" => ["merchant_balance"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "credit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "10000",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUrOEyEDb",
                    "account_id" => "KYiD09b8ThHpOG",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "account_entities" => [
                        "fund_account_type" => ["customer_wallet"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "debit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "17",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUrOEyEDc",
                    "account_id" => "KYiD09b8ThHpOH",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "journal_id" => "PHYFBdSfkJNuoy",
                    "account_entities" => [
                        "fund_account_type" => ["merchant_fee_credits"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ]
            ]
        ],
        'transfer' => [
            'id'              => 'PHYFBK0zckpq4l',
            'to_type'         => 'customer',
            'to_id'           => 'PHYFBK0zckpq4l',
            'amount'          => '10000',
            'currency'        => 'INR',
            'merchant_id'     => '10000000000000',
            'source_id'       => 'PHYFBK0zckpq4l',
            'source_type'     => 'payment'
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => \RZP\Error\ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE,
        ]
    ],
    'testCustomerTransferWalletAPILedgerAmountCredits' => [
        'payload' => [
            "transactor_id" => "trf_PHYFBK0zckpq4l",
            "updated_at" => 1730793943,
            "transactor_event" => "customer_wallet_loading",
            "tenant" => "PG",
            "created_at" => 1730793943,
            "id" => "PHYFBdSfkJNuox",
            "base_amount" => "10000",
            "amount" => "10000",
            "currency" => "INR",
            "transaction_date" => 1730793942,
            "ledger_entry" => [
                [
                    "type" => "debit",
                    "balance" => "967363335.000000",
                    "base_amount" => "10000",
                    "balance_updated" => true,
                    "currency" => "INR",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUr6VwJa3",
                    "account_id" => "KXvs9ocdYKc5AB",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "updated_at" => 1730793943,
                    "account_entities" => [
                        "fund_account_type" => ["merchant_balance"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "debit",
                    "balance" => "967363335.000000",
                    "base_amount" => "10000",
                    "balance_updated" => true,
                    "currency" => "INR",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUr6VwJa3",
                    "account_id" => "KXvs9ocdYKc5AB",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "updated_at" => 1730793943,
                    "account_entities" => [
                        "fund_account_type" => ["reward"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "credit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "10000",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUrOEyEDb",
                    "account_id" => "KYiD09b8ThHpOG",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "account_entities" => [
                        "fund_account_type" => ["customer_wallet"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "credit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "10000",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUrOEyEDc",
                    "account_id" => "KYiD09b8ThHpOH",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "journal_id" => "PHYFBdSfkJNuoy",
                    "account_entities" => [
                        "fund_account_type" => ["razorpay_reward_control"],
                        "account_type" => ["receivable"]
                    ],
                    "merchant_id" => "10000000000000"
                ]
            ]
        ],
        'transfer' => [
            'id'              => 'PHYFBK0zckpq4l',
            'to_type'         => 'customer',
            'to_id'           => 'PHYFBK0zckpq4l',
            'amount'          => '10000',
            'currency'        => 'INR',
            'merchant_id'     => '10000000000000',
            'source_id'       => 'PHYFBK0zckpq4l',
            'source_type'     => 'payment'
        ],
        'expected' => [
            'id'              => 'PHYFBdSfkJNuox',
            'type'            => 'transfer',
            'entity_id'       => 'PHYFBK0zckpq4l',
            'amount'          => 10000,
            'credit'          => 0,
            'debit'           => 10000,
            'currency'        => 'INR',
            'credit_type'     => 'amount',
            'reconciled_type' => 'na',
            'fee'             => 0,
            'tax'             => 0,
            'channel'         => 'axis',
            'fee_model'       => 'prepaid',
            'merchant_id'     => '10000000000000'
        ]
    ],
    'testCustomerTransferWalletAPILedgerAmountCreditsNegative' => [
        'payload' => [
            "transactor_id" => "trf_PHYFBK0zckpq4l",
            "updated_at" => 1730793943,
            "transactor_event" => "customer_wallet_loading",
            "tenant" => "PG",
            "created_at" => 1730793943,
            "id" => "PHYFBdSfkJNuox",
            "base_amount" => "10000",
            "amount" => "10000",
            "currency" => "INR",
            "transaction_date" => 1730793942,
            "ledger_entry" => [
                [
                    "type" => "debit",
                    "balance" => "967363335.000000",
                    "base_amount" => "10000",
                    "balance_updated" => true,
                    "currency" => "INR",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUr6VwJa3",
                    "account_id" => "KXvs9ocdYKc5AB",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "updated_at" => 1730793943,
                    "account_entities" => [
                        "fund_account_type" => ["merchant_balance"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "debit",
                    "balance" => "967363335.000000",
                    "base_amount" => "10000",
                    "balance_updated" => true,
                    "currency" => "INR",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUr6VwJa3",
                    "account_id" => "KXvs9ocdYKc5AB",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "updated_at" => 1730793943,
                    "account_entities" => [
                        "fund_account_type" => ["reward"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "credit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "10000",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUrOEyEDb",
                    "account_id" => "KYiD09b8ThHpOG",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "journal_id" => "PHYFBdSfkJNuox",
                    "account_entities" => [
                        "fund_account_type" => ["customer_wallet"],
                        "account_type" => ["payable"]
                    ],
                    "merchant_id" => "10000000000000"
                ],
                [
                    "type" => "credit",
                    "updated_at" => 1730793943,
                    "currency" => "INR",
                    "base_amount" => "10000",
                    "created_at" => 1730793943,
                    "id" => "PHYFBdUrOEyEDc",
                    "account_id" => "KYiD09b8ThHpOH",
                    "notes" => [
                        "ledger_config_id" => "P9OwZChHi1t0PF"
                    ],
                    "amount" => "10000",
                    "journal_id" => "PHYFBdSfkJNuoy",
                    "account_entities" => [
                        "fund_account_type" => ["razorpay_reward_control"],
                        "account_type" => ["receivable"]
                    ],
                    "merchant_id" => "10000000000000"
                ]
            ]
        ],
        'transfer' => [
            'id'              => 'PHYFBK0zckpq4l',
            'to_type'         => 'customer',
            'to_id'           => 'PHYFBK0zckpq4l',
            'amount'          => '10000',
            'currency'        => 'INR',
            'merchant_id'     => '10000000000000',
            'source_id'       => 'PHYFBK0zckpq4l',
            'source_type'     => 'payment'
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => \RZP\Error\ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE,
        ]
    ],
];
