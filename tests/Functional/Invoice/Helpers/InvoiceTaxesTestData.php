<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestValidationFailureException;

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
                'customer_details' => [
                    'name'    => null,
                    'email'   => 'test@test.test',
                    'contact' => null
                ],
                'line_items' => [
                    [
                        'name'          => 'Item #1',
                        'description'   => null,
                        'amount'        => 100,
                        'gross_amount'  => 500,
                        'tax_amount'    => 50,
                        'net_amount'    => 550,
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
                                'tax_amount' => 50,
                            ],
                        ],
                    ],
                    [
                        'name'          => 'Item #2',
                        'description'   => null,
                        'amount'        => 150,
                        'gross_amount'  => 450,
                        'tax_amount'    => 135,
                        'net_amount'    => 585,
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
                                'tax_amount' => 45,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 2000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 90,
                            ],
                        ],
                    ],
                ],
                'gross_amount'          => 950,
                'tax_amount'            => 185,
                'amount'                => 1135,
                'currency'              => 'INR',
                'description'           => null,
                'type'                  => 'invoice',
                'group_taxes_discounts' => false,
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
                'customer_details' => [
                    'name'    => null,
                    'email'   => 'test@test.test',
                    'contact' => null
                ],
                'line_items' => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'gross_amount'  => 100000,
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
                        'gross_amount'  => 100000,
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
                        'gross_amount'  => 100000,
                        'tax_amount'    => 20000,
                        'net_amount'    => 120000,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 1,
                        'taxes' => [
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 2000,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 20000,
                            ],
                        ],
                    ],
                    [
                        'name'          => 'Item #3',
                        'description'   => null,
                        'amount'        => 1000,
                        'gross_amount'  => 1000,
                        'tax_amount'    => 300,
                        'net_amount'    => 1300,
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
                                'tax_amount' => 100,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 2000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 200,
                            ],
                        ],
                    ],
                ],
                'gross_amount'          => 301000,
                'tax_amount'            => 30300,
                'amount'                => 331300,
                'currency'              => 'INR',
                'description'           => null,
                'type'                  => 'invoice',
                'group_taxes_discounts' => false,
            ],
        ],
    ],

    'testCreateInvoiceWithTaxes3' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'name'          => 'Item #1',
                        'amount'        => 100,
                        'quantity'      => 5,
                        'tax_id'        => 'tax_00000000000001',
                        'tax_inclusive' => true,
                    ],
                    [
                        'name'          => 'Item #2',
                        'amount'        => 150,
                        'quantity'      => 3,
                        'tax_group_id'  => 'taxg_00000000000001',
                        'tax_inclusive' => true,
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
                'customer_details' => [
                    'name'    => null,
                    'email'   => 'test@test.test',
                    'contact' => null
                ],
                'line_items' => [
                    [
                        'name'          => 'Item #1',
                        'description'   => null,
                        'amount'        => 100,
                        'gross_amount'  => 500,
                        'tax_amount'    => 46,
                        'net_amount'    => 500,
                        'currency'      => 'INR',
                        'tax_inclusive' => true,
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
                                'tax_amount' => 46,
                            ],
                        ],
                    ],
                    [
                        'name'          => 'Item #2',
                        'description'   => null,
                        'amount'        => 150,
                        'gross_amount'  => 450,
                        'tax_amount'    => 104,
                        'net_amount'    => 450,
                        'currency'      => 'INR',
                        'tax_inclusive' => true,
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
                                'tax_amount' => 35,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 2000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 69,
                            ],
                        ],
                    ],
                ],
                'gross_amount'          => 950,
                'tax_amount'            => 150,
                'amount'                => 950,
                'currency'              => 'INR',
                'description'           => null,
                'type'                  => 'invoice',
                'group_taxes_discounts' => false,
            ],
        ],
    ],

    'testCreateInvoiceWithTaxes4' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'name'          => 'Item #1',
                        'amount'        => 100,
                        'quantity'      => 5,
                        'tax_group_id'  => 'taxg_00000000000001',
                        'tax_inclusive' => false,
                    ],
                    [
                        'name'          => 'Item #2',
                        'amount'        => 500,
                        'quantity'      => 3,
                        'tax_group_id'  => 'taxg_00000000000002',
                        'tax_inclusive' => true,
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
                'customer_details' => [
                    'name'    => null,
                    'email'   => 'test@test.test',
                    'contact' => null
                ],
                'line_items' => [
                    [
                        'name'          => 'Item #1',
                        'description'   => null,
                        'amount'        => 100,
                        'gross_amount'  => 500,
                        'tax_amount'    => 150,
                        'net_amount'    => 650,
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
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 50,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 2000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 100,
                            ],
                        ],
                    ],
                    [
                        'name'          => 'Item #2',
                        'description'   => null,
                        'amount'        => 500,
                        'gross_amount'  => 1500,
                        'tax_amount'    => 409,
                        'net_amount'    => 1500,
                        'currency'      => 'INR',
                        'tax_inclusive' => true,
                        'unit'          => null,
                        'quantity'      => 3,
                        'taxes'         => [
                            [
                                'tax_id'     => 'tax_00000000000001',
                                'name'       => 'Tax #1',
                                'rate'       => 1000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000002',
                                'group_name' => 'Tax Group #2',
                                'tax_amount' => 109,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000004',
                                'name'       => 'Flat Tax #4',
                                'rate'       => 100,
                                'rate_type'  => 'flat',
                                'group_id'   => 'taxg_00000000000002',
                                'group_name' => 'Tax Group #2',
                                'tax_amount' => 300,
                            ],
                        ],
                    ],
                ],
                'gross_amount'          => 2000,
                'tax_amount'            => 559,
                'amount'                => 2150,
                'currency'              => 'INR',
                'description'           => null,
                'type'                  => 'invoice',
                'group_taxes_discounts' => false,
            ],
        ],
    ],

    'testCreateInvoiceWithMultipleTaxIds' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'name'          => 'Item #1',
                        'amount'        => 100,
                        'quantity'      => 5,
                        'tax_ids'       => ['tax_00000000000001', 'tax_00000000000002'],
                        'tax_inclusive' => false,
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
                'customer_details' => [
                    'name'    => null,
                    'email'   => 'test@test.test',
                    'contact' => null
                ],
                'line_items' => [
                    [
                        'name'          => 'Item #1',
                        'description'   => null,
                        'amount'        => 100,
                        'gross_amount'  => 500,
                        'tax_amount'    => 150,
                        'net_amount'    => 650,
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
                                'tax_amount' => 50,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 2000,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 100,
                            ],
                        ],
                    ],
                ],
                'gross_amount'          => 500,
                'tax_amount'            => 150,
                'amount'                => 650,
                'currency'              => 'INR',
                'description'           => null,
                'type'                  => 'invoice',
                'group_taxes_discounts' => false,
            ],
        ],
    ],

    'testCreateInvoiceLineItemWithTaxIdAndTaxGroupId' => [
        'request'   => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'line_items' => [
                    [
                        'item_id' => 'item_00000000000001',
                    ],
                    [
                        'item_id' => 'item_00000000000002',
                    ],
                    [
                        'item_id' => 'item_00000000000003',
                        'name'    => 'Updated item name',
                        'tax_id'  => 'tax_00000000000002',
                    ],
                    [
                        'name'         => 'Item #3',
                        'amount'       => 1000,
                        'tax_group_id' => 'taxg_00000000000001'
                    ],
                ],
                'type'       => 'invoice',
                'draft'      => '1',
                'customer'   => [
                    'email' => 'test@test.test'
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only one among tax_id, tax_ids or tax_group_id can be present',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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
                'customer_details' => [
                    'name'    => null,
                    'email'   => 'test@test.test',
                    'contact' => null
                ],
                'line_items' => [
                    [
                        'name'          => 'Updated name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'gross_amount'  => 100000,
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
                        'gross_amount'  => 100000,
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
                        'gross_amount'  => 100000,
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
                        'gross_amount'  => 1300,
                        'tax_amount'    => 390,
                        'net_amount'    => 1690,
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
                                'tax_amount' => 130,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 2000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 260,
                            ],
                        ],
                    ],
                    [
                        'name'          => 'New item added #2',
                        'description'   => null,
                        'amount'        => 1300,
                        'gross_amount'  => 6500,
                        'tax_amount'    => 0,
                        'net_amount'    => 6500,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 5,
                        'taxes'         => [],
                    ],
                ],
                'gross_amount'          => 307800,
                'tax_amount'            => 60390,
                'amount'                => 368190,
                'currency'              => 'INR',
                'description'           => null,
                'status'                => 'draft',
                'type'                  => 'invoice',
                'group_taxes_discounts' => false,
            ],
        ],
    ],
];
