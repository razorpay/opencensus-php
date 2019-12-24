<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

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
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_RECORDS_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
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
