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

    'testRblAccountStatementCase7' => [
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
            'message'             => 'The PayGenRes.Body.transactionDetails.1.txnBalance.amountValue must be at least 0.'
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
];
