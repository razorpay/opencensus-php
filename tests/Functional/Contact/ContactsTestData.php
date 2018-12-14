<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateContact' => [
        'request'  => [
            'content' => [
                'name'    => 'Test Contact',
                'type'    => 'self',
                'email'   => 'asd@abc.com',
                'contact' => '9123456789',
                'notes'   => [
                    'test1' => 'One',
                ],
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'  => 'contact',
                'name'    => 'Test Contact',
                'type'    => 'self',
                'email'   => 'asd@abc.com',
                'contact' => '9123456789',
                'notes'   => [
                    'test1' => 'One',
                ],
            ]
        ]
    ],

    'testGetContact' => [
        'request'  => [
            'url'    => '/contacts/cont_1000000contact',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id'     => 'cont_1000000contact',
                'entity' => 'contact',
            ]
        ]
    ],

    'testFetchContacts' => [
        'request'  => [
            'url'    => '/contacts',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'id'     => 'cont_1000002contact',
                        'entity' => 'contact',
                        'name'   => 'Contact Y',
                    ],
                    [
                        'id'     => 'cont_1000001contact',
                        'entity' => 'contact',
                        'name'   => 'Contact X',
                    ],
                ],
            ]
        ]
    ],

    'testFetchContactsByEmail' => [
        'request'  => [
            'url'    => '/contacts?email=random@test.com',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'     => 'cont_1000002contact',
                        'entity' => 'contact',
                        'email'  => 'random@test.com',
                    ],
                ],
            ]
        ]
    ],
];
