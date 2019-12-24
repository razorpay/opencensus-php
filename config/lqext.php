<?php

return [

    // Configuration for making events, bus dispatcher and mailer services
    // transaction aware.
    'transaction' => [

        // Specifies whether to enable this extension.
        'enable' => env('LQEXT_ENABLE_TXN_AWARE', false),

        // This is the number of transactions to skip in testing mode
        // This has been added since tests are wrapped in a tnx which
        // is rolled back at the end of the test, therefore events were
        // not getting fired. Using this we can skip the given number of
        // transactions. This has been kept as configurable because there
        // can be more than 1 database which is being used
        'testing_txn_skip_count' => env('LQEXT_TESTING_TXN_SKIP_COUNT', 1),

        'debug' => false,

        // Whitelisted events, commands, mailable names which are to be
        // transaction aware.
        // Alternatively a class can use TransactionAware trait.
        'whitelist' => [
            'api.account.suspended',
            'api.account.funds_hold',
            'api.account.funds_unhold',
            'api.account.international_enabled',
            'api.account.international_disabled',
            'api.account.instantly_activated',
            'api.account.under_review',
            'api.account.needs_clarification',
            'api.account.activated',
            'api.account.rejected',
            'api.account.payments_enabled',
            'api.account.payments_disabled',
        ],
    ],
];
