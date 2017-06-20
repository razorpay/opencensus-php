<?php

use RZP\Models\Card;

return [
    'testFeeWithMaxFeeForCard' => [
        [
            'amount' => '1000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 20,
            'service_tax' => 0,
            'fee_components' => [
                'payment' => 20,
                'krishi_kalyan_cess' => 0,
                'swachh_bharat_cess' => 0,
                'service_tax' => 0
            ],
        ],
        [
            'amount' => '100000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 1000,
            'service_tax' => 0,
            'fee_components' => [
                'payment' => 1000,
                'krishi_kalyan_cess' => 0,
                'swachh_bharat_cess' => 0,
                'service_tax' => 0,
            ]
        ]
    ],
    'testFeeWithMaxFeeForWallet' => [
            [
                'amount' => 60000,
                'fee' => 2070,
                'service_tax' => 270,
                'fee_components' => [
                    'payment' => 1800,
                    'krishi_kalyan_cess' => 9,
                    'swachh_bharat_cess' => 9,
                    'service_tax' => 252,
                ]
            ],
            // For amounts greater that 66667 fee will remain constant
            // since max_fee is 2000 and the percent_rate is 300 i.e.
            // max_amount = 2000 * 100/3 = 66667
            [
                'amount' => 66667,
                'fee' => 2300,
                'service_tax' => 300,
                'fee_components' => [
                    'payment' => 2000,
                    'krishi_kalyan_cess' => 10,
                    'swachh_bharat_cess' => 10,
                    'service_tax' => 280,
                ]
            ],
            [
                'amount' => 70000,
                'fee' => 2300,
                'service_tax' => 300,
                'fee_components' => [
                    'payment' => 2000,
                    'krishi_kalyan_cess' => 10,
                    'swachh_bharat_cess' => 10,
                    'service_tax' => 280,
                ]
            ],
            [
                'amount' => 80000,
                'fee' => 2300,
                'service_tax' => 300,
                'fee_components' => [
                    'payment' => 2000,
                    'krishi_kalyan_cess' => 10,
                    'swachh_bharat_cess' => 10,
                    'service_tax' => 280,
                ]
            ],
        ],
];
