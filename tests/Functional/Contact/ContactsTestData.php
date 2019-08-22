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
                'name'         => 'Test Contact',
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
                'name'         => 'Test Contact',
                'type'         => 'self',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
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

    'testBulkContact' => [
        'request'   => [
            'url'     => '/contacts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'vendor',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp3',
                        'account_IFSC'      => 'HDFC0003780',
                        'account_number'    => '1234567891',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc125'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'details' => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'details'               => [
                            'address'           => '123@ybl',
                        ],
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'details'               => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'bank_account'          => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ]
                ]
            ],
        ],
    ],

    'testBulkContactWithInvalidContactId' => [
        'request'   => [
            'url'     => '/contacts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => 'invalid',
                        'type'              => 'vendor',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp3',
                        'account_IFSC'      => 'HDFC0003780',
                        'account_number'    => '1234567891',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc125'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'http_status_code'      => 400,
                        'error'                 => [
                            'description'       => 'The id provided does not exist',
                            'code'              => 'BAD_REQUEST_ERROR'
                        ],
                        'idempotency_key'       => 'batch_abc123'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'details'               => [
                            'address'           => '123@ybl',
                        ],
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'details'               => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'bank_account'          => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ]
                ]
            ],
        ],
    ],

    'testBulkContactWithValidContactId' => [
        'request'   => [
            'url'     => '/contacts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => 'cont_1000001contact',
                        'type'              => 'vendor',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123',
                    'contact_id'            => 'cont_1000001contact'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp3',
                        'account_IFSC'      => 'HDFC0003780',
                        'account_number'    => '1234567891',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc125'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'details' => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'details'               => [
                            'address'           => '123@ybl',
                        ],
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'details'               => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'bank_account'          => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ]
                ]
            ],
        ],
    ],

    'testBulkContactWithSameIdempotencyKey' => [
        'request'   => [
            'url'     => '/contacts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'vendor',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp3',
                        'account_IFSC'      => 'HDFC0003780',
                        'account_number'    => '1234567891',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'details' => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'details'               => [
                            'address'           => '123@ybl',
                        ],
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'details' => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ]
                ]
            ],
        ],
    ],
];
