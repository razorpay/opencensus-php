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
                'hsn_code'    => '00110022',
                'tax_rate'    => 1800,
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
                'hsn_code'      => '00110022',
                'tax_rate'      => 1800,
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
                'sac_code'    => '914566',
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
                'sac_code'      => '914566',
                'tax_rate'      => null,
                'unit'          => null,
                'tax_inclusive' => false,
                'tax_id'        => null,
                'tax_group_id'  => null,
            ],
        ],
    ],

    'testCreateItemWithoutCurrency' => [
        'request' => [
            'url'     => '/items',
            'method'  => 'post',
            'content' => [
                'name'        => 'Item 1',
                'description' => 'Item 1 description :) ..',
                'amount'      => 100,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The currency field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateItemWithInvalidTaxRate' => [
        'request' => [
            'url'     => '/items',
            'method'  => 'post',
            'content' => [
                'name'        => 'Item 1',
                'description' => 'Item 1 description :) ..',
                'amount'      => 100,
                'currency'    => 'INR',
                'tax_rate'    => 15000,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The tax rate must be a valid integer between 0 and 10000',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateItemWithTaxId' => [
        'request'  => [
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
                'tax'           => [
                    'id' => 'tax_00000000000001',
                ],
                'tax_group_id'  => null,
            ],
        ],
    ],

    'testCreateItemWithTaxIdInternational' => [
        'request'  => [
            'url'     => '/items',
            'method'  => 'post',
            'content' => [
                'name'        => 'Item 1',
                'description' => 'Item 1 description :) ..',
                'amount'      => 100,
                'currency'    => 'USD',
                'tax_id'      => 'tax_00000000000001',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'tax_id is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
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

    'testCreateItemWithHsnAndSacCode' => [
        'request'   => [
            'url'     => '/items',
            'method'  => 'post',
            'content' => [
                'name'        => 'Item 1',
                'description' => 'Item 1 description :) ..',
                'amount'      => 100,
                'currency'    => 'INR',
                'hsn_code'    => '00110022',
                'sac_code'    => '914600',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Both hsn_code and sac_code cannot be present',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateItemToContainBothHsnAndSacCode' => [
        'request'   => [
            'url'     => '/items/item_1000000001item',
            'method'  => 'patch',
            'content' => [
                'sac_code' => '914600',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Both hsn_code and sac_code cannot be present',
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

    'testGetItemWithExpandTax' => [
        'request' => [
            'url'     => '/items/item_1000000000item',
            'method'  => 'get',
            'content' => [
                'expand' => [
                    'tax',
                ],
            ],
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
                'tax_id'        => 'tax_00000000000001',
                'tax'           => [
                    'id'        => 'tax_00000000000001',
                    'entity'    => 'tax',
                    'name'      => 'Tax #1',
                    'rate_type' => 'percentage',
                    'rate'      => 100000,
                ],
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

    'testGetMultipleItemsWithExpandTax' => [
        'request'  => [
            'url'     => '/items?expand[]=tax',
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
                        'tax'           => null,
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
                        'tax_id'        => 'tax_00000000000001',
                        'tax'           => [
                            'id'        => 'tax_00000000000001',
                            'name'      => 'Tax #1',
                            'rate_type' => 'percentage',
                            'rate'      => 100000,
                        ],
                        'tax_group_id'  => null,
                    ],
                ],
            ],
        ],
    ],

    'testGetMultipleItemsViaEs' => [
        'request' => [
            'url'     => '/items',
            'method'  => 'get',
            'content' => [
                'q'      => 'product',
                'type'   => 'invoice',
                'active' => '1',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'id' => 'item_1000000001item',
                        // Doesn't assert rest attributes in this test case.
                    ],
                ],
            ],
        ],
    ],

    'testGetMultipleItemsViaEsExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'item_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'item_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'multi_match' => [
                                'query'  => 'product',
                                'type'   => 'best_fields',
                                'fields' => [
                                    'name',
                                    'description',
                                ],
                                'boost'                => 1,
                                'minimum_should_match' => '75%',
                                'lenient'              => true
                            ],
                        ]
                    ],
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'type' => [
                                            'value' => 'invoice',
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'active' => [
                                            'value' => true,
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testGetMultipleItemsViaEsExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '1000000001item',
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
                    'description' => 'Cannot delete an item with which invoices have been created already',
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
