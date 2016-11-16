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
];
