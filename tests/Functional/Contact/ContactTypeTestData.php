<?php


namespace RZP\Tests\Functional\Contacts;

return [
    'testCreateContactType' => [
        'request'  => [
            'url'     => '/contacts/types',
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => "collection",
            ]
        ],
    ],

    'testCreateCustomContactNumericType' => [
        'request'  => [
            'url'     => '/contacts/types',
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => "collection",
            ]
        ],
    ],

    'testGetContactType' => [
        'request'  => [
            'url'     => '/contacts/types',
            'method'  => 'GET',
            'content' => [
                'type' => 'Contact Type 1',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => "collection",
                'items' => [
                    [
                        'type' => 'customer'
                    ],
                    [
                        'type' => 'employee'
                    ],
                    [
                        'type' => 'vendor'
                    ],
                    [
                        'type' => 'self'
                    ],
                ]
            ]
        ],
    ],
];
