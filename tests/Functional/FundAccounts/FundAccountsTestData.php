<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testGetFundAccounts' => [
        'request'  => [
            'url'    => '/fund_accounts/fa_100000000000fa',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id'           => 'fa_100000000000fa',
                'entity'       => 'fund_account',
                'active'       => true,
                'account_type' => 'bank_account',
            ],
        ],
    ],

    'testFetchFundAccounts' => [
        'request'  => [
            'url'    => '/fund_accounts',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'id'           => 'fa_100000000003fa',
                        'entity'       => 'fund_account',
                        'active'       => true,
                        'account_type' => 'vpa',
                        'details'      => [
                        ],
                    ],
                    [
                        'id'           => 'fa_100000000002fa',
                        'entity'       => 'fund_account',
                        'active'       => true,
                        'account_type' => 'bank_account',
                        'details'      => [
                            'ifsc'           => 'RZPB0000000',
                            'account_number' => '10010101011',
                        ],
                    ],
                    [
                        'id'           => 'fa_100000000001fa',
                        'entity'       => 'fund_account',
                        'active'       => true,
                        'account_type' => 'bank_account',
                        'details'      => [
                            'ifsc'           => 'RZPB0000000',
                            'account_number' => '10010101011',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateFundAccountInactiveContact' => [
        'request'   => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Fund accounts cannot be created on an inactive contact',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountBankAccount' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountBankAccountBeneficiaryVerified' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountBankAccountBeneficiaryFailed' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_invalidcontact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_invalidcontact',
                'details'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
        ],
    ],

    'testCreateVpa' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'address' => 'amitm@upi',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'address' => 'amitm@upi',
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateCard' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card' => [
                    'name' => 'shk',
                    'number' => '4111111111111111',
                    'expiry_month' => 4,
                    'expiry_year' => 2025
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateCardBeneficiaryVerified' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card' => [
                    'name' => 'shk',
                    'number' => '4111111111111111',
                    'expiry_month' => 4,
                    'expiry_year' => 2025
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateCardBeneficiaryFailed' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_invalidcontact',
                'card' => [
                    'name' => 'shk',
                    'number' => '4111111111111111',
                    'expiry_month' => 4,
                    'expiry_year' => 2025
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_invalidcontact',
                'details'      => [
                ],
            ],
            'status_code' => 201
        ],
    ],


    'testCreateCardAndVpa' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card' => [
                    'name' => 'shk',
                    'number' => '4111111111111111',
                    'expiry_month' => 4,
                    'expiry_year' => 2025
                ],
                'vpa' => []
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,

                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateWithoutContactOrCustomer' => [
        'request'   => [
            'content' => [
                'account_type' => 'vpa',
                'details'      => [
                    'address' => 'amitm@upi',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The contact id field is required when customer id is not present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountInvalidVpa' => [
        'request'   => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'address' => 'amitm',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid VPA. Please enter a valid Virtual Payment Address',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        ],
    ],

    'testCreateFundAccountInvalidBankIfsc' => [
        'request'   => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'ifsc'           => 'SBIQ0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid IFSC Code in Bank Account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFromCustomer' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'customer_id'  => 'cust_1000facustomer',
                'details'      => [
                    'address' => 'amitm@upi',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'vpa',
                'customer_id'  => 'cust_1000facustomer',
                'details'      => [
                    'address' => 'amitm@upi',
                ],
            ],
        ],
    ],

    'testUpdateFundAccount' => [
        'request'  => [
            'content' => [
                'active' => '0'
            ],
            'url'     => '/fund_accounts/fa_100000000000fa',
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'id'     => 'fa_100000000000fa',
                'entity' => 'fund_account',
                'active' => false
            ],
        ]
    ],

    'testDeleteFundAccount' => [
        'request'  => [
            'url'    => '/fund_accounts/fa_100000000000fa',
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

    'testBulkFundAccount' => [
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

    'testBulkFundAccountWithInvalidContactId' => [
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

    'testBulkFundAccountWithValidContactId' => [
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

    'testBulkFundAccountWithSameContact' => [
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
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'type'              => 'vendor',
                        'name'              => 'Test rzp1',
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
                        'details' => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp2',
                            'account_number'    => '1234567890',
                        ],
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp2',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ]
                ]
            ],
        ],
    ],
    'testBulkFundAccountWithSameFundAccount' => [
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
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'type'              => 'vendor',
                        'name'              => 'Test rzp1',
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
    'testBulkFundAccountWithSameIdempotencyKey' => [
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

    'testDuplicateFundAccountCreationOnApiForBankAccount' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                     ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testCreateSingleCharacterHandleOfVpa' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'address' => 'a@upi',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'address' => 'a@upi',
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testDuplicateFundAccountCreationOnApiForVpa' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'address' => 'amitm@upi',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'address' => 'amitm@upi',
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testCreateVpaWithDot' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'address' => 'a.mitm@upi',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'address' => 'a.mitm@upi',
                ],
            ],
            'status_code' => 201
        ]
    ],

    'testDuplicateFundAccountCreationOnDashboardForVpa' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testDuplicateFundAccountCreationOnDashboardForBankAccount' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'details'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ]

];
