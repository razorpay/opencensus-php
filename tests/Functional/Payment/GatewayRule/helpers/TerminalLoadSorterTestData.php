<?php

use RZP\Models\Merchant;

return [
    'testTerminalSelectionWithRule' => [
        // rule satisfies chance value
        [
            'method' => 'card',
            'rules' => [
                [
                    'type'    => 'sorter',
                    'gateway' => 'axis_migs',
                    'network' => 'VISA',
                    'load'    => 70,
                ],
            ],
            'test_chance' => 5000,
            'expected_terminal' => '1000AxisMigsTl',
        ],
        // rule does not satisfy payment criteria
        [
            'method' => 'card',
            'rules' => [
                [
                    'type'    => 'sorter',
                    'gateway' => 'axis_migs',
                    'network' => 'MC',
                    'load'    => 70,
                ],
            ],
            'test_chance' => 5000,
            'expected_terminal' => '1000HdfcShared',
        ],
        // rule does not satisfy chance value
        [
            'method' => 'card',
            'rules' => [
                [
                    'type'    => 'sorter',
                    'gateway' => 'axis_migs',
                    'network' => 'VISA',
                    'load'    => 70,
                ],
            ],
            'test_chance' => 9000,
            'expected_terminal' => '1000HdfcShared',
        ],
        // multiple rules present
        [
            'method' => 'card',
            'rules' => [
                [
                    'type'    => 'sorter',
                    'gateway' => 'axis_migs',
                    'network' => 'VISA',
                    'load'    => 70,
                ],
                [
                    'type'    => 'sorter',
                    'gateway' => 'hdfc',
                    'network' => 'VISA',
                    'load'    => 20,
                ],
            ],
            'test_chance' => 4000,
            'expected_terminal' => '1000AxisMigsTl',
        ],
        // rule specifying all possible details present
        [
            'method' => 'card',
            'rules' => [
                [
                    'type'             => 'sorter',
                    'gateway'          => 'cybersource',
                    'method_type'      => 'credit',
                    'network'          => 'VISA',
                    'issuer'           => 'HDFC',
                    'gateway_acquirer' => 'hdfc',
                    'load'             => 70
                ]
            ],
            'test_chance' => 4000,
            'expected_terminal' => '1000CybrsTrmnl'
        ],
        // rule present on shared account, but no rule present on merchant account
        [
            'method' => 'card',
            'rules' => [
                [
                    'type'        => 'sorter',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'network'     => 'VISA',
                    'load'        => 70
                ]
            ],
            'test_chance' => 5000,
            'expected_terminal' => '1000AxisMigsTl',
        ],
        // Rule present on both shared account and merchant account. MDirect rule
        // to be given precedence
        [
            'method' => 'card',
            'rules' => [
                [
                    'type'        => 'sorter',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'network'     => 'VISA',
                    'load'        => 60
                ],
                [
                    'type'        => 'sorter',
                    'merchant_id' => Merchant\Account::TEST_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'network'     => 'VISA',
                    'load'        => 70
                ]
            ],
            'test_chance' => 5000,
            'expected_terminal' => '1000HdfcShared',
        ],
        // Rules with different specificty levels present, but chance percent favours more generic rule
        [
            'method' => 'card',
            'rules' => [
                [
                    'type'        => 'sorter',
                    'merchant_id' => Merchant\Account::TEST_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'network'     => 'VISA',
                    'load'        => 70
                ],
                [
                    'type'        => 'sorter',
                    'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
                    'gateway'     => 'hdfc',
                    'load'        => 90
                ]
            ],
            'test_chance' => 8000,
            'expected_terminal' => '1000HdfcShared',
        ],
        [
            'method' => 'card',
            'rules' => [
                [
                    'type'        => 'sorter',
                    'merchant_id' => Merchant\Account::TEST_ACCOUNT,
                    'gateway'     => 'axis_migs',
                    'network'     => 'VISA',
                    'load'        => 60
                ],
                [
                    'type'        => 'sorter',
                    'merchant_id' => Merchant\Account::TEST_ACCOUNT,
                    'issuer'      => 'HDFC',
                    'gateway'     => 'hdfc',
                    'load'        => 70
                ],
                [
                    'type'        => 'sorter',
                    'merchant_id' => Merchant\Account::TEST_ACCOUNT,
                    'iins'        => ['401200'],
                    'gateway'     => 'first_data',
                    'load'        => 60
                ]
            ],
            'test_chance' => 5000,
            'expected_terminal' => '1000FrstDataTl',
        ],
        // netbanking rule gives precedence to shared netbanking gateway over direct integration
        [
            'method' => 'netbanking',
            'rules' => [
                [
                    'type'    => 'sorter',
                    'gateway' => 'paytm',
                    'issuer'  => 'HDFC',
                    'load'    => 70
                ]
            ],
            'test_chance' => 4000,
            'expected_terminal' => '1000PaytmTrmnl',
        ],
        // wallet payment with rule
        [
            'method' => 'wallet',
            'rules' => [
                [
                    'type'    => 'sorter',
                    'gateway' => 'wallet_mobikwik',
                    'issuer'  => 'mobikwik',
                    'load'    => 70,
                ]
            ],
            'test_chance' => 4000,
            'expected_terminal' => '1000MobiKwikTl',
        ],
    ],

    'testWithMultipleRulesButNomatchingTerminal' => [
        'method' => 'card',
        'rules' => [
            [
                'type'    => 'sorter',
                'gateway' => 'hdfc',
                'network' => 'VISA',
                'load'    => 30
            ],
            [
                'type'    => 'sorter',
                'gateway' => 'axis_migs',
                'network' => 'VISA',
                'load'    => 60
            ],
            [
                'type'    => 'sorter',
                'gateway' => 'cybersource',
                'network' => 'VISA',
                'load'    => 10
            ]
        ],
        'test_chance' => 5000,
        'expected_terminal' => '1000HdfcShared',
    ],

    'testCapabilityTerminalsSelection' => [
        [
            'method' => 'card',
            'rules' => [
                [
                    'type'       => 'sorter',
                    'gateway'    => 'axis_migs',
                    'network'    => 'VISA',
                    'load'       => 10,
                    'capability' => 2
                ],
                [
                    'type'       => 'sorter',
                    'gateway'    => 'axis_migs',
                    'network'    => 'VISA',
                    'load'       => 90
                ]
            ],
            'test_chance' => 900,
            'expected_terminal' => '1001AxisTrmnal',
        ],
        [
            'method' => 'card',
            'rules' => [
                [
                    'type'       => 'sorter',
                    'gateway'    => 'axis_migs',
                    'network'    => 'VISA',
                    'load'       => 10,
                    'capability' => 2
                ],
                [
                    'type'       => 'sorter',
                    'gateway'    => 'axis_migs',
                    'network'    => 'VISA',
                    'load'       => 90
                ]
            ],
            'test_chance' => 8000,
            'expected_terminal' => '1000AxisMigsTl',
        ],
        [
            'method' => 'card',
            'rules' => [
                [
                    'type'       => 'sorter',
                    'gateway'    => 'axis_migs',
                    'network'    => 'VISA',
                    'load'       => 10,
                ]
            ],
            'test_chance' => 2000,
            'expected_terminal' => '1000AxisMigsTl',
        ],
    ],

    'testInternationalAndDomesticPaymentsWithRules' => [
        // domestic payment with rule applicable for both domestic / international
        [
            'method' => 'card',
            'international' => false,
            'rules' => [
                [
                    'type'    => 'sorter',
                    'gateway' => 'hdfc',
                    'network' => 'VISA',
                    'load'    => 7000
                ]
            ],
            'test_chance' => 5000,
            'expected_terminal' => '1000HdfcShared',
        ],
        // international payment with rule applicable for both domestic / international
        [
            'method' => 'card',
            'international' => true,
            'rules' => [
                [
                    'type'    => 'sorter',
                    'gateway' => 'hdfc',
                    'network' => 'VISA',
                    'load'    => 7000
                ]
            ],
            'test_chance' => 5000,
            'expected_terminal' => '1000HdfcShared',
        ],
        // international payment with rule applicable only for international
        [
            'method' => 'card',
            'international' => true,
            'rules' => [
                [
                    'type'          => 'sorter',
                    'gateway'       => 'hdfc',
                    'network'       => 'VISA',
                    'international' => true,
                    'load'          => 7000
                ]
            ],
            'test_chance' => 5000,
            'expected_terminal' => '1000HdfcShared',
        ]
    ]
];
