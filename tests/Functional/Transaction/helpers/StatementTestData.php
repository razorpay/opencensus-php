<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\InvalidArgumentException;

return [
    'testFetchMultipleStatements' => [
        'request' => [
            'url'    => '/transactions',
            'method' => 'get',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 4,
                'items'  => [
                    [
                        // 'id'             => '',
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1590,
                        'currency'       => 'INR',
                        'credit'         => 1590,
                        'debit'          => 0,
                        'balance'        => 105000,
                        'source'         => [
                            // 'id'             => '',
                            'entity'         => 'reversal',
                            'amount'         => 1590,
                            'currency'       => 'INR',
                        ],
                        // 'created_at'     => ,
                    ],
                    [
                        // 'id'             => '',
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1590,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1590,
                        'balance'        => 103410,
                        'source'         => [
                            // 'id'             => '',
                            'entity'         => 'payout',
                        ],
                        // 'created_at'     => ,
                    ],
                    [
                        // 'id'             => '',
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 2500,
                        'currency'       => 'INR',
                        'credit'         => 2500,
                        'debit'          => 0,
                        'balance'        => 105000,
                        'source'         => [
                            // 'id'             => '',
                            'entity'         => 'bank_transfer',
                            'mode'           => 'NEFT',
                            // 'bank_reference' => '',
                            'amount'         => 2500,
                            'payer_name'     => null,
                            'payer_account'  => '7654321234567',
                            'payer_ifsc'     => 'HDFC0000001',
                        ],
                        // 'created_at'     => ,
                    ],
                    [
                        // 'id'             => '',
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 2500,
                        'currency'       => 'INR',
                        'credit'         => 2500,
                        'debit'          => 0,
                        'balance'        => 102500,
                        'source'         => [
                            // 'id'             => '',
                            'entity'         => 'bank_transfer',
                            'mode'           => 'NEFT',
                            // 'bank_reference' => '',
                            'amount'         => 2500,
                            'payer_name'     => null,
                            'payer_account'  => '7654321234567',
                            'payer_ifsc'     => 'HDFC0000001',
                        ],
                        // 'created_at'     => ,
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleStatementsForBanking' => [
        'request' => [
            'url'    => '/transactions_banking',
            'method' => 'get',
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
            'content' => [
                'count' => 10,
                'skip'  => 0,
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 4,
                'items'  => [
                    [
                        // 'id'             => '',
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1590,
                        'currency'       => 'INR',
                        'credit'         => 1590,
                        'debit'          => 0,
                        'balance'        => 105000,
                        'source'         => [
                            // 'id'             => '',
                            'entity'         => 'reversal',
                            'amount'         => 1590,
                            'currency'       => 'INR',
                        ],
                        // 'created_at'     => ,
                    ],
                    [
                        // 'id'             => '',
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1590,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1590,
                        'balance'        => 103410,
                        'source'         => [
                            // 'id'             => '',
                            'entity'         => 'payout',
                        ],
                        // 'created_at'     => ,
                    ],
                    [
                        // 'id'             => '',
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 2500,
                        'currency'       => 'INR',
                        'credit'         => 2500,
                        'debit'          => 0,
                        'balance'        => 105000,
                        'source'         => [
                            // 'id'             => '',
                            'entity'         => 'bank_transfer',
                            'mode'           => 'NEFT',
                            // 'bank_reference' => '',
                            'amount'         => 2500,
                            'payer_name'     => null,
                            'payer_account'  => '7654321234567',
                            'payer_ifsc'     => 'HDFC0000001',
                        ],
                        // 'created_at'     => ,
                    ],
                    [
                        // 'id'             => '',
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 2500,
                        'currency'       => 'INR',
                        'credit'         => 2500,
                        'debit'          => 0,
                        'balance'        => 102500,
                        'source'         => [
                            // 'id'             => '',
                            'entity'         => 'bank_transfer',
                            'mode'           => 'NEFT',
                            // 'bank_reference' => '',
                            'amount'         => 2500,
                            'payer_name'     => null,
                            'payer_account'  => '7654321234567',
                            'payer_ifsc'     => 'HDFC0000001',
                        ],
                        // 'created_at'     => ,
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleStatementsWithMerchantRules' => [
        'request' => [
            'url'    => '/transactions',
            'method' => 'get',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'items'  => [
                    [
                        // 'id'             => '',
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 2500,
                        'currency'       => 'INR',
                        'credit'         => 2500,
                        'debit'          => 0,
                        'balance'        => 105000,
                        'source'         => [
                            // 'id'             => '',
                            'entity'         => 'bank_transfer',
                            'mode'           => 'NEFT',
                            // 'bank_reference' => '',
                            'amount'         => 2500,
                            'payer_name'     => null,
                            'payer_account'  => '7654321234567',
                            'payer_ifsc'     => 'HDFC0000001',
                        ],
                        // 'created_at'     => ,
                    ],
                    [
                        // 'id'             => '',
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 2500,
                        'currency'       => 'INR',
                        'credit'         => 2500,
                        'debit'          => 0,
                        'balance'        => 102500,
                        'source'         => [
                            // 'id'             => '',
                            'entity'         => 'bank_transfer',
                            'mode'           => 'NEFT',
                            // 'bank_reference' => '',
                            'amount'         => 2500,
                            'payer_name'     => null,
                            'payer_account'  => '7654321234567',
                            'payer_ifsc'     => 'HDFC0000001',
                        ],
                        // 'created_at'     => ,
                    ],
                ],
            ],
        ],
    ],

    'testFetchStatement'          => [
        'request' => [
            'url'    => '/transactions/txn_00000000000001',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                // 'id'             => '',
                'entity'         => 'transaction',
                'account_number' => '2224440041626905',
                'amount'         => 2500,
                'currency'       => 'INR',
                'credit'         => 2500,
                'debit'          => 0,
                'balance'        => 102500,
                'source'         => [
                    // 'id'             => '',
                    'entity'         => 'bank_transfer',
                    'mode'           => 'NEFT',
                    // 'bank_reference' => '',
                    'amount'         => 2500,
                    'payer_name'     => null,
                    'payer_account'  => '7654321234567',
                    'payer_ifsc'     => 'HDFC0000001',
                ],
                // 'created_at'     => ,
            ],
        ],
    ],


    'testFetchStatementWithAttributesPermissionTrue'          => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
            'url'    => '/transactions/txn_00000000000001',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity'         => 'transaction',
                'account_number' => '2224440041626905',
                'amount'         => 2500,
                'currency'       => 'INR',
                'credit'         => 2500,
                'debit'          => 0,
                'balance'        => 102500,
                'source'         => [
                    'entity'         => 'bank_transfer',
                    'mode'           => 'NEFT',
                    'amount'         => 2500,
                    'payer_name'     => null,
                    'payer_account'  => '7654321234567',
                    'payer_ifsc'     => 'HDFC0000001',
                ],
            ],
        ],
    ],

    'testFetchStatementBankingWithAttributesPermissionTrue'          => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
            'url'    => '/transactions_banking',
            'method' => 'get',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'items'  => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 2500,
                        'currency'       => 'INR',
                        'credit'         => 2500,
                        'debit'          => 0,
                        'balance'        => 102500,
                        'source'         => [
                            'entity'         => 'bank_transfer',
                            'mode'           => 'NEFT',
                            'amount'         => 2500,
                            'payer_name'     => null,
                            'payer_account'  => '7654321234567',
                            'payer_ifsc'     => 'HDFC0000001',
                            ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchStatementBankingOnVendorPaymentsAuth'          => [
        'request' => [
            'server' => [
                'HTTP_X-Razorpay-Account'  =>  '10000000000000',
            ],
            'url'    => '/transactions_banking_internal',
            'method' => 'get',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'items'  => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 2500,
                        'currency'       => 'INR',
                        'credit'         => 2500,
                        'debit'          => 0,
                        'balance'        => 102500,
                        'source'         => [
                            'entity'         => 'bank_transfer',
                            'mode'           => 'NEFT',
                            'amount'         => 2500,
                            'payer_name'     => null,
                            'payer_account'  => '7654321234567',
                            'payer_ifsc'     => 'HDFC0000001',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchStatementWithNoAttributes'          => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
            'url'    => '/transactions/txn_00000000000001',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity'         => 'transaction',
                'account_number' => '2224440041626905',
                'amount'         => 2500,
                'currency'       => 'INR',
                'credit'         => 2500,
                'debit'          => 0,
                'balance'        => 102500,
                'source'         => [
                    'entity'         => 'bank_transfer',
                    'mode'           => 'NEFT',
                    'amount'         => 2500,
                    'payer_name'     => null,
                    'payer_account'  => '7654321234567',
                    'payer_ifsc'     => 'HDFC0000001',
                ],
            ],
        ],
    ],

    'testGetBillingLabelWithMerchantAttributesTrueForTransactionFetch' => [
        'request'  => [
            'url'     => '/merchants/billing_label/suggestions',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'Test Name Private Limited Ltd Ltd. Liability Partnership',
                'TEST NAME PRIVATE LIMITED LTD LTD. LIABILITY PARTNERSHIP',
                'Test Name',
                'https://shopify.secondleveldomain.edu.in',
                'secondleveldomain',
                'SECONDLEVELDOMAIN',
                'Secondleveldomain',
                'secondleveldomain.edu.in',
            ]
        ]
    ],

    'testFetchStatementWithAttributesPermissionFalse'          => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
            'url'    => '/transactions/txn_00000000000001',
            'method' => 'get',
        ],
        'response'  => [
            'content' => [
                'error'       => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testFetchStatementWithNoAttributesWithOperationsRole'          => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin'    =>  'https://x.razorpay.com',
            ],
            'url'    => '/transactions/txn_00000000000001',
            'method' => 'get',
        ],
        'response'  => [
            'content' => [
                'error'       => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testFetchStatementForPayoutFromLedger' => [
        'request' => [
            'url'    => '/transactions/txn_00000000000001',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity'         => 'transaction',
                'account_number' => '2224440041626905',
                'amount'         => 1590,
                'currency'       => 'INR',
                'credit'         => 0,
                'debit'          => 1590,
                'balance'        => 98410,
                'source'         => [
                    'entity'         => 'payout',
                    'amount'         => 1000,
                ],
            ],
        ],
    ],

    'testFetchStatementForFailedFavFromLedger' => [
        'request'  => [
            'url'    => '/transactions/txn_00000000000001',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'entity'         => 'transaction',
                'account_number' => '2224440041626905',
                'amount'         => 354,
                'currency'       => 'INR',
                'credit'         => 354,
                'debit'          => 0,
                'balance'        => 98410,
                'source'         => [
                    'entity'   => 'reversal',
                    'amount'   => 0,
                    'fee'      => 354,
                    'tax'      => 54,
                    'currency' => 'INR',
                ],
            ],
        ],
    ],

    'testFetchMultipleStatementsWithIncorrectAccountNumberParameter' => [
        'request' => [
            'url'    => '/transactions',
            'method' => 'get',
            'content' => [
                'account_number' => '1234567890',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_RAZORPAYX_ACCOUNT_NUMBER_IS_INVALID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_RAZORPAYX_ACCOUNT_NUMBER_IS_INVALID,
        ],
    ],

    'testFetchMultipleStatementsWithoutAccountNumberParameter' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account number field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchByContactId' => [
         'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchByPayoutId' => [
         'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchByUtr' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'amount'         => 1590,
                        'currency'       => 'INR',
                        'credit'         => 0,
                        'debit'          => 1590,
                        'balance'        => 98410,
                        'source'         => [
                            'entity'         => 'payout',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByUtrBankTransfer' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'         => 'transaction',
                        'account_number' => '2224440041626905',
                        'currency'       => 'INR',
                        'source'         => [
                            'entity'         => 'bank_transfer',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchByType' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],


    'testFetchByInvalidType' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\InvalidArgumentException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        ],
    ],

    'testFetchByInvalidPaymentType' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid banking transaction type : settlement',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchByContactName' => [
         'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchByContactNameExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'transaction_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'contact_name' => [
                                    'query'                =>'test user',
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
                                        'balance_id' => [
                                            'value' => 'BfCGvMZswckZl8',
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

    'testFetchByContactNameExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByContactEmail' => [
         'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchByContactEmailExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '00000000000001',
                ],
            ],
        ],
    ],

    'testFetchByContactPhone' => [
         'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchByFundAccountId' => [
         'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFAVBankAccountTransaction' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFAVFetchStatement' => [
        'request' => [
            'url'    => '/transactions/txn_00000000000001',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testActionFilter' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testActionFilterFailedPrivateAuth' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/transactions',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'action is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],
];
