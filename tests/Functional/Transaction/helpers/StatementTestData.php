<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testFetchMultipleStatementsOnBankingBalance' => [
        'request' => [
            'url'    => '/transactions',
            'method' => 'get',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        // 'id'         => '',
                        'entity'     => 'statement',
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
                        'entity'     => 'statement',
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

    'testFetchMultipleStatementsOnPrimaryBalance' => [
        'request' => [
            'url'    => '/transactions',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        // 'id'         => '',
                        'entity'     => 'statement',
                        'amount'     => 50000,
                        'credit'     => 49000,
                        'debit'      => 0,
                        'balance'    => 1049000,
                        // 'source'     => [
                        //     // 'id'     => '',
                        //     'entity' => 'payment',
                        // ],
                        // 'created_at' => ,
                        // 'updated_at' => ,
                    ],
                ],
            ],
        ],
    ],

    'testFetchStatementOnBankingBalance'          => [
        'request' => [
            'url'    => '/transactions/txn_00000000000001',
            'method' => 'get',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                // 'id'         => '',
                'entity'     => 'statement',
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

    'testFetchIncorrectStatementOnBankingBalance' => [
        'request' => [
            'url'    => '/transactions/txn_00000000000001',
            'method' => 'get',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],
];
