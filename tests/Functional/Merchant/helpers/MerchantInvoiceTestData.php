<?php

return [

    'testInvoiceEntityCreateForPrevMonth' => [
        'non_card'      => [
            'amount'    => 1000,
            'tax'       => 180,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_gt_2k'    => [
            'amount'    => 4680,
            'tax'       => 842,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_lte_2k'    => [
            'amount'    => 1000,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
    ],

    'testInvoiceEntityCreateForGivenMonthYear' => [
        'non_card'      => [
            'amount'    => 1000,
            'tax'       => 180,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_gt_2k'    => [
            'amount'    => 4680,
            'tax'       => 842,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_lte_2k'    => [
            'amount'    => 1000,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
    ],

    'testFeeAdjustment' => [
        'merchant_id'   => '10000000000000',
        'gstin'         => '29kjsngjk213922',
        'type'          => 'adjustment',
        'amount'        => -1300,
        'tax'           => 0,
    ],

    'testInvoiceEntityCreateForGivenMerchant' => [
        'non_card'      => [
            'amount'    => 1000,
            'tax'       => 180,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_gt_2k'    => [
            'amount'    => 4680,
            'tax'       => 842,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_lte_2k'    => [
            'amount'    => 1000,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
    ],
];

