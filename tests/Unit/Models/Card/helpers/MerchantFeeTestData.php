<?php

use RZP\Models\Card;

return [
    'testFeeWithMaxFeeForCard' => [
        [
            'amount' => '1000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 20,
            'tax' => 0,
            'fee_components' => [
                'payment' => 20,
                'tax' => 0,
            ],
        ],
        [
            'amount' => '300000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 1180,
            'tax' => 180,
            'fee_components' => [
                'payment' => 1000,
                'tax' => 180,
            ]
        ]
    ],

    'testInterstateGstForCard' => [
        [
            'amount' => '1000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 20,
            'tax' => 0,
            'fee_components' => [
                'payment' => 20,
                'tax' => 0,
            ],
        ],
        [
            'amount' => '225100',
            'card_type' => Card\Type::CREDIT,
            'fee' => 1180,
            'tax' => 180,
            'fee_components' => [
                'payment' => 1000,
                'tax' => 180,
            ]
        ]
    ],

    'testOptimizerConvenienceFeeCalculation' => [
        [
            'amount' => '1000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 50,
            'tax' => 0,
            'gateway' => 'payu',
            'fee_components' => [
                'payment' => 20,
                'optimizer_convenience_fee' => 30,
                'tax' => 0,
            ],
        ],
        [
            'amount' => '225100',
            'card_type' => Card\Type::CREDIT,
            'fee' => 3837,
            'tax' => 586,
            'gateway' =>  null,
            'fee_components' => [
                'payment' => 1000,
                'tax' => 586,
                'optimizer_convenience_fee' => 2251,
            ]
        ],
        [
            'amount' => '1000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 30,
            'tax' => 0,
            'gateway' =>  'cashfree',
            'fee_components' => [
                'payment' => 20,
                'tax' => 0,
                'optimizer_convenience_fee' => 10,
            ]
        ]
    ],

    'testOptimizerConvenienceFeeCalculationWithoutDefaultPlan' => [
        [
            'amount' => '1000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 50,
            'tax' => 0,
            'gateway' => 'payu',
            'fee_components' => [
                'payment' => 20,
                'optimizer_convenience_fee' => 30,
                'tax' => 0,
            ],
        ],
        [
            'amount' => '225100',
            'card_type' => Card\Type::CREDIT,
            'fee' => 1180,
            'tax' => 180,
            'gateway' =>  null,
            'fee_components' => [
                'payment' => 1000,
                'tax' => 180,
                'optimizer_convenience_fee' => 0,
            ]
        ],
        [
            'amount' => '1000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 20,
            'tax' => 0,
            'gateway' =>  'cashfree',
            'fee_components' => [
                'payment' => 20,
                'tax' => 0,
                'optimizer_convenience_fee' => 0,
            ]
        ]
    ],

    'testInterstateGstForCardWithPercentScaleFactor' => [
        [
            'amount' => '1000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 21,
            'tax' => 0,
            'fee_components' => [
                'payment' => 21,
                'tax' => 0,
            ],
        ],
        [
            'amount' => '225100',
            'card_type' => Card\Type::CREDIT,
            'fee' => 1180,
            'tax' => 180,
            'fee_components' => [
                'payment' => 1000,
                'tax' => 180,
            ]
        ]
    ],

    'testInterstateGstForCardWithPercentScaleFactorAndFeeRound' => [
        [
            'amount' => '1000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 20,
            'tax' => 0,
            'fee_components' => [
                'payment' => 20,
                'tax' => 0,
            ],
            'splitz_variant' => 'on',
        ],
        [
            'amount' => '1000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 21,
            'tax' => 0,
            'fee_components' => [
                'payment' => 20,
                'tax' => 0,
            ],
            'splitz_variant' => 'control',
        ]
    ],

    'testFeeWithMaxFeeForWallet' => [
        [
            'amount' => 60000,
            'fee' => 2124,
            'tax' => 324,
            'fee_components' => [
                'payment' => 1800,
                'tax' => 324,
            ]
        ],
        // For amounts greater that 66667 fee will remain constant
        // since max_fee is 2000 and the percent_rate is 300 i.e.
        // max_amount = 2000 * 100/3 = 66667
        [
            'amount' => 66667,
            'fee' => 2360,
            'tax' => 360,
            'fee_components' => [
                'payment' => 2000,
                'tax' => 360,
            ]
        ],
        [
            'amount' => 70000,
            'fee' => 2360,
            'tax' => 360,
            'fee_components' => [
                'payment' => 2000,
                'tax' => 360,
            ]
        ],
        [
            'amount' => 80000,
            'fee' => 2360,
            'tax' => 360,
            'fee_components' => [
                'payment' => 2000,
                'tax' => 360,
            ]
        ],
    ],
];
