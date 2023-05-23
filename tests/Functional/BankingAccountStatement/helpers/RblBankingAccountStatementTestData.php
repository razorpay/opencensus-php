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
    'testRblAccountStatementFetchV2' => [
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


    'testRblAccountStatementV2ApiCase1' => [
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

    'testRblAccountStatementV2ApiIncorrectRequestDetails' => [
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
                    'code' => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR,
        ],
    ],

    'testRblAccountStatementV2ApiEmptyFieldInResponse' => [
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
            'message'             => 'The pstd date field is required.'
        ],
    ],

    'testRblAccountStatementV2ApiMissingFieldInResponse' => [
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
            'message'             => 'number of fields in a statement row not equal to 9'
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

    'testRblAccountStatementTxnMappingForRewardPayout' => [
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

    'testRblAccountStatementTxnMappingForRewardPayoutWithNewCreditsFlow' => [
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

    'testRblAccountStatementTxnMappingForMultipleRewardsPayout' => [
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

    'testRblAccountStatementTxnMappingForMultipleRewardsPayoutWithNewCreditsFlow' => [
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

    'testRblAccountStatementTxnMappingForLessRewardsAndBankingBalancePayout' => [
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

    'testRblAccountStatementTxnMappingForLessRewardsAndBankingBalancePayoutWithNewCreditsFlow'  => [
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
    'testStatementGenerationWithValidChannelAndFormat' => [
        'request' => [
            'method' => 'POST',
            'url' => '/banking_account_statement/generate',
            'content' => [
                'account_number' => '2224440041626905',
                'send_email' => '0',
                'format' => 'xlsx',
                'to_date' => '',
                'from_date' => '946684800',
                'channel' => 'rbl'
            ]
        ],
        'response' => [
            'content' => ['account_number' => '2224440041626905', 'send_email' => '0', 'format' => 'xlsx', 'from_date' => '946684800', 'channel' => 'rbl']
        ]
    ],

    'testStatementGenerationWithInvalidChannel' => [
        'request' => [
            'method' => 'POST',
            'url' => '/banking_account_statement/generate',
            'content' => [
                'account_number' => '2224440041626905',
                'send_email' => '0',
                'format' => 'xlsx',
                'to_date' => '',
                'from_date' => '946684800',
                'channel' => 'hdfc'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid channel: hdfc',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testStatementGenerationWithInvalidFormat' => [
        'request' => [
            'method' => 'POST',
            'url' => '/banking_account_statement/generate',
            'content' => [
                'account_number' => '2224440041626905',
                'send_email' => '0', 'format' => 'csv',
                'to_date' => '',
                'from_date' => '946684800',
                'channel' => 'rbl'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid format: csv',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRblAccountStatementWithInvalidTxnType' => [
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
            'class'               => 'RZP\Exception\IntegrationException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_INTEGRATION_ERROR,
        ],
    ],

    'testRblAccountStatementWithInvalidCategory' => [
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

    'testTransactionCreatedWebhookForSuccessfulMappingToPayout' => [
        'entity'   => 'event',
        'event'    => 'transaction.created',
        'contains' => [
            'transaction',
        ],
        'payload'  => [
            'transaction' => [
                'entity' => [
                    'entity' => 'transaction',
                    'source'   => [
                        'entity' => 'payout',
                        'status' => 'processed',
                    ],
                ],
            ],
        ],
     ],

    'testPayoutProcessedWebhookForSuccessfulMappingToPayout' => [
        'entity'   => 'event',
        'event'    => 'payout.processed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'processed',
                ],
            ],
        ],
    ],

    'testTransactionCreatedWebhookForSuccessfulMappingToExternal' => [
        'entity'   => 'event',
        'event'    => 'transaction.created',
        'contains' => [
            'transaction',
        ],
        'payload'  => [
            'transaction' => [
                'entity' => [
                    'entity' => 'transaction',
                    'source'   => [
                        'entity' => 'external',
                    ],
                ],
            ],
        ],
    ],

    'testTransactionCreatedWebhookForSuccessfulMappingToReversal' => [
        'entity'   => 'event',
        'event'    => 'transaction.created',
        'contains' => [
            'transaction',
        ],
        'payload'  => [
            'transaction' => [
                'entity' => [
                    'entity' => 'transaction',
                    'source'   => [
                        'entity' => 'reversal',
                    ],
                ],
            ],
        ],
    ],

    'testPayoutReversedWebhookForSuccessfulMappingToReversal' => [
        'entity'   => 'event',
        'event'    => 'payout.reversed',
        'contains' => [
            'payout',
        ],
        'payload'  => [
            'payout' => [
                'entity' => [
                    'entity' => 'payout',
                    'status' => 'reversed',
                ],
            ],
        ],
    ],

    'testStatementFetchDispatchUsingBASDetailsTable' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process/rbl',
            'content' => [],
        ],
        'response' => [
            'content' => ['accounts_processed' => ['2224440041626905', '2323230041626904', '2323230041626903']]
        ]

    ],

    'testStatementFetchDispatchUsingBASDetailsTablePoolAccounts' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process/rbl',
            'content' => [
                "account_type" => "shared"
            ],
        ],
        'response' => [
            'content' => ['accounts_processed' => ['2323230041626906']]
        ]

    ],

    'testLimitForEightHourRuleAndAccountsThatMadePayoutsForBASFetch' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process/rbl',
            'content' => [],
        ],
        'response' => [
            'content' => ['accounts_processed' => ['2323230041626903', '2323230041626904', '2323230041626905',
                '2323230041626906', '2323230041626908', '2323230041626909']]
        ]

    ],

    'testLimitForAccountsThatMadePayoutsAndOtherAccountsForBASFetch' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process/rbl',
            'content' => [],
        ],
        'response' => [
            'content' => ['accounts_processed' => ['2323230041626905', '2323230041626910', '2323230041626901',
                '2323230041626902', '2323230041626903', '2323230041626904']]
        ]
    ],

    'testRblSourceUpdateInvalidStateTransitionFromProcessedToFailed' => [
        'request' => [
            'url'     => '/banking_account_statement/source/update/validate',
            'method'  => 'POST',
            'content' => [
                'payout_id'    => 'randomid',
                'debit_bas_id' => '100000000000po',
                'end_status'   => 'failed'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected end status is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testRblSourceUpdateInvalidStateTransitionFromFailedToProcessed' => [
        'request' => [
            'url'     => '/banking_account_statement/source/update/validate',
            'method'  => 'POST',
            'content' => [
                'payout_id'    => 'randomid',
                'debit_bas_id' => '100000000000po',
                'end_status'   => 'processed'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Status change not permitted',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testGatewayBalanceCreateInBASDetailsTable' => [
        'request' => [
            'method'  => 'put',
            'url'     => '/banking_accounts/gateway/rbl/balance',
            ],
        'response' => [
            'content' => []
        ]
    ],

    'testCreateBASDetailsTable' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/banking_account_statement/details',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCreateBASDetailsTableWithInvalidAccountType' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/banking_account_statement/details',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Invalid account_type: escrow',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRblSlackAlertThrownForRecon' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'FAILED',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1234567,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => '10000000000000',
                'source_type'         => 'payout',
                'status'              => 'processed',
                'source_account_id'   => 111111111,
                'bank_account_type'   => 'current',
                'channel'             => 'rbl',
            ]
        ],
        'response'  => [
            'status_code' => 500,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ]
        ],
        'exception' => [
            'class'               => Rzp\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
            'message'             => 'Failed payout has a corresponding BAS entity. This should be reversed instead, not failed.'
        ],
    ],

    'testPayoutReversedWhenDebitExistsAndCreditIsProcessedAfterFTSUpdateForCurrentAccount' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => [
                'fund_transfer_id'    => 1234567,
                'source_id'           => '10000000000000',
                'source_type'         => 'payout',
                'status'              => 'failed',
            ]
        ],
        'response'  => [
            'status_code' => 500,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ]
        ],
        'exception' => [
            'class'               => Rzp\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
            'message'             => 'Failed payout has a corresponding BAS entity. This should be reversed instead, not failed.'
        ],
    ],

    'testRblMissingAccountStatement' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/fetch_missing/rbl',
            'content' => [
                'account_number' => '2224440041626905',
                'from_date'      => 1656786600,
                'to_date'        => 1656829799,
                'save_in_redis'  => true,
            ],
        ],
        'response' => [
            'content' => [
                'expected_attempts' => 1,
                'dispatched'        => 'success'
            ]
        ]
    ],

    'testOptimiseInsertRblMissingAccountStatement' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/insert_missing',
            'content' => [
                'account_number' => '2224440041626905',
                'channel'        => 'rbl',
                'action'         => 'insert',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testInsertRblMissingAccountStatement' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/insert_missing',
            'content' => [
                'account_number' => '2224440041626905',
                'channel'        => 'rbl',
                'action'         => 'insert',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testE2ERblAutomatedReconFlow' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/cron/automate_recon/rbl',
            'content' => [
                'account_numbers' => [
                    '2224440041626905'
                ],
                'save_in_redis'   => true,
                'new_cron_setup'  => true,
            ],
        ],
        'response' => [
            'content' => [
                '2224440041626905' => [
                    'fetch_missing_statement' => 'success'
                ]
            ]
        ]
    ],

    'testRblMissingStatementUpdateTriggerAction' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/update_missing/rbl',
            'content' => [
                'account_numbers' => [
                    '2224440041626905'
                ],
            ],
        ],
        'response' => [
            'content' => [
                '2224440041626905' => [
                    'update_missing_statement' => 'success'
                ]
            ]
        ]
    ],

    'testRblAutomatedReconForMissingStatementsForGivenRange' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/cron/automate_recon/rbl',
            'content' => [
                'account_numbers' => [
                    '2224440041626905'
                ],
                'save_in_redis'   => true,
                'from_date'       => '1683225000',
                'to_date'         => '1683268199',
                'new_cron_setup'  => true,
            ],
        ],
        'response' => [
            'content' => [
                '2224440041626905' => [
                    'fetch_missing_statement' => 'success'
                ]
            ]
        ]
    ],

    'testRblAutomatedReconWithMismatchedChannel' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/cron/automate_recon/icici',
            'content' => [
                'account_numbers' => [
                    '2224440041626905'
                ],
                'save_in_redis'   => true,
                'from_date'       => '1683225000',
                'to_date'         => '1683268199',
                'new_cron_setup'  => true,
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testRblAutomatedReconWithAccountsWithLastReconciledAt' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/cron/automate_recon/rbl',
            'content' => [
                'account_numbers' => [],
                'save_in_redis'   => true,
                'new_cron_setup'  => true,
            ],
        ],
        'response' => [
            'content' => [
                '2224440041626906' => [
                    'fetch_missing_statement' => 'success'
                ]
            ]
        ]
    ],

    'testRblAutomatedReconWithPriorityAccountNumbers' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/cron/automate_recon/rbl',
            'content' => [
                'account_numbers' => [],
                'save_in_redis'   => true,
                'new_cron_setup'  => true,
                'recon_limit'     => 6,
            ],
        ],
        'response' => [
            'content' => [
                '2224440041626906' => [
                    'fetch_missing_statement' => 'success'
                ]
            ]
        ]
    ],

    'testRblMissingAccountStatementInsertAsync' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/cron/insert_missing/rbl',
            'content' => [

                'account_number'  => '2224440041626905',
                'action'          => 'insert'
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testfetchRblMissingAccountStatementWithInvalidDateRange' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/fetch_missing/rbl',
            'content' => [
                'account_number' => '2224440041626905',
                'from_date'      => 1656686600,
                'to_date'        => 1657986600,
                'save_in_redis'  => true,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Given date range exceeds the threshold of 7 days.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Rzp\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRblMissingAccountStatementDetection' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/detect_missing/rbl',
            'content' => [
                'account_numbers'              => ['2224440041626905'],
                'suspected_mismatch_timestamp' => 1660501800,
            ],
        ],
        'response' => [
            'content' => [
                'channel'                      => 'rbl',
                'account_numbers'              => ['2224440041626905'],
                'suspected_mismatch_timestamp' => '1660501800',
            ]
        ]
    ],

    'testRblMissingAccountStatementPushesCurrentTimestampWhenStartTimeIsNotPassed' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/detect_missing/rbl',
            'content' => [
                'account_numbers' => ['2224440041626905'],
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],
];
