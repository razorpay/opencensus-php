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
            'name'          => 'Line item #1',
            'amount'        => 10000,
            'currency'      => 'INR',
            'quantity'      => 1,
            'tax_inclusive' => false,
        ],
        'tax' => [
            'name'      => 'Tax #1',
            'rate_type' => 'percentage',
            'rate'      => 1000,
        ],
        'expected_tax_amount' => 1000,
    ],

    [
        'line_item' => [
            'name'          => 'Line item #1',
            'amount'        => 10000,
            'currency'      => 'INR',
            'quantity'      => 5,
            'tax_inclusive' => false,
        ],
        'tax' => [
            'name'      => 'Tax #1',
            'rate_type' => 'percentage',
            'rate'      => 1000,
        ],
        'expected_tax_amount' => 5000,
    ],

    [
        'line_item' => [
            'name'          => 'Line item #1',
            'amount'        => 10000,
            'currency'      => 'INR',
            'quantity'      => 5,
            'tax_inclusive' => false,
        ],
        'tax' => [
            'name'      => 'Tax #1',
            'rate'      => 10,
            'rate_type' => 'flat',
        ],
        'expected_tax_amount' => 10,
    ],

    [
        'line_item' => [
            'name'          => 'Line item #1',
            'amount'        => 10000,
            'currency'      => 'INR',
            'quantity'      => 1,
            'tax_inclusive' => true,
        ],
        'tax' => [
            'name'      => 'Tax #1',
            'rate_type' => 'percentage',
            'rate'      => 1000,
        ],
        'expected_tax_amount' => 909,
    ],

    [
        'line_item' => [
            'name'          => 'Line item #1',
            'amount'        => 10000,
            'currency'      => 'INR',
            'quantity'      => 5,
            'tax_inclusive' => true,
        ],
        'tax' => [
            'name'      => 'Tax #1',
            'rate'      => 10,
            'rate_type' => 'flat',
        ],
        'expected_tax_amount' => 10,
    ],
];
