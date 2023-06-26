<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\FundTransfer;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;


return [
    'testIciciAccountStatementCase1' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process/icici',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'accounts_processed' => ['2224440041626905']
            ],
        ],
    ],

    'testIciciDisableAccountStatementFetch' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process/icici',
            'content' => [
                'blacklist_fetch' => true,
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testIciciStatementFetchDispatchForIciciNonBankingHours' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process/icici',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'accounts_processed' => [],
                'reason'             => 'ICICI Statement Fetch does not happen during this period.'
            ]
        ]
    ],

    'testIciciAccountStatementWithVariousRegex' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process/icici',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'accounts_processed' => ['2224440041626905']
            ],
        ],
    ],

    'testCreatingIFTPayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'payout',
                'narration'       => 'ICICI account payout',
                'fund_account_id' => 'fa123',
                'mode'            => FundTransfer\Mode::IFT,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'status'  => 'processing',
            ],
        ]
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

    'testInsertIciciMissingAccountStatement' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/insert_missing',
            'content' => [
                'account_number' => '2224440041626905',
                'channel'        => 'icici',
                'action'         => 'insert',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testViewIciciMissingAccountStatementsFromRedis' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/insert_missing',
            'content' => [
                'account_number' => '2224440041626905',
                'channel'        => 'icici',
                'action'         => 'fetch',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDryRunInsertMissingAccountStatement' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/insert_missing',
            'content' => [
                'account_number' => '2224440041626905',
                'channel'        => 'icici',
                'action'         => 'dry_run',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchIciciMissingAccountStatement' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/fetch_missing/icici',
            'content' => [
                'account_number' => '2224440041626905',
                'from_date'      => 1656686600,
                'to_date'        => 1656986600,
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

    'testICICIStatementShouldNotFetchForNon2FAMerchantsIfBlockIsEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process/icici',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'accounts_processed' => ['2224440041626905']
            ],
        ],
    ],

    'testICICIStatementShouldNotFetchForBaasMerchantsWhenCredentialsIsNotReturnedByBas' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process/icici',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'accounts_processed' => ['2224440041626905']
            ],
        ],
    ],

    'testIciciAccountStatementTxnMappingUsingGatewayRefNo' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process/icici',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testIciciMissingAccountStatementDetection' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/detect_missing/icici',
            'content' => [
                'account_numbers'              => ['2224440041626905'],
                'suspected_mismatch_timestamp' => 1660501800,
            ],
        ],
        'response' => [
            'content' => [
                'channel'                      => 'icici',
                'account_numbers'              => ['2224440041626905'],
                'suspected_mismatch_timestamp' => '1660501800',
            ]
        ]
    ],
];
