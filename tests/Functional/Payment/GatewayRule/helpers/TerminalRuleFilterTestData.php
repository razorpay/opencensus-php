<?php

use RZP\Models\Merchant;
use RZP\Models\Payment\Method;

return [
    'testRuleFilterCombinations' => [
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
            ],
        ],
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'A',
                ],
            ],
            'expected_terminal_ids' => [
                '1000AxisMigsTl',
                '1000SharpTrmnl',
            ],
        ],
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
                '1000AxisMigsTl',
            ],
        ],
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'A',
                ],
            ],
            'expected_terminal_ids' => [
                '1000SharpTrmnl'
            ],
        ],
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'A',
                ],
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared'
            ],
        ],
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
            ],
            'expected_terminal_ids' => [
                '1000AxisMigsTl'
            ],
        ],
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'cybersource',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'first_data',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'A',
                ],
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
                '1000AxisMigsTl',
                '1000SharpTrmnl',
            ],
        ],

        // Test rule filter combinations across different groups
        // one of the terminals selected by one group is rejected by other group
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'B',
                    'network'     => 'VISA'
                ]
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
            ],
        ],
        // All terminals selected by one group is rejected by other group
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'B',
                    'network'     => 'VISA'
                ]
            ],
            'expected_terminal_ids' => [
                '1000SharpTrmnl',
            ],
        ],
        // Of all terminals selected by one group, some are rejected by other group
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                    'network'     => 'VISA'
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'B',
                ],
            ],
            'expected_terminal_ids' => [
                '1000AxisMigsTl',
            ],
        ],
        // Of all terminals selected by one group, some are selected by other group
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                    'network'     => 'VISA'
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'B',
                ],
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
            ],
        ],
        // No terminals selected by one group match selection rules defined by other group
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                    'network'     => 'VISA'
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'cybersource',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'B',
                ],
            ],
            'expected_terminal_ids' => [
                '1000SharpTrmnl',
            ],
        ],
        // Terminals selected by 1 group don't match rejection rules defined by other group
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                    'network'     => 'VISA'
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'cybersource',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'B',
                ],
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
                '1000AxisMigsTl',
            ],
        ]
    ],

    'testMethodFilter' => [
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
            ]
        ],
        [
            'payment_options' => [
                'method' => Method::NETBANKING,
                'bank'   => 'HDFC',
            ],
            'fixtures' => [
                [
                    'method'      => 'netbanking',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'billdesk',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                    'issuer'      => 'HDFC',
                ],
            ],
            'expected_terminal_ids' => [
                '1000BdeskTrmnl',
            ]
        ],
        [
            'payment_options' => [
                'method' => Method::WALLET,
                'wallet' => 'olamoney',
            ],
            'fixtures' => [
                [
                    'method'      => 'wallet',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'wallet_olamoney',
                    'issuer'      => 'olamoney',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'      => 'wallet',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'wallet_jiomoney',
                    'issuer'      => 'jiomoney',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
            ],
            'expected_terminal_ids' => [
                '1000OlamoneyTl',
            ]
        ],
        [
            'payment_options' => [
                'method' => Method::EMI,
                'amount' => '300000',
                'emi' => [
                    'duration' => '9',
                    'bank' => 'HDFC',
                ],
                'card' => [
                    'number'       => '41476700000006',
                    'name'         => 'Harshil',
                    'expiry_month' => '12',
                    'expiry_year'  => '2017',
                    'cvv'          => '566',
                    'network'      => 'Visa',
                    'issuer'       => 'HDFC',
                ],
                'bank' => 'HDFC',
            ],
            'fixtures' => [
                [
                    'method'         => 'emi',
                    'merchant_id'    => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'        => 'hdfc',
                    'type'           => 'filter',
                    'filter_type'    => 'select',
                    'group'          => 'method_filter',
                    'emi_duration'   => 9,
                    'emi_subvention' => 'customer',
                    'issuer'         => 'HDFC',
                ],
            ],
            'expected_terminal_ids' => [
                'ShrdHdfcEmiTrm',
            ]
        ],
        [
            'payment_options' => [
                'method' => Method::EMI,
                'amount' => '300000',
                'emi' => [
                    'duration' => '9',
                    'bank' => 'KKBK',
                ],
                'card' => [
                    'number'       => '41476700000006',
                    'name'         => 'Harshil',
                    'expiry_month' => '12',
                    'expiry_year'  => '2017',
                    'cvv'          => '566',
                    'network'      => 'Visa',
                    'issuer'       => 'KKBK',
                ],
                'bank' => 'KKBK',
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
            ]
        ],
        [
            'payment_options' => [
                'method' => Method::UPI,
                'vpa' => 'vishnu@icici',
            ],
            'fixtures' => [
                [
                    'method'      => Method::UPI,
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'upi_icici',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
            ],
            'expected_terminal_ids' => [
                '100UPIICICITml',
            ]
        ],
    ],

    'testMethodFilterWithMerchantEmiSubvention' => [
        'payment_options' => [
                'method' => Method::EMI,
                'amount' => '500000',
                'emi' => [
                    'duration' => '9',
                    'bank' => 'HDFC',
                ],
                'card' => [
                    'number'       => '41476700000006',
                    'name'         => 'Harshil',
                    'expiry_month' => '12',
                    'expiry_year'  => '2017',
                    'cvv'          => '566',
                    'network'      => 'Visa',
                    'issuer'       => 'HDFC',
                ],
                'bank' => 'HDFC',
            ],
            'fixtures' => [
                 [
                    'method'         => 'emi',
                    'merchant_id'    => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'        => 'hdfc',
                    'type'           => 'filter',
                    'filter_type'    => 'select',
                    'group'          => 'method_filter',
                    'emi_duration'   => 9,
                    'emi_subvention' => 'merchant',
                    'issuer'         => 'HDFC',
                ],
                [
                    'method'       => 'emi',
                    'merchant_id'  => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'      => 'hdfc',
                    'type'         => 'filter',
                    'filter_type'  => 'select',
                    'group'        => 'method_filter',
                ],
            ],
            'expected_terminal_ids' => [
                'ShrdEmiMrSubTr',
            ]
    ],

    'testNetworkFilter' => [
        'payment_options' => [
            'method' => Method::CARD,
            'card' => [
                'network' => 'RuPay'
            ]
        ],
        'fixtures' => [
            [
                'method'      => Method::CARD,
                'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                'gateway'     => 'hdfc',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                'gateway'     => 'axis_migs',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                'gateway'     => 'hdfc',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'network_filter',
                'network'     => 'RUPAY',
            ],
        ],
        'expected_terminal_ids' => [
            '1000HdfcShared',
        ]
    ],

    'testInternationalFilter' => [
        [
            'payment_options' => [
                'method' => Method::CARD,
                'card' => [
                    'international' => true
                ]
            ],
            'fixtures' => [
                [
                    'method'      => Method::CARD,
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'        => Method::CARD,
                    'merchant_id'   => Merchant\Account::SHARED_ACCOUNT,
                    'type'          => 'filter',
                    'filter_type'   => 'select',
                    'group'         => 'international_filter',
                    'international' => '1',
                ],
            ],
            'expected_terminal_ids' => [
                '1000FrstDataTl',
            ]
        ],
        [
            'payment_options' => [
                'method' => Method::CARD,
            ],
            'fixtures' => [
                [
                    'method'      => Method::CARD,
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'        => Method::CARD,
                    'merchant_id'   => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'       => 'first_data',
                    'type'          => 'filter',
                    'filter_type'   => 'select',
                    'group'         => 'international_filter',
                    'international' => '1',
                ],
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
            ]
        ]
    ],

    'testCurrencyFilter' => [
        'payment_options' => [
            'method' => Method::CARD,
            'currency' => 'USD',
        ],
        'fixtures' => [
            [
                'method'      => Method::CARD,
                'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                'gateway'     => 'hdfc',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                'gateway'     => 'first_data',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                'gateway'     => 'first_data',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'currency_filter',
                'currency'    => 'USD'
            ],
        ],
        'expected_terminal_ids' => [
            '1000FrstDataTl'
        ]
    ],

    'testAmountFilter' => [
        [
            'payment_options' => [
                'method' => Method::CARD,
                'amount' => '300000'
            ],
            'fixtures' => [
                [
                    'method'      => Method::CARD,
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'      => Method::CARD,
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'        => Method::CARD,
                    'merchant_id'   => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'       => 'axis_migs',
                    'type'          => 'filter',
                    'filter_type'   => 'reject',
                    'group'         => 'amount_filter',
                    'min_amount'    => '200000',
                ],
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
            ]
        ],
        [
            'payment_options' => [
                'method' => Method::CARD,
                'amount' => '300000'
            ],
            'fixtures' => [
                [
                    'method'      => Method::CARD,
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'      => Method::CARD,
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'        => Method::CARD,
                    'merchant_id'   => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'       => 'axis_migs',
                    'type'          => 'filter',
                    'filter_type'   => 'reject',
                    'group'         => 'amount_filter',
                    'max_amount'    => '400000',
                ],
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
            ]
        ],
        [
            'payment_options' => [
                'method' => Method::CARD,
                'amount' => '300000'
            ],
            'fixtures' => [
                [
                    'method'      => Method::CARD,
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'      => Method::CARD,
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'        => Method::CARD,
                    'merchant_id'   => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'       => 'axis_migs',
                    'type'          => 'filter',
                    'filter_type'   => 'reject',
                    'group'         => 'amount_filter',
                    'min_amount'    => '200000',
                    'max_amount'    => '500000',
                ],
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
            ]
        ]
    ],

    'testIinFilter' => [
        'payment_options' => [
            'method' => Method::CARD,
        ],
        'fixtures' => [
            [
                'method'      => Method::CARD,
                'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                'gateway'     => 'hdfc',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                'gateway'     => 'axis_migs',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'        => Method::CARD,
                'merchant_id'   => Merchant\Account::SHARED_ACCOUNT,
                'gateway'       => 'axis_migs',
                'type'          => 'filter',
                'filter_type'   => 'reject',
                'group'         => 'iin_filter',
                'iins'          => ['401200'],
            ],
        ],
        'expected_terminal_ids' => [
            '1000HdfcShared',
        ]
    ],

    'testMerchantCategory2Filter' => [
        'payment_options' => [
            'method' => Method::CARD,
        ],
        'fixtures' => [
            [
                'method'      => Method::CARD,
                'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                'gateway'     => 'hdfc',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                'gateway'     => 'axis_migs',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'        => Method::CARD,
                'merchant_id'   => Merchant\Account::SHARED_ACCOUNT,
                'gateway'       => 'axis_migs',
                'type'          => 'filter',
                'filter_type'   => 'reject',
                'group'         => 'category2_filter',
                'category2'     => 'securities'
            ],
        ],
        'expected_terminal_ids' => [
            '1000HdfcShared',
        ]
    ],

    'testNetworkCategoryFilter' => [
        'payment_options' => [
            'method' => Method::NETBANKING,
            'bank' => "KKBK",
        ],
        'fixtures' => [
            [
                'method'      => Method::NETBANKING,
                'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                'gateway'     => 'netbanking_kotak',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'           => Method::NETBANKING,
                'merchant_id'      => Merchant\Account::SHARED_ACCOUNT,
                'gateway'          => 'netbanking_kotak',
                'type'             => 'filter',
                'filter_type'      => 'select',
                'group'            => 'category_filter',
                'category2'        => 'corporate',
                'network_category' => 'corporate',
            ],
        ],
        'expected_terminal_ids' => [
            'SCorNbKtkTrmnl'
        ]
    ],

    'testDirectTerminalFilter' => [
        'payment_options' => [
            'method' => Method::NETBANKING,
            'bank' => "KKBK",
        ],
        'fixtures' => [
            [
                'method'      => Method::NETBANKING,
                'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                'gateway'     => 'netbanking_kotak',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'          => Method::NETBANKING,
                'merchant_id'     => Merchant\Account::SHARED_ACCOUNT,
                'gateway'         => 'netbanking_kotak',
                'type'            => 'filter',
                'filter_type'     => 'select',
                'group'           => 'direct_filter',
                'shared_terminal' => 0,
            ],
        ],
        'expected_terminal_ids' => [
            'DrctNbKtkTrmnl'
        ]
    ],

    'testMerchantSpecificFilterRules' => [
        'payment_options' => [
            'method' => Method::CARD,
        ],
        'fixtures' => [
            [
                'method'      => Method::CARD,
                'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                'gateway'     => 'hdfc',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => Merchant\Account::TEST_ACCOUNT,
                'gateway'     => 'axis_migs',
                'type'        => 'filter',
                'filter_type' => 'reject',
                'group'       => 'method_filter',
            ],
        ],
        'expected_terminal_ids' => [
            '1000HdfcShared',
        ]
    ],

    'testFeatureBasedMigrationPlan' => [
        [
            'payment_options' => [
                'method' => Method::CARD,
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
            ]
        ],
        [
            'payment_options' => [
                'method' => Method::CARD,
            ],
            'fixtures' => [
                [
                    'method'      => Method::CARD,
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
            ],
            'expected_terminal_ids' => [
                '1000HdfcShared',
            ]
        ]
    ]
];
