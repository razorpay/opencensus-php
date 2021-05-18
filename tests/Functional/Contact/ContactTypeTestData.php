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

    'testGetContactTypeInternal' => [
        'request'  => [
            'url'     => '/contacts_internal/types',
            'method'  => 'GET'
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

    'testGetCustomContactTypeInternal' => [
        'request'  => [
            'url'     => '/contacts_internal/types',
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => "collection",
                'count'     => 5,
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
                    [
                        'type' => 'Contact Type 1'
                    ]
                ]
            ]
        ],
    ],
];
