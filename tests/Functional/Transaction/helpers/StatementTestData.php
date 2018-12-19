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
                'count'  => 2,
                'items'  => [
                    [
                        // 'id'         => '',
                        'entity'     => 'transaction',
                        'amount'     => 2500,
                        'credit'     => 2500,
                        'debit'      => 0,
                        'balance'    => 5000,
                        'source'     => [
                            // 'id'             => '',
                            'entity'         => 'bank_transfer',
                            'mode'           => 'NEFT',
                            // 'bank_reference' => '',
                            'amount'         => 2500,
                            'payer_name'     => null,
                            'payer_account'  => '7654321234567',
                            'payer_ifsc'     => 'HDFC0000001',
                        ],
                        // 'created_at' => ,
                        // 'updated_at' => ,
                    ],
                    [
                        // 'id'         => '',
                        'entity'     => 'transaction',
                        'amount'     => 2500,
                        'credit'     => 2500,
                        'debit'      => 0,
                        'balance'    => 2500,
                        'source'     => [
                            // 'id'             => '',
                            'entity'         => 'bank_transfer',
                            'mode'           => 'NEFT',
                            // 'bank_reference' => '',
                            'amount'         => 2500,
                            'payer_name'     => null,
                            'payer_account'  => '7654321234567',
                            'payer_ifsc'     => 'HDFC0000001',
                        ],
                        // 'created_at' => ,
                        // 'updated_at' => ,
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
                // 'id'         => '',
                'entity'     => 'transaction',
                'amount'     => 2500,
                'credit'     => 2500,
                'debit'      => 0,
                'balance'    => 2500,
                'source'     => [
                    // 'id'             => '',
                    'entity'         => 'bank_transfer',
                    'mode'           => 'NEFT',
                    // 'bank_reference' => '',
                    'amount'         => 2500,
                    'payer_name'     => null,
                    'payer_account'  => '7654321234567',
                    'payer_ifsc'     => 'HDFC0000001',
                ],
                // 'created_at' => ,
                // 'updated_at' => ,
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
];
