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
