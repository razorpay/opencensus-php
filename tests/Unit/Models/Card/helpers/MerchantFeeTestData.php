<?php

use RZP\Models\Card;

return [
    'testFeeWithMaxFeeForCard' => [
        [
            'amount' => '1000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 24,
            'service_tax' => 4,
            'fee_components' => [
                'payment' => 20,
                'igst' => 4
            ],
        ],
        [
            'amount' => '100000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 1180,
            'service_tax' => 180,
            'fee_components' => [
                'payment' => 1000,
                'igst' => 180
            ]
        ]
    ],

    'testIntrastateGstForCard' => [
        [
            'amount' => '1000',
            'card_type' => Card\Type::CREDIT,
            'fee' => 24,
            'service_tax' => 4,
            'fee_components' => [
                'payment' => 20,
                'cgst' => 2,
                'sgst' => 2,
            ],
        ],
        [
            'amount' => '1250',
            'card_type' => Card\Type::CREDIT,
            'fee' => 29,
            'service_tax' => 4,
            'fee_components' => [
                'payment' => 25,
                'cgst' => 2,
                'sgst' => 2,
            ]
        ]
    ],

    'testFeeWithMaxFeeForWallet' => [
        [
            'amount' => 60000,
            'fee' => 2124,
            'service_tax' => 324,
            'fee_components' => [
                'payment' => 1800,
                'igst' => 324
            ]
        ],
        // For amounts greater that 66667 fee will remain constant
        // since max_fee is 2000 and the percent_rate is 300 i.e.
        // max_amount = 2000 * 100/3 = 66667
        [
            'amount' => 66667,
            'fee' => 2360,
            'service_tax' => 360,
            'fee_components' => [
                'payment' => 2000,
                'igst' => 360,
            ]
        ],
        [
            'amount' => 70000,
            'fee' => 2360,
            'service_tax' => 360,
            'fee_components' => [
                'payment' => 2000,
                'igst' => 360,
            ]
        ],
        [
            'amount' => 80000,
            'fee' => 2360,
            'service_tax' => 360,
            'fee_components' => [
                'payment' => 2000,
                'igst' => 360,
            ]
        ],
    ],
];
