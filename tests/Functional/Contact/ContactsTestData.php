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

    'testGetContactDetailsForCheckout' => [
        'request'  => [
            'url'    => '/checkout/contacts/cont_1000000contact?expand[]=fund_accounts',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id'   => 'cont_1000000contact',
                'name' => 'eum',
                'fund_accounts' => [
                    [
                        'account_type' => 'bank_account',
                        'bank_account' => [
                            'ifsc' => 'SBIN0007105',
                            'bank_name' => 'State Bank of India',
                            'name' => 'test',
                            'notes' => [],
                            'account_number' => 'XX1000',
                        ],
                    ],
                ]
            ],
        ],
    ],

    'testGetContactWithTypeVendorAndPrivateAuth' => [
        'request'  => [
            'url'    => '/contacts/cont_1000000contact',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id'     => 'cont_1000000contact',
                'entity' => 'contact',
                'type'   => 'vendor',
            ],
        ],
    ],

    'testGetContactWithTypeVendorAndProxyAuth' => [
        'request'  => [
            'url'    => '/contacts/cont_1000000contact',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id'            => 'cont_1000000contact',
                'entity'        => 'contact',
                'type'          => 'vendor',
                'payment_terms' => 10,
                'tds_category'  => 1,
                'expense_id'    => '1',
                'vendor'        => [
                    'payment_terms'        => 10,
                    'tds_category'         => 1,
                    'gstin'                => '22AAAAA0000A1Z5',
                    'expense_id'           => '1',
                    'pan'                  => 'test_pan',
                    'vendor_portal_status' => 'INVITED',
                    'id'                   => '1',
                    'contact_id'           => 'cont_1000000contact'
                ]
            ],
        ],
    ],

    'testGetContactWithTypeVendorAndExternalServiceFailure' => [
        'request'  => [
            'url'    => '/contacts/cont_1000000contact',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id'            => 'cont_1000000contact',
                'entity'        => 'contact',
                'type'          => 'vendor',
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

    'testFetchContactsForXDemo' => [
        'request'  => [
            'url'    => '/contacts',
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
                        'name'   => 'Contact Y',
                    ],
                ],
            ],
        ],
    ],

    'testFetchContactsWithTypeVendorAndPrivateAuth' => [
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
                        'type'   => 'customer',
                    ],
                    [
                        'id'     => 'cont_1000001contact',
                        'entity' => 'contact',
                        'name'   => 'Contact X',
                        'type'   => 'vendor',
                    ],
                ],
            ],
        ],
    ],

    'testFetchContactsWithTypeVendorAndProxyAuth' => [
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
                        'type'   => 'customer',
                    ],
                    [
                        'id'            => 'cont_1000001contact',
                        'entity'        => 'contact',
                        'name'          => 'Contact X',
                        'type'          => 'vendor',
                        'payment_terms' => 10,
                        'tds_category'  => 1,
                        'gstin'         => '22AAAAA0000A1Z4',
                        'expense_id'    => '1',
                        'vendor'        => [
                            'payment_terms'        => 10,
                            'tds_category'         => 1,
                            'gstin'                => '22AAAAA0000A1Z4',
                            'expense_id'           => '1',
                            'pan'                  => 'test_pan',
                            'vendor_portal_status' => 'INVITED',
                            'id'                   => '2',
                            'contact_id'           => 'cont_1000001contact'
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testFetchContactsWithTypeVendorAndExternalServiceFailure' => [
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
                        'type'   => 'customer',
                    ],
                    [
                        'id'            => 'cont_1000001contact',
                        'entity'        => 'contact',
                        'name'          => 'Contact X',
                        'type'          => 'vendor',
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

    'testFetchContactsByEmailDifferentDomainSameName' => [
        'request'  => [
            'url'    => '/contacts?email=test@test.com',
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
                        'email'  => 'test@test.com',
                    ],
                ],
            ]
        ],
    ],

    'testFetchContactsWithEmailsFetchesExactMatchesOnly' => [
        'request' => [
            'url' => '/contacts?email=contact1@test.com',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'id' => 'cont_1000005contact',
                        'entity' => 'contact',
                        'name' => 'Contact1A',
                        'email' => 'contact1@test.com',
                    ],
                ],
            ],
        ],
    ],

    'testFetchContactsWithEmailsFetchesExactMatchesOnlyExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'contact_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'contact_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'=> [
                'bool'=> [
                    'filter'=> [
                        'bool'=> [
                            'must'=> [
                                [
                                    'term'=> [
                                        'email.raw'=> [
                                            'value'=> 'contact1@test.com'
                                        ]
                                    ]
                                ],
                                [
                                    'term'=> [
                                        'merchant_id'=> [
                                            'value'=> '10000000000000'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
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

    'testFetchContactsWithEmailsFetchesExactMatchesOnlyExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '1000005contact',
                ],
            ],
        ],
    ],

    'testContactsWithExpiredKey' => [
        'request'  => [],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED,
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'testCreateContactLiveModeNonKycActivatedNonCaActivated' => [
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
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_FOR_LIVE_REQUEST,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => PublicErrorCode::BAD_REQUEST_ERROR,
        ]
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

    'testCreateContactWithInvalidGstinProxyAuth' => [
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
                'gstin' => 'hello'
            ],
            'url'     => '/contacts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The gstin field is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateContactWithGstinProxyAuth' => [
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
                'gstin' => '22AAAAA0000A1Z5'
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
                'gstin' => '22AAAAA0000A1Z5'
            ],
            'status_code' => '201'
        ],
    ],

    'testCreateContactWithGstinInternalAuth' => [
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
                'gstin' => '22AAAAA0000A1Z5'
            ],
            'url'     => '/contacts_internal',
            'server'  =>  [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
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
                'gstin' => '22AAAAA0000A1Z5'
            ],
            'status_code' => '201'
        ],
    ],

    'testEditContactWithGstinProxyAuth' => [
        'request'  => [
            'content' => [
                'gstin' => '22AAAAA0000A1Z6'
            ],
            'url'     => '/contacts/cont_1',
            'method'  => 'PATCH'
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
                'gstin' => '22AAAAA0000A1Z6'
            ],
            'status_code' => '200'
        ],
    ],

    'testEditContactWithGstinInternalAuth' => [
        'request'  => [
            'content' => [
                'gstin' => '22AAAAA0000A1Z6'
            ],
            'url'     => '/contacts_internal/cont_1',
            'server'  =>  [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
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
                'gstin' => '22AAAAA0000A1Z6'
            ],
            'status_code' => '200'
        ],
    ],

    'testCreateContactWithNbsp' => [
        'request'  => [
            'content' => [
                'name'         => 'Tanmay Hospitality and Solution ',
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
                'name'         => 'Tanmay Hospitality and Solution',
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

    'testCreateContactWithApostrophe' => [
        'request'  => [
            'content' => [
                'name'         => 'Test’ company',
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
                'name'         => 'Test’ company',
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

    'testCreateContactWithCommaAndSlash' => [
        'request'  => [
            'content' => [
                'name'         => 'Test, com/pany/',
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
                'name'         => 'Test, com/pany/',
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

    'testCreateContactWithEnDash' => [
        'request'  => [
            'content' => [
                'name'         => 'Test – company',
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
                'name'         => 'Test – company',
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

    'testCreateContactWithTypeVendorAndPrivateAuth' => [
        'request'  => [
            'content' => [
                'name'          => 'Test / Contact',
                'type'          => 'vendor',
                'reference_id'  => '#123abc',
                'email'         => 'asd@abc.com',
                'contact'       => '9123456789',
                'payment_terms' => 10,
                'tds_category'  => 1,
                'gstin'        => '22AAAAA0000A1Z5',
                'pan'          => 'ABCD',
                'notes'         => [
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
                'type'         => 'vendor',
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

    'testCreateContactWithTypeVendorAndProxyAuth' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'vendor',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'payment_terms' => 10,
                'tds_category'  => 1,
                'gstin'        => '22AAAAA0000A1Z5',
                'pan'          => 'ABCD',

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
                'type'         => 'vendor',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'payment_terms' => 10,
                'tds_category'  => 1,
                'vendor'        => [
                    'payment_terms'        => 10,
                    'tds_category'         => 1,
                    'gstin'                => '22AAAAA0000A1Z5',
                    'expense_id'           => '1',
                    'pan'                  => 'test_pan',
                    'vendor_portal_status' => 'INVITED',
                    'id'                   => '1',
                    'contact_id'           => 'cont_xyz'
                ],
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'status_code' => '201'
        ],
    ],

    'testCreateContactWithTypeVendorExternalServiceFailure' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'vendor',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'payment_terms' => 10,
                'tds_category'  => 1,
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
                'type'         => 'vendor',
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

    'testCreateContactWithTypeVendorWithoutPaymentTerms' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'vendor',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'tds_category'  => 1,
                'gstin'        => '22AAAAA0000A1Z5',
                'pan'          => 'ABCD',
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
                'type'         => 'vendor',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'payment_terms' => 0,
                'tds_category'  => 1,
                'vendor'        => [
                    'payment_terms'        => 0,
                    'tds_category'         => 1,
                    'gstin'                => '22AAAAA0000A1Z5',
                    'pan'                  => 'test_pan',
                    'id'                   => '1',
                    'contact_id'           => 'cont_xyz'
                ],
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'status_code' => '201'
        ],
    ],


    'testCreateContactWithTypeVendorWithoutTdsCategory' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'vendor',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'payment_terms'=> 10,
                'gstin'        => '22AAAAA0000A1Z5',
                'pan'          => 'ABCD',
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
                'type'         => 'vendor',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'payment_terms' => 10,
                'tds_category'  => 0,
                'vendor'        => [
                    'payment_terms'        => 10,
                    'gstin'                => '22AAAAA0000A1Z5',
                    'pan'                  => 'test_pan',
                    'id'                   => '1',
                    'contact_id'           => 'cont_xyz'
                ],
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'status_code' => '201'
        ],
    ],


    'testCreateContactWithTypeVendorWithoutGstin' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'vendor',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'payment_terms' => 10,
                'tds_category'  => 1,
                'pan'          => 'ABCD',
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
                'type'         => 'vendor',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'payment_terms' => 10,
                'tds_category'  => 1,
                'vendor'        => [
                    'payment_terms'        => 10,
                    'tds_category'         => 1,
                    'gstin'                => null,
                    'pan'                  => 'test_pan',
                    'id'                   => '1',
                    'contact_id'           => 'cont_xyz'
                ],
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'status_code' => '201'
        ],
    ],


    'testCreateContactWithTypeVendorWithoutPan' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'vendor',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'payment_terms' => 10,
                'tds_category'  => 1,
                'gstin'        => '22AAAAA0000A1Z5',
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
                'type'         => 'vendor',
                'reference_id' => '#123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'payment_terms' => 10,
                'tds_category'  => 1,
                'vendor'        => [
                    'payment_terms'        => 10,
                    'tds_category'         => 1,
                    'gstin'                => '22AAAAA0000A1Z5',
                    'pan'                  => null,
                    'id'                   => '1',
                    'contact_id'           => 'cont_xyz'
                ],
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'status_code' => '201'
        ],
    ],

    'testCreateContactWithTypeVendorWithoutVendorDetails' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'vendor',
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
                'type'         => 'vendor',
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

    'testCreateContactWithProxyAuth' => [
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

    'testUpdateContactWithObserver' => [
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
            'query'=> [
                'bool'=> [
                    'filter'=> [
                        'bool'=> [
                            'must'=> [
                                [
                                    'term'=> [
                                        'email.raw'=> [
                                            'value'=> 'random@test.com'
                                        ]
                                    ]
                                ],
                                [
                                    'term'=> [
                                        'merchant_id'=> [
                                            'value'=> '10000000000000'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
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

    'testFetchContactsByEmailDifferentDomainSameNameExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'contact_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'contact_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'=> [
                'bool'=> [
                    'filter'=> [
                        'bool'=> [
                            'must'=> [
                                [
                                    'term'=> [
                                        'email.raw'=> [
                                            'value'=> 'test@test.com'
                                        ]
                                    ]
                                ],
                                [
                                    'term'=> [
                                        'merchant_id'=> [
                                            'value'=> '10000000000000'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
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


    'testFetchContactsByEmailDifferentDomainSameNameExpectedSearchResponse' => [
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
            'status_code' => '200'
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

    'testGetContactPublic' => [
        'request'  => [
            'url'    => '/contacts/cont_1000000contact/public',
            'method' => 'GET'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testCreateContactBulk' => [
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
                        'type'              => 'customer',
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
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
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
                    ]
                ]
            ],
        ],
    ],

    'testCreateContactBulkWithoutBatchId' => [
        'request'   => [
            'url'     => '/contacts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'   => 'bank_account',
                        'account_name'   => 'Sample rzp1',
                        'account_IFSC'   => 'SBIN0007106',
                        'account_number' => '1234567890',
                        'account_vpa'    => ''
                    ],
                    'contact' => [
                        'id'             => '',
                        'type'           => 'vendor',
                        'name'           => 'Test rzp1',
                        'email'          => 'sample@example.com',
                        'mobile'         => '9988998897',
                        'reference_id'   => ''
                    ],
                    'notes' => [
                        'code'           => 'abc123',
                        'place'          => 'Bangalore',
                        'state'          => 'Karnataka'
                    ],
                ],
            ],
        ],
        'response'  => [
            'content' => [
                'entity' => "collection",
                'count' => 1,
                'items' => [
                    [
                        'http_status_code' => 400,
                        'error' => [
                            'description' => "idempotency_key not present",
                            'code' => "BAD_REQUEST_ERROR"
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateContactBulkPrivateAuth' => [
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
                    'idempotency_key'       => 'batch_abc123',
                    'contact_id'            => 'cont_1000001contact'
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
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

    'testCreateContactWithTypeNull' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => null,
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
                'type'         => null,
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

    'testCreateContactWithTypeEmptyString' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => '',
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
                'type'         => null,
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

    'testUpdateContactWithEmptyString' => [
        'request'  => [
            'content' => [
                'type'         => '',
            ],
            'url'     => '/contacts/cont_1000000contact',
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'id'           => 'cont_1000000contact',
                'entity'       => 'contact',
                'type'         => null,
            ]
        ]
    ],

    'testUpdateContactWithNull' => [
        'request'  => [
            'content' => [
                'type'         => null,
                'email'        => "",
            ],
            'url'     => '/contacts/cont_1000000contact',
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'id'           => 'cont_1000000contact',
                'entity'       => 'contact',
                'type'         => null,
                'email'        => null,
            ]
        ]
    ],

    'testUpdateContactWithEmailNull' => [
        'request'  => [
            'content' => [
                'email'        => null,
            ],
            'url'     => '/contacts/cont_1000000contact',
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'id'           => 'cont_1000000contact',
                'entity'       => 'contact',
                'email'        => null,
            ]
        ]
    ],

    'testCreateContactWithIdempotencyKey' => [
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
                'idempotency_key'  => 'contact_test_123',
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

    'testCreateContactWithDuplicateIdempotencyKey' => [
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
                'idempotency_key'  => 'contact_test_123',
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

    'testFetchContactsById' => [
        'request'  => [
            'url'    => '/contacts/cont_1000002contact',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'contact',
                'id'     => 'cont_1000002contact',
                'email'  => 'random@test.com',
            ],
        ],
        'status_code' => '200'
    ],

    'testFetchContactWithGstinProxyAuth' => [
        'request'  => [
            'url'    => '/contacts/cont_1000002contact',
            'method' => 'GET'
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
                'gstin' => '22AAAAA0000A1Z5'
            ],
        ],
        'status_code' => '200'
    ],

    'testFetchContactWithGstinInternalAuth' => [
        'request'  => [
            'url'    => '/contacts_internal/cont_1000002contact',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
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
                'gstin' => '22AAAAA0000A1Z5'
            ],
        ],
        'status_code' => '200'
    ],

    'testCreateContactWithCustomType' => [
        'request'  => [
            'content' => [
                'name'         => 'Test / Contact',
                'type'         => 'Payouts to Mehul',
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
                'type'         => 'Payouts to Mehul',
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

    'testCreateContactWithUnnecessarySpacesTrimmedInNameAndType' => [
        'request'  => [
            'content' => [
                'name'         => '  Test / Contact   ',
                'type'         => 'self ',
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

    'testCreateContactWithUnnecessarySpacesTrimmedInNameAndTypeAndProxyAuth' => [
        'request'  => [
            'content' => [
                'name'         => '  Test / Contact   ',
                'type'         => 'self ',
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

    'testUpdateContactWithUnnecessarySpacesTrimmedInNameAndType' => [
        'request'  => [
            'content' => [
                'type'         => ' employee ',
                'name'         => 'name ',
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
                'name'         => 'name'
            ]
        ]
    ],

    'testAddCustomContactTypeWithSpacesTrimmed' => [
        'request'  => [
            'content' => [
                'type' => ' type'
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
                        'type' => "leading trailing",
                    ],
                ],
            ],
        ],
    ],

    'testAddCustomContactTypeThatAlreadyExistsTrimmedType' => [
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
                    'description' => 'Type \'%s\' is already defined and cannot be added.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateContactCheckType' => [
        'request'  => [
            'content' => [
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

    'testCreateContactWithoutNameNewApiError' => [
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
                    'reason'      => 'input_validation_failed',
                    'source'      => 'business',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateContactWithoutNameNewApiErrorOnLiveMode' => [
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
                    'reason'      => 'input_validation_failed',
                    'source'      => 'business',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateRZPFeesTypeContactNewApiError' => [
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
                    'reason'      => 'server_error',
                    'source'      => 'internal',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
        ],
    ],

    'testCreateRZPFeesTypeContactNewApiErrorOnLiveMode' => [
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
                    'reason'      => 'server_error',
                    'source'      => 'internal',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
        ],
    ],

    'testCreateRZPXpayrollTypeContact' => [
        'request'  => [
            'content' => [
                'name'         => 'Test Contact',
                'type'         => 'rzp_xpayroll',
                'reference_id' => '123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'url'     => '/contacts_internal',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'contact',
                'name'         => 'Test Contact',
                'type'         => 'rzp_xpayroll',
                'reference_id' => '123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'status_code' => '201'
        ],
    ],

    'testCreateRZPCapitalCollectionsTypeContact' => [
        'request'  => [
            'content' => [
                'name'         => 'Test Contact',
                'type'         => 'rzp_capital_collections'
            ],
            'url'     => '/contacts_internal',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'contact',
                'name'         => 'Test Contact',
                'type'         => 'rzp_capital_collections',
                'reference_id' => null,
                'email'        => null,
                'contact'      => null,
                'notes'        => []
            ],
            'status_code' => '201'
        ],
    ],

    'testCreateRZPCaptialCollectionsTypeContactByOtherInternalAppFailure' => [
        'request'  => [
            'content' => [
                'name'         => 'Test Contact',
                'type'         => 'rzp_capital_collections'
            ],
            'url'     => '/contacts_internal',
            'method'  => 'POST'
        ],

        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Creating/Updating an internal Razorpay Contact is not permitted',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
        ],
    ],

    'testCreateRZPXpayrolltypeContactByOtherInternalAppFailure' => [
        'request'  => [
            'content' => [
                'name'         => 'Test Contact',
                'type'         => 'rzp_xpayroll',
                'reference_id' => '123abc',
                'email'        => 'asd@abc.com',
                'contact'      => '9123456789',
                'notes'        => [
                    'test1' => 'One',
                ],
            ],
            'url'     => '/contacts_internal',
            'method'  => 'POST'
        ],

        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Creating/Updating an internal Razorpay Contact is not permitted',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
        ],
    ],

    'testTrimContactName' =>[
        'request'  => [
            'url'    => '',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id'     => '',
                'entity' => 'contact',
                'name' => 'test contact',
            ],
        ],
    ],

    'testTrimContactType' => [
        'request'  => [
            'url'    => '',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id'     => '',
                'entity' => 'contact',
                'name' => 'test contact',
                'type' => 'test type',
            ],
        ],
    ],
];
