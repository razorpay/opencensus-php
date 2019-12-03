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
                'name'         => 'Test / Contact',
                'type'         => 'self',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'contact',
                'name'         => 'Test / Contact',
                'type'         => 'self',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'status_code' => '201'
        ],
    ],

    'testCreateContactWithoutType' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'contact',
                'name'         => 'Test / Contact',
            ],
            'status_code' => '201'
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
                'name' => 'Test',
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid type: invalid_type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateContactInvalidReferenceId' => [
        'request'   => [
            'content' => [
                'name'         => 'Test',
                'reference_id' => '12345678901234567890123456789012345678901234567890',
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The reference id may not be greater than 40 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchContactsByNameActiveAndType' => [
        'request'  => [
            'url'    => '/contacts',
            'method' => 'GET',
            'content' => [
                'name'   => 'Test Contact',
                'active' => 1,
                'type'   => 'vendor',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 0,
                'items'  => [
                ],
            ]
        ],
    ],


    'testUpdateContact' => [
        'request'  => [
            'content' => [
                'type'         => 'employee',
                'reference_id' => '213',
            ],
            'url'     => '/contacts/cont_1000000contact',
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'id'           => 'cont_1000000contact',
                'entity'       => 'contact',
                'type'         => 'employee',
                'reference_id' => '213',
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

    'testFetchContactByAccountNumber' => [
        'request'  => [
            'url'    => '/contacts?account_number=111000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'     => 'cont_1000005contact',
                        'entity' => 'contact',
                        'email'  => 'test@test5.com',
                    ],
                ],
            ]
        ],
    ],

    'testFetchContactByFundAccountId' => [
        'request'  => [
            'url'    => '/contacts?account_number=111000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'     => 'cont_1000005contact',
                        'entity' => 'contact',
                        'email'  => 'test@test5.com',
                    ],
                ],
            ]
        ],
    ],

    'testFetchContactByActive' => [
        'request'  => [
            'url'    => '/contacts?account_number=111000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'     => 'cont_1000005contact',
                        'entity' => 'contact',
                        'email'  => 'test@test5.com',
                    ],
                ],
            ]
        ],
    ],

    'testFetchContactByType' => [
        'request'  => [
            'url'    => '/contacts?account_number=111000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'     => 'cont_1000005contact',
                        'entity' => 'contact',
                        'email'  => 'test@test5.com',
                    ],
                ],
            ]
        ],
    ],

    'testFetchContactsByEmailExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'contact_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'contact_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'email' => [
                                    'query'                =>'random@test.com',
                                    'boost'                => 2,
                                    'minimum_should_match' => '75%',
                                ],
                            ],
                        ],
                    ],
                    'filter' => [
                        'bool' => [
                            'must' => [
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

    'testFetchContactsByEmailExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '1000002contact',
                ],
            ],
        ],
    ],

    'testFetchContactsByNameActiveAndTypeExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'contact_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'contact_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'name' => [
                                    'query'                =>'test contact',
                                    'boost'                => 2,
                                    'minimum_should_match' => '75%',
                                ],
                            ],
                        ],
                    ],
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'active' => [
                                            'value' => true,
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'type' => [
                                            'value' => 'vendor',
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

    'testFetchContactsByNameActiveAndTypeExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
            ],
        ],
    ],

    'testDuplicateContactCreationOnApi' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'self',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],

        'response' => [
            'content' => [
                'entity'       => 'contact',
                'name'         => 'Test / Contact',
                'type'         => 'self',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'status_code' => '200'
        ],
    ],

    'testDuplicateContactCreationOnDashboard' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'self',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'contact',
                'name'         => 'Test / Contact',
                'type'         => 'self',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'status_code' => '201'
        ],
    ],

    'testDuplicateContactCreationWithSameName' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'contact',
                'name'         => 'Test / Contact',
            ],
            'status_code' => '201'
        ],
    ],

    'testDuplicateContactCreationWithSameNameAndNullAttributes' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],

        'response' => [
            'content' => [
                'entity'       => 'contact',
                'name'         => 'Test / Contact',
           ],
            'status_code' => '200'
        ],
    ],

    'testDuplicateContactCreationWithSameNameAndEmptyAttributes' =>  [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'contact'      => ""
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],

        'response' => [
            'content' => [
                'entity'       => 'contact',
                'name'         => 'Test / Contact',

            ],
            'status_code' => '201'
        ],
    ],
];
