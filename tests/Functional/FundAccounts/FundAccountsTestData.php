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
                    'description' => 'Invalid Address: amitm',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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
];
