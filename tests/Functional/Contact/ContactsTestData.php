<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testGetContact' => [
        'request'  => [
            'url'    => '/contacts/cont_1000000contact',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id'     => 'cont_1000000contact',
                'entity' => 'contact',
            ],
        ],
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
            ],
        ],
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
        ],
    ],

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
        ],
    ],

    'testCreateContactWithoutName' => [
        'request'   => [
            'content' => [
                'type'  => 'self',
                'email' => 'asd@abc.com',
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The name field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateContactInvalidName' => [
        'request'   => [
            'content' => [
                'type' => 'self',
                'name' => 'Amit@M',
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The name field is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateContactInvalidType' => [
        'request'   => [
            'content' => [
                'type' => 'invalid_type',
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid contact type: invalid_type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateContact' => [
        'request'  => [
            'content' => [
                'type' => 'employee',
            ],
            'url'     => '/contacts/cont_1000000contact',
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'id'     => 'cont_1000000contact',
                'entity' => 'contact',
                'type'   => 'employee',
            ]
        ]
    ],

    'testDeleteContact' => [
        'request'  => [
            'url'    => '/contacts/cont_1000000contact',
            'method' => 'DELETE'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
    ],
];
