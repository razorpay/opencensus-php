<?php

return [
    'testGetIntegrationUrlServiceMethod' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-payouts/integration/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testInitiateIntegrationServiceMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-payouts/integration/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testIntegrationStatusServiceMethod' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-payouts/integration/status',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testIntegrationStatusAppServiceMethod' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-payouts/integration/quickbooks/status',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCallbackServiceMethod' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-payouts/callback',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testAppCredentialsServiceMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-payouts/appcredentials/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testDeleteServiceMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-payouts/delete/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testSyncStatusServiceMethod' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-payouts/sync/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testSyncServiceMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-payouts/sync/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testWaitlistServiceMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-payouts/waitlist/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testIntegrationStatusForViewOnlyUsers' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-payouts/integration/status',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testSyncStatusForViewOnlyUsers' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-payouts/sync/quickbooks',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCreateTallyInvoiceServiceMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/tally/invoices',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testFetchTallyInvoiceServiceMethod' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-integration/tally/invoices',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCancelTallyInvoiceServiceMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/tally/invoices/cancel',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testFetchTallyPaymentsServiceMethod' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-integration/tally/payments',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testAcknowledgeTallyPaymentServiceMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/tally/payments/randomid/acknowledge',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testIntegrateTallyServiceMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/tally/integrate',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testDeleteIntegrationTallyServiceMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/tally/delete',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testUpdateBankAccountMappingCallsServiceMethods' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/cashflow/update-bank-mapping',

        ],
        'response' => [
            'content' => []
        ]
    ],
    'testGetOrganisationsInfoServiceMethod'           => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-integration/organizations/zoho',

        ],
        'response' => [
            'content' => []
        ]
    ],

    'testListCashFlowBankAccountCallsServiceMethods' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/accounting-integration/cashflow/bank-accounts',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testSetOrganisationsInfoServiceMethod' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/accounting-integration/organizations/zoho',

        ],
        'response' => [
            'content' => []
        ]

    ],
];
