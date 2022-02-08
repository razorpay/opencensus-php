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
            'content' => [],
        ],
        'response' => [
            'content' => [],
        ],
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
];
