<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\Tax\Entity as T;
use RZP\Models\Tax\Gst\GstTaxIdMap;
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
                                'rate'       => 100000,
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
                                'rate'       => 100000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 45,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 200000,
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
                                'rate'       => 100000,
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
                                'rate'       => 200000,
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
                                'rate'       => 100000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 100,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 200000,
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
                                'rate'       => 100000,
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
                                'rate'       => 100000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 35,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 200000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 69,
                            ],
                        ],
                    ],
                ],
                'gross_amount'          => 950,
                'tax_amount'            => 149,
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
                                'rate'       => 100000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 50,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 200000,
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
                                'rate'       => 100000,
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
                                'rate'       => 100000,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 50,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 200000,
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
                        'item_id' => 'item_00000000000003',
                        'name'    => 'Updated item name',
                        'tax_id'  => 'tax_00000000000002',
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

    'testCreateInvoiceLineItemWithTaxIdAndTaxIds' => [
        'request'   => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'line_items' => [
                    [
                        'name'    => 'Item #1',
                        'amount'  => 1000,
                        'tax_id'  => 'tax_00000000000001',
                        'tax_ids' => [
                            T::getIdPrefix() . GstTaxIdMap::CGST_50000,
                            T::getIdPrefix() . GstTaxIdMap::SGST_50000
                        ],
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

    'testCreateInvoiceWithSharedGstTaxes' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'name'          => 'Item #1',
                        'amount'        => 100000,
                        'quantity'      => 5,
                        'tax_ids'       => [
                            T::getIdPrefix() . GstTaxIdMap::CGST_50000,
                            T::getIdPrefix() . GstTaxIdMap::SGST_50000
                        ],
                        'tax_inclusive' => false,
                    ],
                    [
                        'name'          => 'Item #2',
                        'amount'        => 100000,
                        'quantity'      => 2,
                        'tax_ids'       => [T::getIdPrefix() . GstTaxIdMap::IGST_280000],
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
                        'amount'        => 100000,
                        'gross_amount'  => 500000,
                        'tax_amount'    => 50000,
                        'net_amount'    => 550000,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 5,
                        'taxes'         => [
                            [
                                'tax_id'     => T::getIdPrefix() . GstTaxIdMap::CGST_50000,
                                'name'       => 'CGST 5%',
                                'rate'       => 50000,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 25000,
                            ],
                            [
                                'tax_id'     => T::getIdPrefix() . GstTaxIdMap::SGST_50000,
                                'name'       => 'SGST 5%',
                                'rate'       => 50000,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 25000,
                            ],
                        ],
                    ],
                    [
                        'name'          => 'Item #2',
                        'description'   => null,
                        'amount'        => 100000,
                        'gross_amount'  => 200000,
                        'tax_amount'    => 56000,
                        'net_amount'    => 256000,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 2,
                        'taxes'         => [
                            [
                                'tax_id'     => T::getIdPrefix() . GstTaxIdMap::IGST_280000,
                                'name'       => 'IGST 28%',
                                'rate'       => 280000,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 56000,
                            ],
                        ],
                    ],
                ],
                'gross_amount'          => 700000,
                'tax_amount'            => 106000,
                'amount'                => 806000,
                'currency'              => 'INR',
                'description'           => null,
                'type'                  => 'invoice',
                'group_taxes_discounts' => false,
            ],
        ],
    ],

    'testCreateInvoiceWithSharedGstTaxes2' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'name'          => 'Item #1',
                        'amount'        => 1000,
                        'quantity'      => 1,
                        'tax_ids'       => [
                            T::getIdPrefix() . GstTaxIdMap::CGST_1250,
                            T::getIdPrefix() . GstTaxIdMap::SGST_1250,
                        ],
                        'tax_inclusive' => false,
                    ],
                    [
                        'name'          => 'Item #2',
                        'amount'        => 500,
                        'quantity'      => 2,
                        'tax_ids'       => [
                            T::getIdPrefix() . GstTaxIdMap::IGST_2500,
                        ],
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
                        'amount'        => 1000,
                        'gross_amount'  => 1000,
                        'tax_amount'    => 3,
                        'net_amount'    => 1003,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 1,
                        'taxes'         => [
                            [
                                'tax_id'     => T::getIdPrefix() . GstTaxIdMap::CGST_1250,
                                'name'       => 'CGST 0.125%',
                                'rate'       => 1250,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 1,
                            ],
                            [
                                'tax_id'     => T::getIdPrefix() . GstTaxIdMap::SGST_1250,
                                'name'       => 'SGST 0.125%',
                                'rate'       => 1250,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 1,
                            ],
                        ],
                    ],
                    [
                        'name'          => 'Item #2',
                        'description'   => null,
                        'amount'        => 500,
                        'gross_amount'  => 1000,
                        'tax_amount'    => 3,
                        'net_amount'    => 1003,
                        'currency'      => 'INR',
                        'tax_inclusive' => false,
                        'unit'          => null,
                        'quantity'      => 2,
                        'taxes'         => [
                            [
                                'tax_id'     => T::getIdPrefix() . GstTaxIdMap::IGST_2500,
                                'name'       => 'IGST 0.25%',
                                'rate'       => 2500,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 3,
                            ],
                        ],
                    ],
                ],
                'gross_amount'          => 2000,
                'tax_amount'            => 5,
                'amount'                => 2005,
                'currency'              => 'INR',
                'description'           => null,
                'type'                  => 'invoice',
                'group_taxes_discounts' => false,
            ],
        ],
    ],

    'testCreateInvoiceLineItemWithItemCessTax' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'item_id'       => 'item_00000000000003',
                        'quantity'      => 5,
                        'tax_ids'       => [
                            T::getIdPrefix() . GstTaxIdMap::CGST_50000,
                            T::getIdPrefix() . GstTaxIdMap::SGST_50000
                        ],
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
                        'name'          => 'Item Name',
                        'currency'      => 'INR',
                        'quantity'      => 5,
                        'taxes'         => [
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 200000,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                            ],
                            [
                                'tax_id'     => T::getIdPrefix() . GstTaxIdMap::CGST_50000,
                                'name'       => 'CGST 5%',
                                'rate'       => 50000,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 25000,
                            ],
                            [
                                'tax_id'     => T::getIdPrefix() . GstTaxIdMap::SGST_50000,
                                'name'       => 'SGST 5%',
                                'rate'       => 50000,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 25000,
                            ],
                        ],
                    ],
                ],
                'type'                  => 'invoice',
            ],
        ],
    ],

    'testCreateInvoiceWithCgstAndSgstTaxes' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'type'       => 'invoice',
                'draft'      => '1',
                'line_items' => [
                    [
                        'name'     => 'Sample item #1',
                        'amount'   => 100,
                        'quantity' => 5,
                        'tax_ids'  => [
                            T::getIdPrefix() . GstTaxIdMap::CGST_25000,
                            T::getIdPrefix() . GstTaxIdMap::SGST_25000
                        ],
                        'tax_inclusive' => false,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'line_items' => [
                    [
                        'name'          => 'Sample item #1',
                        'amount'        => 100,
                        'unit_amount'   => 100,
                        'gross_amount'  => 500,
                        'tax_amount'    => 25,
                        'net_amount'    => 525,
                        'currency'      => 'INR',
                        'type'          => 'invoice',
                        'tax_inclusive' => false,
                        'quantity'      => 5,
                        'taxes'         => [
                            [
                                'tax_id'     => 'tax_9nDpYhZ0d60X7V',
                                'name'       => 'CGST 2.5%',
                                'rate'       => 25000,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 13,
                            ],
                            [
                                'tax_id'     => 'tax_9nDpYoeYBsXRvC',
                                'name'       => 'SGST 2.5%',
                                'rate'       => 25000,
                                'rate_type'  => 'percentage',
                                'group_id'   => null,
                                'group_name' => null,
                                'tax_amount' => 13,
                            ],
                        ],
                    ],
                ],
                'status'       => 'draft',
                'gross_amount' => 500,
                'tax_amount'   => 25,
                'amount'       => 525,
                'amount_paid'  => null,
                'amount_due'   => null,
                'currency'     => 'INR',
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
                                'rate'       => 100000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 10000,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 200000,
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
                                'rate'       => 100000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 10000,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 200000,
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
                                'rate'       => 100000,
                                'rate_type'  => 'percentage',
                                'group_id'   => 'taxg_00000000000001',
                                'group_name' => 'Tax Group #1',
                                'tax_amount' => 130,
                            ],
                            [
                                'tax_id'     => 'tax_00000000000002',
                                'name'       => 'Tax #2',
                                'rate'       => 200000,
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
