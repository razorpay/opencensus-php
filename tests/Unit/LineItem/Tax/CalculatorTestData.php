<?php

namespace RZP\Tests\Unit\LineItem\Tax;

/**
 * Returns data in format: Input line item and tax entity data
 * and the expected tax amount the formers.
 *
 */

return [
    [
        'line_item' => [
            'attributes' => [
                'name'          => 'Line item #1',
                'amount'        => 10000,
                'currency'      => 'INR',
                'quantity'      => 1,
                'tax_inclusive' => false,
            ],
            'taxable_amount'    => 10000,
        ],
        'taxes' => [
            [
                'attributes' => [
                    'name'      => 'Tax #1',
                    'rate_type' => 'percentage',
                    'rate'      => 100000,
                ],
                'tax_amount'    => 1000,
            ],
        ],
    ],

    [
        'line_item' => [
            'attributes' => [
                'name'          => 'Line item #1',
                'amount'        => 10000,
                'currency'      => 'INR',
                'quantity'      => 5,
                'tax_inclusive' => false,
            ],
            'taxable_amount'    => 50000,
        ],
        'taxes' => [
            [
                'attributes' => [
                    'name'      => 'Tax #1',
                    'rate_type' => 'percentage',
                    'rate'      => 100000,
                ],
                'tax_amount'    => 5000,
            ],
        ],
    ],

    [
        'line_item' => [
            'attributes' => [
                'name'          => 'Line item #1',
                'amount'        => 10000,
                'currency'      => 'INR',
                'quantity'      => 5,
                'tax_inclusive' => false,
            ],
            'taxable_amount'    => 50000,
        ],
        'taxes' => [
            [
                'attributes' => [
                    'name'      => 'Tax #1',
                    'rate'      => 10,
                    'rate_type' => 'flat',
                ],
                'tax_amount'    => 50,
            ],
        ],
    ],

    [
        'line_item' => [
            'attributes' => [
                'name'          => 'Line item #1',
                'amount'        => 10000,
                'currency'      => 'INR',
                'quantity'      => 1,
                'tax_inclusive' => true,
            ],
            'taxable_amount'    => 9091,
        ],
        'taxes' => [
            [
                'attributes' => [
                    'name'      => 'Tax #1',
                    'rate_type' => 'percentage',
                    'rate'      => 100000,
                ],
                'tax_amount'    => 909.1,
            ],
        ],
    ],

    [
        'line_item' => [
            'attributes' => [
                'name'          => 'Line item #1',
                'amount'        => 10000,
                'currency'      => 'INR',
                'quantity'      => 5,
                'tax_inclusive' => true,
            ],
            'taxable_amount'    => 49950,
        ],
        'taxes' => [
            [
                'attributes' => [
                    'name'      => 'Tax #1',
                    'rate'      => 10,
                    'rate_type' => 'flat',
                ],
                'tax_amount'    => 50,
            ],
        ],
    ],

    [
        'line_item' => [
            'attributes' => [
                'name'          => 'Line item #1',
                'amount'        => 10000,
                'currency'      => 'INR',
                'quantity'      => 5,
                'tax_inclusive' => true,
            ],
            'taxable_amount'    => 35370,
        ],
        'taxes' => [
            [
                'attributes' => [
                    'name'      => 'Tax #2',
                    'rate'      => 150000,
                    'rate_type' => 'percentage',
                ],
                'tax_amount'    => 5305.5,
            ],
            [
                'attributes' => [
                    'name'      => 'Tax #2',
                    'rate'      => 200000,
                    'rate_type' => 'percentage',
                ],
                'tax_amount'    => 7074,
            ],
            [
                'attributes' => [
                    'name'      => 'Tax #3',
                    'rate'      => 450,
                    'rate_type' => 'flat',
                ],
                'tax_amount'    => 2250,
            ],
        ],
    ],
];
