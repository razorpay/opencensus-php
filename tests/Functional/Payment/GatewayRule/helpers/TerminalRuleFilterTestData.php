<?php

use RZP\Models\Emi;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account as MerchantAccount;
use RZP\Models\Payment\Method;
use RZP\Models\Admin\Org\Entity as Org;

return [
    'testRuleFilterCombinations' => [
        [
            'payment_options' => [
                'method' => Method::CARD
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'cybersource',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                    'network'     => 'VISA'
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                    'network'     => 'VISA'
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                    'network'     => 'VISA'
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'A',
                    'network'     => 'VISA'
                ],
                [
                    'method'      => 'card',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                'bank'   => 'SBIN',
            ],
            'fixtures' => [
                [
                    'method'      => 'netbanking',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'billdesk',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                    'issuer'      => 'SBIN',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'wallet_olamoney',
                    'issuer'      => 'olamoney',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'      => 'wallet',
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'subvention' => Emi\Subvention::CUSTOMER,
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
                    'merchant_id'    => MerchantAccount::SHARED_ACCOUNT,
                    'step'           => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
        [
            'payment_options' => [
                'method' => Method::EMI,
                'amount' => '300000',
                'emi' => [
                    'duration' => '9',
                    'bank' => 'SBIN',
                ],
                'card' => [
                    'number'       => '4726426811111117',
                    'name'         => 'Harshil',
                    'expiry_month' => '12',
                    'expiry_year'  => '2017',
                    'cvv'          => '566',
                    'network'      => 'Visa',
                    'issuer'       => 'SBIN',
                ],
                'bank' => 'SBIN',
            ],
            'fixtures' => [
                [
                    'method'         => 'emi',
                    'merchant_id'    => MerchantAccount::SHARED_ACCOUNT,
                    'step'           => 'authorization',
                    'gateway'        => 'hitachi',
                    'type'           => 'filter',
                    'filter_type'    => 'select',
                    'group'          => 'routing_filter',
                    'issuer'         => 'SBIN',
                ],
            ],
            'expected_terminal_ids' => [
                '100HitachiTmnl',
            ]
        ]
    ],

    'testMethodFilterWithMerchantEmiSubvention' => [
        'payment_options' => [
                'method' => Method::EMI,
                'amount' => '500000',
                'emi' => [
                    'duration' => '9',
                    'bank' => 'HDFC',
                    'subvention' => Emi\Subvention::MERCHANT,
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
                    'merchant_id'    => MerchantAccount::SHARED_ACCOUNT,
                    'step'           => 'authorization',
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
                    'merchant_id'  => MerchantAccount::SHARED_ACCOUNT,
                    'step'         => 'authorization',
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
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'hdfc',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'axis_migs',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
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

    'testSharedTerminalFilter'  => [
        'payment_options' => [
            'method' => Method::CARD,
        ],
        'fixtures' => [
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'        => Method::CARD,
                'merchant_id'   => MerchantAccount::SHARED_ACCOUNT,
                'step'          => 'authorization',
                'type'          => 'filter',
                'filter_type'   => 'select',
                'group'         => 'shared_terminal_filter',
            ],
        ],
        'expected_terminal_ids' => [
            '1000HdfcDirect',
            '1000AxisDirect',
            '1000SharpTrmnl'
        ]
    ],

    'testUpiFilter'  => [
        'payment_options' => [
            'method' => Method::UPI,
            'vpa' => 'vishnu@icici',
        ],
        'fixtures' => [
            [
                'method'      => Method::UPI,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'upi_mindgate',
                'type'        => 'filter',
                'filter_type' => 'reject',
                'group'       => 'method_filter',
            ],
        ],
        'expected_terminal_ids' => [
            '100UPIICICITml',
        ]
    ],

    'testRecurringRuleInitial'  => [
        'payment_options' => [
            'method'         => Method::CARD,
            'recurring'      => 1,
            'recurring_type' => 'initial',
        ],
        'fixtures' => [
            [
                'method'         => Method::CARD,
                'merchant_id'    => MerchantAccount::SHARED_ACCOUNT,
                'step'           => 'authorization',
                'gateway'        => 'hdfc',
                'type'           => 'filter',
                'filter_type'    => 'select',
                'issuer'         => 'HDFC',
                'recurring'      => 1,
                'recurring_type' => 'initial',
                'group'          => 'A',
            ],
        ],
        'expected_terminal_ids' => [
            'FssRecurringTl',
        ]
    ],

    'testRecurringRuleAuto'  => [
        'payment_options' => [
            'method'         => Method::CARD,
            'recurring'      => 1,
            'recurring_type' => 'auto',
        ],
        'fixtures' => [
            [
                'method'         => Method::CARD,
                'merchant_id'    => MerchantAccount::SHARED_ACCOUNT,
                'step'           => 'authorization',
                'gateway'        => 'hdfc',
                'type'           => 'filter',
                'filter_type'    => 'select',
                'issuer'         => 'HDFC',
                'recurring'      => 1,
                'recurring_type' => 'auto',
                'group'          => 'A',
            ],
        ],
        'expected_terminal_ids' => [
            'FssRecurringTl',
        ]
    ],

    'testInternationalFilter' => [
        [
            'payment_options' => [
                'method' => Method::CARD,
                'international' => true,
            ],
            'fixtures' => [
                [
                    'method'      => Method::CARD,
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'        => Method::CARD,
                    'merchant_id'   => MerchantAccount::SHARED_ACCOUNT,
                    'step'          => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'        => Method::CARD,
                    'merchant_id'   => MerchantAccount::SHARED_ACCOUNT,
                    'step'          => 'authorization',
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

    'testDomesticPaymentFilter' => [
        'payment_options' => [
            'method' => Method::CARD,
            'international' => false,
        ],
        'fixtures' => [
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'        => Method::CARD,
                'merchant_id'   => MerchantAccount::SHARED_ACCOUNT,
                'step'          => 'authorization',
                'type'          => 'filter',
                'filter_type'   => 'select',
                'group'         => 'domestic_filter',
                'international' => '0',
                'currency'      => 'INR',
            ],
        ],
        'expected_terminal_ids' => [
            '1000HdfcShared',
            '1000FrstDataTl'
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
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                'gateway'     => 'hdfc',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'first_data',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'      => Method::CARD,
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'        => Method::CARD,
                    'merchant_id'   => MerchantAccount::SHARED_ACCOUNT,
                    'step'          => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'      => Method::CARD,
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'        => Method::CARD,
                    'merchant_id'   => MerchantAccount::SHARED_ACCOUNT,
                    'step'          => 'authorization',
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'hdfc',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'      => Method::CARD,
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
                    'gateway'     => 'axis_migs',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'method_filter',
                ],
                [
                    'method'        => Method::CARD,
                    'merchant_id'   => MerchantAccount::SHARED_ACCOUNT,
                    'step'          => 'authorization',
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
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'hdfc',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'axis_migs',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'        => Method::CARD,
                'merchant_id'   => MerchantAccount::SHARED_ACCOUNT,
                'step'          => 'authorization',
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

    'testMerchantCategoryFilterReject' => [
        'payment_options' => [
            'method' => Method::CARD,
        ],
        'fixtures' => [
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'hdfc',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'axis_migs',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'        => Method::CARD,
                'merchant_id'   => MerchantAccount::SHARED_ACCOUNT,
                'step'          => 'authorization',
                'gateway'       => 'axis_migs',
                'type'          => 'filter',
                'filter_type'   => 'reject',
                'group'         => 'category_filter',
                'category'     =>  '1234' // assume 1234 is a blacklisted MCC for axis
            ],
        ],
        'expected_terminal_ids' => [
            '1000HdfcShared',
        ]
    ],

    'testMerchantCategoryFilterDontReject' => [
        'payment_options' => [
            'method' => Method::CARD,
        ],
        'fixtures' => [
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'hdfc',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'axis_migs',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'        => Method::CARD,
                'merchant_id'   => MerchantAccount::SHARED_ACCOUNT,
                'step'          => 'authorization',
                'gateway'       => 'axis_migs',
                'type'          => 'filter',
                'filter_type'   => 'reject',
                'group'         => 'category_filter',
                'category'     =>  '1234' // assume 1234 is a blacklisted MCC for axis
            ],
        ],
        'expected_terminal_ids' => [
            '1000HdfcShared',
            '1000AxisMigsTl',
        ]
    ],

    'testMerchantCategory2Filter' => [
        'payment_options' => [
            'method' => Method::CARD,
        ],
        'fixtures' => [
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'hdfc',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'axis_migs',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'        => Method::CARD,
                'merchant_id'   => MerchantAccount::SHARED_ACCOUNT,
                'step'          => 'authorization',
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
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'netbanking_kotak',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'           => Method::NETBANKING,
                'merchant_id'      => MerchantAccount::SHARED_ACCOUNT,
                'step'             => 'authorization',
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
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'netbanking_kotak',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'          => Method::NETBANKING,
                'merchant_id'     => MerchantAccount::SHARED_ACCOUNT,
                'step'            => 'authorization',
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

    'testOrgDirectTerminalFilter' => [
        'payment_options' => [
            'method' => Method::NETBANKING,
            'bank' => 'KKBK',
        ],
        'fixtures' => [
            [
                'method'      => Method::NETBANKING,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'netbanking_kotak',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'           => Method::NETBANKING,
                'org_id'           => Org::RAZORPAY_ORG_ID,
                'step'             => 'authorization',
                'gateway'          => 'netbanking_kotak',
                'type'             => 'filter',
                'filter_type'      => 'select',
                'group'            => 'direct_filter',
                'shared_terminal'  => 0,
            ],
        ],
        'expected_terminal_ids' => [
            'DrctNbKtkTrmnl'
        ]
    ],

    'testOrgDirectTerminalFilterWithDifferentOrg' => [
        'payment_options' => [
            'method' => Method::NETBANKING,
            'bank' => 'KKBK',
        ],
        'fixtures' => [
            [
                'method'      => Method::NETBANKING,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'netbanking_kotak',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'           => Method::NETBANKING,
                'org_id'           => Org::HDFC_ORG_ID,
                'step'             => 'authorization',
                'gateway'          => 'netbanking_kotak',
                'type'             => 'filter',
                'filter_type'      => 'select',
                'group'            => 'direct_filter',
                'shared_terminal'  => 0,
            ],
        ],
        'expected_terminal_ids' => [
            'DrctNbKtkTrmnl',
            'SCorNbKtkTrmnl',
        ]
    ],

    'testMerchantSpecificFilterRules' => [
        'payment_options' => [
            'method' => Method::CARD,
        ],
        'fixtures' => [
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                'step'        => 'authorization',
                'gateway'     => 'hdfc',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'method_filter',
            ],
            [
                'method'      => Method::CARD,
                'merchant_id' => MerchantAccount::TEST_ACCOUNT,
                'step'        => 'authorization',
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

    'testTerminalSortingWithSorterRuleForDirectTerminal' => [
        'payment_options' => [
            'method' => Method::CARD,
        ],
        'fixtures' => [
            [
                'method'          => Method::CARD,
                'merchant_id'     => MerchantAccount::SHARED_ACCOUNT,
                'step'            => 'authorization',
                'gateway'         => 'hdfc',
                'type'            => 'sorter',
                'load'            => 100,
                'shared_terminal' => 0,
            ],
        ],
        'expected_terminal_ids' => [
            '1000HdfcDirect',
            '1000AxisDirect',
        ],
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
                    'merchant_id' => MerchantAccount::SHARED_ACCOUNT,
                    'step'        => 'authorization',
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
