<?php

return [
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

    'testGetTaxSlabServiceMethod' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-integration/tally/tax-slabs',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testGetAllSettingsMethod' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-integration/settings',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testAddOrUpdateSettingsMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/settings',
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
];
