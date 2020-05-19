<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testRblXlsxStatementGeneration' => [
        'request' => [
            'method' => 'POST',
            'url' => '/banking_account_statement/generate',
            'content' => ['account_number' => '2224440041626905', 'send_email' => '0', 'format' => 'xlsx', 'to_date' => '', 'from_date' => '946684800', 'channel' => 'rbl']
        ],
        'response' => [
            'content' => ['account_number' => '2224440041626905', 'send_email' => '0', 'format' => 'xlsx', 'from_date' => '946684800', 'channel' => 'rbl']
        ]
    ],
    'testRblXlsxStatementEmailSent' => [
        'request' => [
            'method' => 'POST',
            'url' => '/banking_account_statement/generate',
            'content' => ['account_number' => '2224440041626905', 'send_email' => '1', 'to_emails' => ['test@razorpay.com'], 'format' => 'xlsx', 'from_date' => '946684800', 'to_date' => '', 'channel' => 'rbl']
        ],
        'response' => [
            'content' => ['account_number' => '2224440041626905', 'send_email' => '1', 'format' => 'xlsx', 'from_date' => '946684800', 'channel' => 'rbl']
        ]
    ],
    'testRblAccountStatementCase1' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'rbl',
            ],
        ],
        'response' => [
            'content' => [
                'account_number' => '2224440041626905',
                'channel'        => 'rbl'
            ],
        ],
    ],

    'testRblAccountStatementCase2' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'rbl',
            ],
        ],
        'response' => [
            'content' => [
                'account_number' => '2224440041626905',
                'channel'        => 'rbl'
            ],
        ],
    ],

    'testRblAccountStatementCase3' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'rbl',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR,
        ],
    ],

    'testRblAccountStatementCase4' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'rbl',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR,
        ],
    ],

    'testRblAccountStatementCase5' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'rbl',
            ],
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
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'message'             => 'The PayGenRes.Body.transactionDetails.0.pstdDate field is required.'
        ],
    ],

    'testRblAccountStatementCase6' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'rbl',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_BANKING_ACCOUNT_STATEMENT_BALANCES_DO_NOT_MATCH,
            'message'             => 'Balance at channel does not match with our balance',
        ],
    ],

    'testRblAccountStatementTxnMappingCase1' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'rbl',
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testLastFetchedAtWhenNewDataIsPresent' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/balances',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testLastFetchedAtWhenNewDataIsNotPresent' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/balances',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testLastFetchedAtEqualsBalanceUpdatedAtInitially' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/balances',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testRblAccountStatementNegativeBalance' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'rbl',
            ],
        ],
        'response'  => [
            'content' => []
        ],
    ],

    'testRblAccountStatementNegativeBalanceWithExternalSource' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'rbl',
            ],
        ],
        'response'  => [
            'content' => []
        ],
    ],

    'testRblAccountStatementNegativeBalanceWithSourceReversal' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'rbl',
            ],
        ],
        'response'  => [
            'content' => []
        ],
    ],

    'testRblAccountStatementWhenNegativeBalanceExceedsMaxLimit' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'rbl',
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Negative Balance has crossed the negative limit threshold',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED,
            'message'             => 'Negative Balance has crossed the negative limit threshold',
        ]
     ],

    'testCreateRblPayoutWhenBalanceFetchCronRunsBeforeBankingAccountStatementCron' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 10000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true,
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => "payout",
                'fund_account_id' => "fa_100000000000fa",
                'amount'          => 10000,
                'currency'        => "INR",
                'notes'           => [
                    'abc' => "xyz",
                ],
                'fees'            => 590,
                'tax'             => 90,
                'status'          => "processing",
                'purpose'         => "refund",
                'utr'             => null,
                'mode'            => "IMPS",
                'reference_id'    => null,
                'narration'       => "Batman",
                'batch_id'        => null,
                'failure_reason'  => null,
            ]
        ]
    ],

    'testCreateRblPayoutWhenBalanceFetchCronRunsAfterBankingAccountStatementCron' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 10000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true,
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => "payout",
                'fund_account_id' => "fa_100000000000fa",
                'amount'          => 10000,
                'currency'        => "INR",
                'notes'           => [
                    'abc' => "xyz",
                ],
                'fees'            => 590,
                'tax'             => 90,
                'status'          => "processing",
                'purpose'         => "refund",
                'utr'             => null,
                'mode'            => "IMPS",
                'reference_id'    => null,
                'narration'       => "Batman",
                'batch_id'        => null,
                'failure_reason'  => null,
            ]
        ]
    ],

    'testCreateRblPayoutWhenBalanceFetchCronRunsAfterBankingAccountStatementCronWithLowBalance' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 10000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true,
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => "payout",
                'fund_account_id' => "fa_100000000000fa",
                'amount'          => 10000,
                'currency'        => "INR",
                'notes'           => [
                    'abc' => "xyz",
                ],
                'fees'            => 0,
                'tax'             => 0,
                'status'          => "queued",
                'purpose'         => "refund",
                'utr'             => null,
                'mode'            => "IMPS",
                'reference_id'    => null,
                'narration'       => "Batman",
                'batch_id'        => null,
                'failure_reason'  => null,
            ]
        ]
    ],

    'testCreateRblPayoutWhenBalanceFetchCronRunsBeforeBankingAccountStatementCronWithLowBalance' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 12000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true,
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => "payout",
                'fund_account_id' => "fa_100000000000fa",
                'amount'          => 12000,
                'currency'        => "INR",
                'notes'           => [
                    'abc' => "xyz",
                ],
                'fees'            => 0,
                'tax'             => 0,
                'status'          => "queued",
                'purpose'         => "refund",
                'utr'             => null,
                'mode'            => "IMPS",
                'reference_id'    => null,
                'narration'       => "Batman",
                'batch_id'        => null,
                'failure_reason'  => null,
            ]
        ]
    ],

    'testFetchStatementByTransactionIdForRbl' => [
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

    'testAccountStatementFetchWithTwoPayoutsWithSameCmsRefNoForIFT' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'rbl',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::SERVER_ERROR,
                    'description' => 'The server encountered an error. The incident has been reported to admins.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\LogicException',
            'internal_error_code' => 'SERVER_ERROR_TOO_MANY_PAYOUTS_FOUND_VIA_CMS_REF_NO_FOR_IFT',
        ],
    ],

    'testAccountStatementFetchWithTwoPayoutsWithSameCmsRefNoForNonIFT' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'rbl',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::SERVER_ERROR,
                    'description' => 'The server encountered an error. The incident has been reported to admins.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\LogicException',
            'internal_error_code' => 'SERVER_ERROR_TOO_MANY_PAYOUTS_FOUND_VIA_CMS_REF_NO_FOR_NON_IFT',
        ],
    ],
];
