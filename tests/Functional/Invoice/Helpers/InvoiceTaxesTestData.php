<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateInvoiceWithTaxes1' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'name'     => 'Item #1',
                        'amount'   => 100,
                        'quantity' => 5,
                        'tax_id'   => 'tax_00000000000001'
                    ],
                    [
                        'name'         => 'Item #2',
                        'amount'       => 150,
                        'quantity'     => 3,
                        'tax_group_id' => 'taxg_00000000000001'
                    ],
                ],
                'type'     => 'invoice',
                'draft'    => '0',
                'customer' => [
                    'email' => 'test@test.test'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer' => [
                    'name'    => null,
                    'email'   => 'test@test.test',
                    'contact' => null
                ],
                'line_items' => [
                    [
                        'name'          => 'Item #1',
                        'description'   => null,
                        'amount'        => 100,
                        'total_amount'  => 500,
                        'tax_amount'    => 45,
                        'net_amount'    => 545,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 5,
                        'taxes'         => [
                            [
                                'tax_id'     => 'tax_00000000000001',
                                'name'       => 'Tax #1',
                                'rate'       => 1000,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 45,
                            ],
                        ],
                    ],
                    [
                        'name'          => 'Item #2',
                        'description'   => null,
                        'amount'        => 150,
                        'total_amount'  => 450,
                        'tax_amount'    => 116,
                        'net_amount'    => 566,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 3,
                        'taxes'         => [
                            [
                                'tax_id'     => 'tax_00000000000001',
                                'name'       => 'Tax #1',
                                'rate'       => 1000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 41,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 2000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 75,
                            ],
                        ],
                    ],
                ],
                'amount'             => 950,
                'tax_amount'         => 161,
                'net_amount'         => 1111,
                'currency'           => 'INR',
                'description'        => null,
                'type'               => 'invoice',
                'show_taxes_grouped' => false,
            ],
        ],
    ],

    'testCreateInvoiceWithTaxes2' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'item_id'  => 'item_00000000000001',
                    ],
                    [
                        'item_id'  => 'item_00000000000002',
                    ],
                    [
                        'item_id'  => 'item_00000000000003',
                        'name'     => 'Updated item name',
                        'tax_id'   => 'tax_00000000000002',
                    ],
                    [
                        'name'         => 'Item #3',
                        'amount'       => 1000,
                        'tax_group_id' => 'taxg_00000000000001'
                    ],
                ],
                'type'     => 'invoice',
                'draft'    => '1',
                'customer' => [
                    'email' => 'test@test.test'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer' => [
                    'name'    => null,
                    'email'   => 'test@test.test',
                    'contact' => null
                ],
                'line_items' => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'total_amount'  => 100000,
                        'tax_amount'    => 0,
                        'net_amount'    => 100000,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 1,
                        'taxes'         => [],
                    ],
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'total_amount'  => 100000,
                        'tax_amount'    => 10000,
                        'net_amount'    => 110000,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 1,
                        'taxes' => [
                            [
                                'tax_id'     => 'tax_00000000000001',
                                'name'       => 'Tax #1',
                                'rate'       => 1000,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 10000,
                            ],
                        ],
                    ],
                    [
                        'name'          => 'Updated item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'total_amount'  => 100000,
                        'tax_amount'    => 10100,
                        'net_amount'    => 110100,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 1,
                        'taxes' => [
                            [
                                'tax_id'     => 'tax_00000000000001',
                                'name'       => 'Tax #1',
                                'rate'       => 1000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000002',
                                'group_name' => 'Tax Group #2',
                                'tax_amount' => 10000,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000004',
                                'name'       => 'Flat Tax #4',
                                'rate'       => 100,
                                'rate_type'  => 'flat',
                                'group_id'   => 'taxg_00000000000002',
                                'group_name' => 'Tax Group #2',
                                'tax_amount' => 100,
                            ],
                        ],
                    ],
                    [
                        'name'          => 'Item #3',
                        'description'   => null,
                        'amount'        => 1000,
                        'total_amount'  => 1000,
                        'tax_amount'    => 258,
                        'net_amount'    => 1258,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 1,
                        'taxes'         => [
                            [
                                'tax_id'     => 'tax_00000000000001',
                                'name'       => 'Tax #1',
                                'rate'       => 1000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 91,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 2000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 167,
                            ],
                        ],
                    ],
                ],
                'amount'             => 301000,
                'tax_amount'         => 20358,
                'net_amount'         => 321358,
                'currency'           => 'INR',
                'description'        => null,
                'type'               => 'invoice',
                'show_taxes_grouped' => false,
            ],
        ],
    ],

    'testUpdateInvoiceWithTaxes' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice',
            'method' => 'patch',
            'content' => [
                'line_items' => [
                    [
                        'id'           => 'li_100000lineitem',
                        'name'         => 'Updated name',
                        'tax_group_id' => 'taxg_00000000000001',
                    ],
                    [
                        'id'           => 'li_100001lineitem',
                        'tax_id'       => null,
                        'tax_group_id' => 'taxg_00000000000001',
                    ],
                    [
                        'id'           => 'li_100002lineitem',
                        'tax_id'       => null,
                        'tax_group_id' => null,
                    ],
                    [
                        'name'         => 'New item added #1',
                        'amount'       => 1300,
                        'tax_group_id' => 'taxg_00000000000001'
                    ],
                    [
                        'name'         => 'New item added #2',
                        'amount'       => 1300,
                        'quantity'     => 5,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer' => [
                    'name'    => null,
                    'email'   => 'test@test.test',
                    'contact' => null
                ],
                'line_items' => [
                    [
                        'name'          => 'Updated name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'total_amount'  => 100000,
                        'tax_amount'    => 30000,
                        'net_amount'    => 130000,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 1,
                        'taxes'         => [
                            [
                                'tax_id'     => 'tax_00000000000001',
                                'name'       => 'Tax #1',
                                'rate'       => 1000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 10000,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 2000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 20000,
                            ],
                        ],
                    ],
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'total_amount'  => 100000,
                        'tax_amount'    => 30000,
                        'net_amount'    => 130000,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 1,
                        'taxes'         => [
                            [
                                'tax_id'     => 'tax_00000000000001',
                                'name'       => 'Tax #1',
                                'rate'       => 1000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 10000,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 2000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 20000,
                            ],
                        ],
                    ],
                    [
                        'name'          => 'Updated item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'total_amount'  => 100000,
                        'tax_amount'    => 0,
                        'net_amount'    => 100000,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 1,
                        'taxes'         => [],
                    ],
                    [
                        'name'          => 'New item added #1',
                        'description'   => null,
                        'amount'        => 1300,
                        'total_amount'  => 1300,
                        'tax_amount'    => 335,
                        'net_amount'    => 1635,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 1,
                        'taxes'         => [
                            [
                                'tax_id'     => 'tax_00000000000001',
                                'name'       => 'Tax #1',
                                'rate'       => 1000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 118,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 2000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 217,
                            ],
                        ],
                    ],
                    [
                        'name'          => 'New item added #2',
                        'description'   => null,
                        'amount'        => 1300,
                        'total_amount'  => 6500,
                        'tax_amount'    => 0,
                        'net_amount'    => 6500,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 5,
                        'taxes'         => [],
                    ],
                ],
                'amount'             => 307800,
                'tax_amount'         => 60335,
                'net_amount'         => 368135,
                'currency'           => 'INR',
                'description'        => null,
                'status'             => 'draft',
                'type'               => 'invoice',
                'show_taxes_grouped' => false,
            ],
        ],
    ],
];
