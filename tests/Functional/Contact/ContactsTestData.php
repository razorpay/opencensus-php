<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

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

    'testGetContactTypes' => [
        'request'  => [
            'url'     => '/contacts/types',
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                'entity'    => "collection",
                'count'     => 4,
                'items'     => [
                    [
                        'type' => "customer",
                    ],
                    [
                        'type' => "employee",
                    ],
                    [
                        'type' => "vendor",
                    ],
                    [
                        'type' => "self",
                    ],
                ],
            ],
        ],
    ],

    'testAddCustomContactType' => [
        'request'  => [
            'content' => [
                'type' => 'Payouts to Mehul'
            ],
            'url'     => '/contacts/types',
            'method'  => 'POST'
        ],

        'response' => [
            'content' => [
                'entity'    => "collection",
                'count'     => 5,
                'items'     => [
                    [
                        'type' => "customer",
                    ],
                    [
                        'type' => "employee",
                    ],
                    [
                        'type' => "vendor",
                    ],
                    [
                        'type' => "self",
                    ],
                    [
                        'type' => "Payouts to Mehul",
                    ],
                ],
            ],
        ],
    ],

    'testAddCustomContactTypeThatAlreadyExists' => [
        'request'  => [
            'content' => [
                'type' => 'Payouts to Mehul'
            ],
            'url'     => '/contacts/types',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Type \'Payouts to Mehul\' is already defined and cannot be added.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAdd101CustomContactTypes' => [
        'request'  => [
            'content' => [
                'type' => 'Payouts to Mehul'
            ],
            'url'     => '/contacts/types',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You have reached the maximum limit (100) of custom contact types that can be created.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateBulkContactsMoreThanAllowedNumber' => [
        'request'   => [
            'url'     => '/contacts/bulk',
            'method'  => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Current batch size 16, max limit of Bulk Fund Account is 15',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateBulkContactsInvalidType' => [
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
                        'type'              => 'RZP Employees',
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
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'error' => [
                            'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                            'description' => 'Invalid type: RZP Employees',
                        ],
                        'http_status_code' => 400,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ],
                ],
            ],
        ],
    ],

    'testCreateBulkContactsInvalidName' => [
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
                        'name'              => 'A name can only be 50 characters long and this is more than that',
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
            ],
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'error' => [
                            'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                            'description' => 'The name may not be greater than 50 characters.',
                        ],
                        'http_status_code' => 400,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                ],
            ],
        ],
    ],

    'testAddCustomContactTypeRZPFees' =>  [
        'request'  => [
            'content' => [
                'type'  => 'rzp_fees',
            ],
            'url'     => '/contacts/types',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Type \'rzp_fees\' is an internal contact type used by Razorpay and cannot be added.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateRZPFeesContact' => [
        'request'  => [
            'content' => [
                'type'      => 'self',
                'active'    => 0,
            ],
            'method'  => 'PATCH'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
        ],
    ],

    'testUpdateContactTypeToRZPFeesContact' => [
        'request'  => [
            'content' => [
                'type'  => 'rzp_fees',
            ],
            'method'  => 'PATCH'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid type: rzp_fees',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateRZPFeesTypeContact' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'rzp_fees',
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
        ],
    ],
];
