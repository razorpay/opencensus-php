<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testOperationsCannotCreateIntegrationURLMapping' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-payouts/integration/zoho',

        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ]
    ],
    'testOperationsCannotAccessUpdateBankMapping' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-integration/cashflow/update-bank-mapping',

        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testGetIntegrationUrlServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'GET',
            'url'    => '/accounting-payouts/integration/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testInitiateIntegrationServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-payouts/integration/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testIntegrationStatusServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'GET',
            'url'    => '/accounting-payouts/integration/status',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testIntegrationStatusAppServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'GET',
            'url'    => '/accounting-payouts/integration/quickbooks/status',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCallbackServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'GET',
            'url'    => '/accounting-payouts/callback',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testAppCredentialsServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-payouts/appcredentials/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testDeleteServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-payouts/delete/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testSyncStatusServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'GET',
            'url'    => '/accounting-payouts/sync/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testSyncServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-payouts/sync/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testWaitlistServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-payouts/waitlist/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testIntegrationStatusForViewOnlyUsers' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'GET',
            'url'    => '/accounting-payouts/integration/status',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testSyncStatusForViewOnlyUsers' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'GET',
            'url'    => '/accounting-payouts/sync/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCreateTallyInvoiceServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-integration/tally/invoices',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCashFlowEntriesAckServiceMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/tally/cashflow/entries/ack',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCashFlowUpdateMappingServiceMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/tally/cashflow/update-mapping',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testGetTaxSlabServiceMethod' => [
        'request' => [
            'method' => 'GET',
            'url' => '/accounting-integration/tally/tax-slabs',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testGetAllSettingsMethod' => [
        'request' => [
            'method' => 'GET',
            'url' => '/accounting-integration/settings',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testAddOrUpdateSettingsMethod' => [
        'request' => [
            'method' => 'POST',
            'url' => '/accounting-integration/settings',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testGetBankStatementReportMethod' => [
        'request' => [
            'method' => 'GET',
            'url' => '/accounting-integration/bank-statement/report',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCreateTallyVendorsServiceMethod' => [
        'request' => [
            'method' => 'POST',
            'url' => '/accounting-integration/tally/vendors',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testSyncVendorStatusServiceMethod' => [
        'request' => [
            'method' => 'GET',
            'url' => '/accounting-integration/tally/vendors/sync-status',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testGetCashFlowEntriesServiceMethod' => [
        'request' => [
            'method' => 'GET',
            'url' => '/accounting-integration/tally/cashflow/entries',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testGetTallyBankTransactionsServiceMethod' => [
        'request' => [
            'method' => 'GET',
            'url' => '/accounting-integration/tally/bank-transactions',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testAckTallyBankTransactionsServiceMethod' => [
        'request' => [
            'method' => 'POST',
            'url' => '/accounting-integration/tally/bank-transactions/ack',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testFetchTallyInvoiceServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'GET',
            'url'    => '/accounting-integration/tally/invoices',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCancelTallyInvoiceServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-integration/tally/invoices/cancel',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testFetchTallyPaymentsServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'GET',
            'url'    => '/accounting-integration/tally/payments',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testAcknowledgeTallyPaymentServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-integration/tally/payments/randomid/acknowledge',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testIntegrateTallyServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-integration/tally/integrate',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testDeleteIntegrationTallyServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-integration/tally/delete',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testUpdateBankAccountMappingCallsServiceMethods' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-integration/cashflow/update-bank-mapping',

        ],
        'response' => [
            'content' => []
        ]
    ],
    'testGetOrganisationsInfoServiceMethod'           => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'GET',
            'url'    => '/accounting-integration/organizations/zoho',

        ],
        'response' => [
            'content' => []
        ]
    ],

    'testListCashFlowBankAccountCallsServiceMethods' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'GET',
            'url'    => '/accounting-integration/cashflow/bank-accounts',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testSetOrganisationsInfoServiceMethod' => [
        'request'  => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'method' => 'POST',
            'url'    => '/accounting-integration/organizations/zoho',

        ],
        'response' => [
            'content' => []
        ]

    ],

    'testGetChartOfAccountsServiceMethod'  => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-integration/chart-of-accounts/zoho',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testPutChartOfAccountsServiceMethod'  => [
        'request'  => [
            'method' => 'PUT',
            'url'    => '/accounting-integration/chart-of-accounts/zoho',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testSyncChartOfAccountsServiceMethod'  => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/chart-of-accounts/zoho/sync',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCreateIntegrationFromL1Role' => [
        'request'  => [
            'method' => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000006',
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'url'    => '/accounting-payouts/integration/zoho',
            'content' => [],
        ],
        'response' => [
            'status_code' => 200,
            'content' => [],
        ]
    ],

    'testBankStatementFetchTriggerMerchant' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/bank-statement/fetch-trigger-merchant',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testBankStatementFetchTriggerCron' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/bank-statement/fetch-trigger-cron',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testZohoStatementSyncCron' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/zoho/bank-statement/sync-cron',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testGetMerchantBankingAccountsForTally' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-integration/tally/banking-accounts',
        ],
        'response' => [
            'content' => [
                'account_details' => [
                    [
                        'account_number' => '12345566',
                        'account_type' => 'Virtual Account'
                    ],
                    [
                        'account_number' => '12345577',
                        'account_type' => 'Current Account'
                    ]
                ]
            ]
        ]
    ],

    'testUpdateRxTallyLedgerMapping' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/tally/banking-accounts/mapping',
            'content' => [
                'rx_tally_ledger_mapping' => [
                    [
                        'account_number' => '123456',
                        'ledger_id' => '344567',
                        'ledger_name' => 'testXyz'
                    ]
                ]
            ]
        ],
        'response' => [
            'content' => [
                'success' => 'true',
            ]
        ]
    ],

    'testGetBankTransactionsSyncStatus' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-integration/bank-transactions/sync-status',
            'content' => [
                'txn_id-1',
                'txn_id-2',
            ]
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'entity' => 'collection',
                'items' => [
                    [
                        'entity_id' => 'txn_id-1',
                        'entity_type' => 'transaction',
                        'reason' => '',
                        'status' => 'ready-to-sync',
                        'sync_completed_at' => 0,
                        'sync_failed_at' => 0,
                        'sync_started_at' => 12345688,
                    ],
                    [
                        'entity_id' => 'txn_id-2',
                        'entity_type' => 'transaction',
                        'reason' => '',
                        'status' => 'syncing',
                        'sync_completed_at' => 0,
                        'sync_failed_at' => 0,
                        'sync_started_at' => 12345688,
                    ]
                ]
            ]
        ]
    ],

    'testCheckIfBankMappingRequired' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-integration/bank-accounts/verify',
        ],
        'response' => [
            'content' => [
                'is_required' => true,
            ]
        ]
    ],

    'testEdgeProxyForAccountingIntegrationsSuccessProxyAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-integrations/rules/rule_testid',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
                'id' => 'rule_testid',
                'type' => 'rx_contact'
            ],
            'status_code' => 200
        ]
    ],

    'testEdgeProxyForAccountingIntegrations5xxProxyAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-integrations/rules/rule_testid',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
                'message' => 'request could not be completed due to internal error',
                'details' => [
                    'code' => 'INTERNAL_SERVER_ERROR'
                ]
            ],
            'status_code' => 500
        ]
    ],

    'testEdgeProxyForAccountingIntegrationsDirectAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/direct/accounting-integrations/callback',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
                'message' => 'integration completed successfully'
            ],
            'status_code' => 200
        ]
    ],
];
