<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateItem' => [
        'request' => [
            'url'     => '/items',
            'method'  => 'post',
            'content' => [
                'name'        => 'Item 1',
                'description' => 'Item 1 description :) ..',
                'amount'      => 100,
                'currency'    => 'INR',
                'type'        => 'invoice',
            ],
        ],
        'response' => [
            'content' => [
                'active'        => true,
                'name'          => 'Item 1',
                'description'   => 'Item 1 description :) ..',
                'amount'        => 100,
                'currency'      => 'INR',
                'unit'          => null,
                'tax_inclusive' => false,
                'tax_id'        => null,
                'tax_group_id'  => null,
            ],
        ],
    ],

    'testCreateItem2' => [
        'request' => [
            'url'     => '/items',
            'method'  => 'post',
            'content' => [
                'name'        => 'Item 1',
                'description' => 'Item 1 description :) ..',
                'unit_amount' => 100,
                'currency'    => 'INR',
                'type'        => 'invoice',
            ],
        ],
        'response' => [
            'content' => [
                'active'        => true,
                'name'          => 'Item 1',
                'description'   => 'Item 1 description :) ..',
                'amount'        => 100,
                'unit_amount'   => 100,
                'currency'      => 'INR',
                'unit'          => null,
                'tax_inclusive' => false,
                'tax_id'        => null,
                'tax_group_id'  => null,
            ],
        ],
    ],


    'testCreateItemWithTaxId' => [
        'request' => [
            'url'     => '/items',
            'method'  => 'post',
            'content' => [
                'name'        => 'Item 1',
                'description' => 'Item 1 description :) ..',
                'amount'      => 100,
                'currency'    => 'INR',
                'tax_id'      => 'tax_00000000000001',
            ],
        ],
        'response' => [
            'content' => [
                'active'        => true,
                'name'          => 'Item 1',
                'description'   => 'Item 1 description :) ..',
                'amount'        => 100,
                'currency'      => 'INR',
                'unit'          => null,
                'tax_inclusive' => false,
                'tax_id'        => 'tax_00000000000001',
                'tax_group_id'  => null,
            ],
        ],
    ],

    'testCreateItemWithTaxGroupId' => [
        'request' => [
            'url'     => '/items',
            'method'  => 'post',
            'content' => [
                'name'         => 'Item 1',
                'description'  => 'Item 1 description :) ..',
                'amount'       => 100,
                'currency'     => 'INR',
                'tax_group_id' => 'taxg_00000000000001',
            ],
        ],
        'response' => [
            'content' => [
                'active'        => true,
                'name'          => 'Item 1',
                'description'   => 'Item 1 description :) ..',
                'amount'        => 100,
                'currency'      => 'INR',
                'unit'          => null,
                'tax_inclusive' => false,
                'tax_id'        => null,
                'tax_group_id'  => 'taxg_00000000000001',
            ],
        ],
    ],

    'testCreateItemWithBothTaxIdAndTaxGroupId' => [
        'request' => [
            'url'     => '/items',
            'method'  => 'post',
            'content' => [
                'name'         => 'Item 1',
                'description'  => 'Item 1 description :) ..',
                'amount'       => 100,
                'currency'     => 'INR',
                'tax_id'       => 'tax_00000000000001',
                'tax_group_id' => 'taxg_00000000000001',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Both tax_id and tax_group_id cannot be present',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetItem' => [
        'request' => [
            'url' => '/items/item_1000000000item',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'active'        => true,
                'id'            => 'item_1000000000item',
                'name'          => 'Some item name',
                'description'   => 'Some item description',
                'amount'        => 100000,
                'currency'      => 'INR',
                'unit'          => null,
                'tax_inclusive' => false,
                'tax_id'        => null,
                'tax_group_id'  => null,
            ],
        ],
    ],

    'testGetMultipleItems' => [
        'request' => [
            'url'     => '/items',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'items' => [
                    [
                        'id'            => 'item_1000000001item',
                        'active'        => true,
                        'name'          => 'A different product',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'currency'      => 'INR',
                        'unit'          => null,
                        'tax_inclusive' => false,
                        'tax_id'        => null,
                        'tax_group_id'  => null,
                    ],
                    [
                        'id'            => 'item_1000000000item',
                        'active'        => true,
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'currency'      => 'INR',
                        'unit'          => null,
                        'tax_inclusive' => false,
                        'tax_id'        => null,
                        'tax_group_id'  => null,
                    ],
                ],
            ],
        ],
    ],

    'testUpdateItem' => [
        'request' => [
            'url'     => '/items/item_1000000000item',
            'method'  => 'patch',
            'content' => [
                'active' => '0',
                'name'   => 'Item 2 Updated',
                'amount' => 1000,
                'unit'   => 'Pc',
            ],
        ],
        'response' => [
            'content' => [
                'id'            => 'item_1000000000item',
                'active'        => false,
                'name'          => 'Item 2 Updated',
                'description'   => 'Some item description',
                'amount'        => 1000,
                'currency'      => 'INR',
                'unit'          => 'Pc',
                'tax_inclusive' => false,
                'tax_id'        => 'tax_00000000000001',
                'tax_group_id'  => null,
            ],
        ],
    ],

    'testUpdateItem2' => [
        'request' => [
            'url'     => '/items/item_1000000000item',
            'method'  => 'patch',
            'content' => [
                'name'        => 'Item 2 Updated',
                'unit_amount' => 5000,
            ],
        ],
        'response' => [
            'content' => [
                'id'            => 'item_1000000000item',
                'active'        => true,
                'name'          => 'Item 2 Updated',
                'description'   => 'Some item description',
                'amount'        => 5000,
                'unit_amount'   => 5000,
                'currency'      => 'INR',
            ],
        ],
    ],

    'testUpdateItemWithNewTaxId' => [
        'request' => [
            'url'     => '/items/item_1000000000item',
            'method'  => 'patch',
            'content' => [
                'tax_id' => 'tax_00000000000004',
            ],
        ],
        'response' => [
            'content' => [
                'id'            => 'item_1000000000item',
                'tax_inclusive' => false,
                'tax_id'        => 'tax_00000000000004',
                'tax_group_id'  => null,
            ],
        ],
    ],

    'testUpdateItemWithNewTaxGroupId' => [
        'request' => [
            'url'     => '/items/item_1000000000item',
            'method'  => 'patch',
            'content' => [
                'tax_group_id' => 'taxg_00000000000002',
            ],
        ],
        'response' => [
            'content' => [
                'id'            => 'item_1000000000item',
                'tax_inclusive' => false,
                'tax_id'        => null,
                'tax_group_id'  => 'taxg_00000000000002',
            ],
        ],
    ],

    'testUpdateItemWithTaxIdWhenTaxGroupIdExists' => [
        'request' => [
            'url'     => '/items/item_1000000000item',
            'method'  => 'patch',
            'content' => [
                'tax_id' => 'tax_00000000000001',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Both tax_id and tax_group_id cannot be present',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateItemWithTaxIdAndRemoveTaxGroupId' => [
        'request' => [
            'url'     => '/items/item_1000000000item',
            'method'  => 'patch',
            'content' => [
                'tax_id'       => 'tax_00000000000001',
                'tax_group_id' => null,
            ],
        ],
        'response' => [
            'content' => [
                'id'            => 'item_1000000000item',
                'tax_inclusive' => false,
                'tax_id'        => 'tax_00000000000001',
                'tax_group_id'  => null,
            ],
        ],
    ],

    'testUpdateItemOfTypeNonInvoice' => [
        'request' => [
            'url'     => '/items/item_1000000000item',
            'method'  => 'patch',
            'content' => [
                'name' => 'Updated name',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Update operation not allowed for item of type: plan',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDeleteItem' => [
        'request' => [
            'url'     => '/items/item_1000000000item',
            'method'  => 'delete',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDeleteItemHavingLineItemsAssociated' => [
        'request' => [
            'url'     => '/items/item_1000000000item',
            'method'  => 'delete',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Cannot edit/delete an item with which invoices have been created already',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDeleteItemOfTypeNonInvoice' => [
        'request' => [
            'url'    => '/items/item_1000000000item',
            'method' => 'delete',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Delete operation not allowed for item of type: plan',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
