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
            ],
        ],
        'response' => [
            'content' => [
                'name'        => 'Item 1',
                'description' => 'Item 1 description :) ..',
                'amount'      => 100,
                'currency'    => 'INR',
            ],
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
                'id'          => 'item_1000000000item',
                'name'        => 'Some item name',
                'description' => 'Some item description',
                'amount'      => 100000,
                'currency'    => 'INR',
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
                        'id'          => 'item_1000000001item',
                        'name'        => 'A different product',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'currency'    => 'INR',
                    ],
                    [
                        'id'          => 'item_1000000000item',
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'currency'    => 'INR',
                    ],
                ],
            ],
        ],
    ],

    'testPutItem' => [
        'request' => [
            'url'     => '/items/item_1000000000item',
            'method'  => 'put',
            'content' => [
                'name'        => 'Item 2 Updated',
                'amount'      => 1000,
            ],
        ],
        'response' => [
            'content' => [
                'id'          => 'item_1000000000item',
                'name'        => 'Item 2 Updated',
                'description' => 'Some item description',
                'amount'      => 1000,
                'currency'    => 'INR',
            ],
        ],
    ],

    'testPutItemHavingLineItemsAssociated' => [
        'request' => [
            'url'     => '/items/item_1000000000item',
            'method'  => 'put',
            'content' => [
                'name'        => 'Item 2 Updated',
                'amount'      => 1000,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You can not edit/delete an item with which invoices has been created already.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
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
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You can not edit/delete an item with which invoices has been created already.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
