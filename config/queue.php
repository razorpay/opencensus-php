<?php

use RZP\Services\Aws\Credentials\FileCache;

//
// By default aws's php sdk usage InstanceProfileProvider mechanism to get credentials from EC2 meta data server.
// We cache the result in file system(by using FileCache adapter).
//
$awsCredentialsCache = new FileCache;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Driver
    |--------------------------------------------------------------------------
    |
    | The Laravel queue API supports a variety of back-ends via an unified
    | API, giving you convenient access to each back-end using the same
    | syntax for each one. Here you may set the default queue driver.
    |
    | Supported: "null", "sync", "database", "beanstalkd",
    |            "sqs", "redis"
    |
    */

    'default'               => env('QUEUE_DRIVER', 'sync'),

    /*
    | If set to true (only in local/testing environment), all queue jobs are pushed to default connection & queue.
    | Only applies for asynchronous job drivers.
    */
    'routing_mock'            => env('QUEUE_ROUTING_MOCK', false),

    /*
    |--------------------------------------------------------------------------
    | Contains mapping of route keys(nested) and which queue connection & queue name to use respectively.
    | Usage: Ref \RZP\Jobs\Extended\PendingDispatch.php
    |--------------------------------------------------------------------------
    */

    'webhook' => [
        'test' => [
            'payment' => [
                'authorized'        => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'captured'          => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'failed'            => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'dispute' => [
                    'created'       => env('AWS_WEBHOOKS_TEST_QUEUE'),
                ],
                'downtime' => [
                    'started'       => env('AWS_WEBHOOKS_TEST_QUEUE'),
                    'resolved'      => env('AWS_WEBHOOKS_TEST_QUEUE'),
                ],
            ],
            'order' => [
                'paid'              => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'invoice' => [
                'paid'              => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'partially_paid'    => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'expired'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'vpa' => [
                'edited'            => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'p2p' => [
                'created'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'rejected'          => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'transferred'       => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'subscription' => [
                'activated'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'charged'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'pending'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'halted'            => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'expired'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'cancelled'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'completed'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'settlement' => [
                'processed'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'virtual_account' => [
                'created'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'credited'          => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'token' => [
                'confirmed'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'payout' => [
                'processed'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'created'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'reversed'          => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'queued'            => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'initiated'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'failed'            => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'refund' => [
                'processed'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'speed_changed'     => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'failed'            => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'account' => [
                'suspended'              => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'funds_hold'             => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'funds_unhold'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'international_enabled'  => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'international_disabled' => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'instantly_activated'    => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'under_review'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'needs_clarification'    => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'activated'              => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'rejected'               => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'payments_enabled'       => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'payments_disabled'      => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
        ],
        'live' => [
            'payment' => [
                'authorized'        => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'captured'          => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'failed'            => env('AWS_WEBHOOKS_FAILURE_QUEUE'),
                'dispute' => [
                    'created'       => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                ],
                'downtime' => [
                    'started'       => env('AWS_PAYMENT_DOWNTIME_WEBHOOK_QUEUE'),
                    'resolved'      => env('AWS_PAYMENT_DOWNTIME_WEBHOOK_QUEUE'),
                ],
            ],
            'order' => [
                'paid'              => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'invoice' => [
                'paid'              => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'partially_paid'    => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'expired'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'vpa' => [
                'edited'            => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'p2p' => [
                'created'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'rejected'          => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'transferred'       => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'subscription' => [
                'activated'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'charged'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'pending'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'halted'            => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'expired'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'cancelled'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'completed'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'settlement' => [
                'processed'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'virtual_account' => [
                'created'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'credited'          => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'token' => [
                'confirmed'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'payout' => [
                'processed'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'created'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'reversed'          => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'queued'            => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'initiated'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'failed'            => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'refund' => [
                'processed'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'speed_changed'     => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'failed'            => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'account' => [
                'suspended'              => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'funds_hold'             => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'funds_unhold'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'international_enabled'  => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'international_disabled' => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'instantly_activated'    => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'under_review'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'needs_clarification'    => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'activated'              => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'rejected'               => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'payments_enabled'       => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'payments_disabled'      => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
        ],
    ],
    'webhook_event' => [
        'test'       => env('AWS_WEBHOOKS_TEST_QUEUE'),
        'live'       => env('AWS_WEBHOOKS_LIVE_QUEUE'),
    ],
    'dashboard' => [
        'test'       => env('AWS_GENERAL_TEST_QUEUE'),
        'live'       => env('AWS_GENERAL_LIVE_QUEUE'),
    ],
    'queued_payouts' => [
        'test'       => env('AWS_PAYOUTS_TEST_QUEUE'),
        'live'       => env('AWS_PAYOUTS_LIVE_QUEUE'),
    ],
    'es_sync' => [
        'test'       => env('AWS_ES_SYNC_QUEUE'),
        'live'       => env('AWS_ES_SYNC_QUEUE'),
    ],
    'reports_job' => [
        'test'       => env('AWS_REPORTS_QUEUE'),
        'live'       => env('AWS_REPORTS_QUEUE'),
    ],
    'merchant_invoice' => [
        'test'       => env('AWS_INVOICE_REPORTS_QUEUE'),
        'live'       => env('AWS_INVOICE_REPORTS_QUEUE'),
    ],
    'invoice' => [
        'test'       => env('AWS_INVOICE_EMAILS_QUEUE'),
        'live'       => env('AWS_INVOICE_EMAILS_QUEUE'),
    ],
    'batch' => [
        'test'       => env('AWS_BATCH_QUEUE'),
        'live'       => env('AWS_BATCH_QUEUE'),
    ],
    'irctc_batch' => [
        'test'       => env('AWS_IRCTC_BATCH_QUEUE'),
        'live'       => env('AWS_IRCTC_BATCH_QUEUE'),
    ],
    'emandate_batch' => [
        'test'       => env('AWS_PAYMENT_BATCH_QUEUE'),
        'live'       => env('AWS_PAYMENT_BATCH_QUEUE'),
    ],
    'nach_batch' => [
        'test'       => env('AWS_PAYMENT_BATCH_QUEUE'),
        'live'       => env('AWS_PAYMENT_BATCH_QUEUE'),
    ],
    'reconciliation_batch' => [
        'test'       => env('AWS_RECON_QUEUE'),
        'live'       => env('AWS_RECON_QUEUE'),
    ],
    'direct_debit_batch' => [
        'test'       => env('AWS_PAYMENT_BATCH_QUEUE'),
        'live'       => env('AWS_PAYMENT_BATCH_QUEUE'),
    ],
    'bank_transfer_batch' => [
        'test'       => env('AWS_PAYMENT_BATCH_QUEUE'),
        'live'       => env('AWS_PAYMENT_BATCH_QUEUE'),
    ],
    'terminal_creation_batch' => [
        'test'       => env('AWS_TERMINAL_BATCH_QUEUE'),
        'live'       => env('AWS_TERMINAL_BATCH_QUEUE'),
    ],
    'refund_batch' => [
        'test'       => env('AWS_REFUND_QUEUE'),
        'live'       => env('AWS_REFUND_QUEUE'),
    ],
    'terminal_batch' => [
        'test'       => env('AWS_TERMINAL_BATCH_QUEUE'),
        'live'       => env('AWS_TERMINAL_BATCH_QUEUE'),
    ],
    'submerchant_assign_batch' => [
        'test'       => env('AWS_TERMINAL_BATCH_QUEUE'),
        'live'       => env('AWS_TERMINAL_BATCH_QUEUE'),
    ],
    'capture' => [
        'test'       => env('AWS_CAPTURE_TEST_QUEUE'),
        'live'       => env('AWS_CAPTURE_LIVE_QUEUE'),
    ],
    'bulk_refund' => [
        'test'       => env('AWS_REFUND_QUEUE'),
        'live'       => env('AWS_REFUND_QUEUE'),
    ],
    'scrooge_refund' => [
        'test'      => env('AWS_SCROOGE_TEST_QUEUE'),
        'live'      => env('AWS_SCROOGE_LIVE_QUEUE'),
    ],
    'scrooge_refund_retry' => [
        'test'      => env('AWS_SCROOGE_TEST_QUEUE'),
        'live'      => env('AWS_SCROOGE_LIVE_QUEUE'),
    ],
    'scrooge_refund_update' => [
        'test'      => env('AWS_SCROOGE_TEST_QUEUE'),
        'live'      => env('AWS_SCROOGE_LIVE_QUEUE'),
    ],
    'scrooge_refund_verify' => [
        'test'      => env('AWS_SCROOGE_TEST_QUEUE'),
        'live'      => env('AWS_SCROOGE_LIVE_QUEUE'),
    ],
    'gateway_file' => [
        'test'       => env('AWS_GATEWAY_FILE_QUEUE'),
        'live'       => env('AWS_GATEWAY_FILE_QUEUE'),
    ],
    'run_shield_check' => [
        'test'       => env('AWS_SHIELD_QUEUE'),
        'live'       => env('AWS_SHIELD_QUEUE'),
    ],
    'beam_job' => [
        'test'       => env('AWS_BEAM_TEST_QUEUE'),
        'live'       => env('AWS_BEAM_LIVE_QUEUE'),
    ],
    //transfers queue
    'transfer_settlement' => [
        'test'       => env('AWS_TRANSFER_SETTLEMENT_TEST_QUEUE'),
        'live'       => env('AWS_TRANSFER_SETTLEMENT_LIVE_QUEUE'),
    ],
    // settlement related QUEUES
    'settlement_create' => [
        'test'       => env('AWS_SETTLEMENT_CREATE_TEST_QUEUE'),
        'live'       => env('AWS_SETTLEMENT_CREATE_LIVE_QUEUE'),
    ],
    'settlement_bucket' => [
        'test'       => env('AWS_SETTLEMENT_BUCKET_TEST_QUEUE'),
        'live'       => env('AWS_SETTLEMENT_BUCKET_LIVE_QUEUE'),
    ],
    'settlement_initiate' => [
        'test'       => env('AWS_SETTLEMENT_INITIATE_TEST_QUEUE'),
        'live'       => env('AWS_SETTLEMENT_INITIATE_LIVE_QUEUE'),
    ],

    // not using anymore for settlement
    // but has dependency on FTA
    'settlement_transactions' => [
        'test'       => env('AWS_SETTLEMENT_TEST_QUEUE'),
        'live'       => env('AWS_SETTLEMENT_LIVE_QUEUE'),
    ],
    'instant_fund_transfer' => [
        'test'       => env('AWS_SETTLEMENT_TEST_QUEUE'),
        'live'       => env('AWS_SETTLEMENT_LIVE_QUEUE'),
    ],
    'fund_transfer_recon_update' => [
        'test'       => env('AWS_SETTLEMENT_TEST_QUEUE'),
        'live'       => env('AWS_SETTLEMENT_LIVE_QUEUE'),
    ],
    'fund_transfer_status_check' => [
        'test'       => env('AWS_SETTLEMENT_TEST_QUEUE'),
        'live'       => env('AWS_SETTLEMENT_LIVE_QUEUE'),
    ],
    'fts_create_account' => [
        'test'       => env('AWS_FTS_TEST_QUEUE'),
        'live'       => env('AWS_FTS_LIVE_QUEUE'),
    ],
    'fts_register_account' => [
        'test'       => env('AWS_FTS_TEST_QUEUE'),
        'live'       => env('AWS_FTS_LIVE_QUEUE'),
    ],
    'fts_fund_transfer' => [
        'test'       => env('AWS_FTS_TEST_QUEUE'),
        'live'       => env('AWS_FTS_LIVE_QUEUE'),
    ],
    'carvault_migration' => [
        'test'       => env('AWS_CARDVAULT_MIGRATION_QUEUE'),
        'live'       => env('AWS_CARDVAULT_MIGRATION_QUEUE'),
     ],
    'terminal_onboarding_creation' => [
        'test'       => env('AWS_TERMINAL_ONBOARDING_CREATION_TEST_QUEUE'),
        'live'       => env('AWS_TERMINAL_ONBOARDING_CREATION_LIVE_QUEUE'),
    ],
    'merchant_balance_update' => [
        'test'       => env('AWS_MERCHANT_BALANCE_UPDATE_TEST_QUEUE'),
        'live'       => env('AWS_MERCHANT_BALANCE_UPDATE_LIVE_QUEUE'),
     ],
    'core_payment_service_sync' => [
        'test'       => env('AWS_CPS_SYNC_TEST_QUEUE'),
        'live'       => env('AWS_CPS_SYNC_LIVE_QUEUE'),
    ],
    'subscriptions_payment_notify' => [
        'test'       => env('AWS_SUBSCRIPTIONS_PAYMENT_NOTIFY_QUEUE'),
        'live'       => env('AWS_SUBSCRIPTIONS_PAYMENT_NOTIFY_QUEUE'),
    ],
    'beneficiary_registrations' => [
        'test'       => env('AWS_BENEFICIARY_TEST_QUEUE'),
        'live'       => env('AWS_BENEFICIARY_LIVE_QUEUE'),
    ],
    'beneficiary_verifications' => [
        'test'       => env('AWS_BENEFICIARY_VERIFY_TEST_QUEUE'),
        'live'       => env('AWS_BENEFICIARY_VERIFY_LIVE_QUEUE'),
    ],
    'commission' => [
        'test'       => env('AWS_COMMISSION_QUEUE'),
        'live'       => env('AWS_COMMISSION_QUEUE'),
    ],
    'fund_account_validation' => [
        'test'       => env('AWS_FUND_ACCOUNT_VALIDATION_QUEUE'),
        'live'       => env('AWS_FUND_ACCOUNT_VALIDATION_QUEUE'),
    ],
    'mailing_list_update' => [
        'test'       => env('AWS_MAILING_LIST_UPDATE_TEST_QUEUE'),
        'live'       => env('AWS_MAILING_LIST_UPDATE_LIVE_QUEUE'),
    ],
    'fa_vpa_validation' => [
        'test'       => env('AWS_FA_VPA_VALIDATION_TEST_QUEUE'),
        'live'       => env('AWS_FA_VPA_VALIDATION_LIVE_QUEUE'),
    ],
    'payment_card_api_reconciliation' => [
        'test'       => env('AWS_PAYMENT_CARD_API_RECONCILIATION_TEST_QUEUE'),
        'live'       => env('AWS_PAYMENT_CARD_API_RECONCILIATION_LIVE_QUEUE'),
    ],
    'rbl_banking_account_statement' => [
        'test'       => env('AWS_RBL_BANKING_ACCOUNT_STATEMENT_TEST_QUEUE'),
        'live'       => env('AWS_RBL_BANKING_ACCOUNT_STATEMENT_LIVE_QUEUE'),
    ],
    'payment_nbplus_api_reconciliation' => [
        'test'       =>  env('AWS_PAYMENT_NBPLUS_API_RECONCILIATION_TEST_QUEUE'),
        'live'       =>  env('AWS_PAYMENT_NBPLUS_API_RECONCILIATION_LIVE_QUEUE'),
    ],
    'rbl_banking_account_gateway_balance_update' => [
        'test'       =>  env('AWS_RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_TEST_QUEUE'),
        'live'       =>  env('AWS_RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_LIVE_QUEUE'),
    ],

    /*
     | Lists various queues to be used per mailable
     */
    'mail' => [
        'default' => env('AWS_EMAILS_QUEUE'),
    ],

    'poc_update'=>[
        'test'       =>  env('AWS_POC_UPDATE_QUEUE'),
        'live'       =>  env('AWS_POC_UPDATE_QUEUE'),
    ],

    'terminals_service_migrate' => [
        'test'      =>  env('TERMINALS_SERVICE_MIGRATE_QUEUE'),
        'live'      =>  env('TERMINALS_SERVICE_MIGRATE_QUEUE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table'  => 'jobs',
            'queue'  => 'default',
            'expire' => 60,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host'   => 'localhost',
            'queue'  => 'default',
            'ttr'    => 60,
        ],

        'sqs' => [
            'driver'      => 'sqs',
            'key'         => env('AWS_KEY_ID'),
            'secret'      => env('AWS_KEY_SECRET'),
            'prefix'      => env('AWS_QUEUE_PREFIX'),
            'queue'       => env('AWS_DEFAULT_QUEUE'),
            'region'      => env('AWS_REGION'),
            //
            // This timeout is only used for getting credentials from instance meta server.
            // This timeout is "not" for normal http operations of sdk, e.g. push sqs job, publish sns message etc
            // for which there is another argument/option i.e. http.timeout.
            //
            'timeout'     => 3.0,
            'credentials' => $awsCredentialsCache,
        ],

        // TODO: Update brahma's & k8s code & remove this block
        // Ref: https://github.com/razorpay/brahma/blob/master/ansible-playbooks/roles/app-supervisor/templates/api.supervisor.conf.j2#L19
        'sqs_multi_default' => [
            'driver'      => 'sqs',
            'key'         => env('AWS_KEY_ID'),
            'secret'      => env('AWS_KEY_SECRET'),
            'prefix'      => env('AWS_QUEUE_PREFIX'),
            'queue'       => env('AWS_DEFAULT_QUEUE'),
            'region'      => env('AWS_REGION'),
            // See sqs.timeout configuration above.
            'timeout'     => 3.0,
            'credentials' => $awsCredentialsCache,
        ],

        // TODO: Slack lib should expose method to set just queue name instead of connection
        'sqs_slack' => [
            'driver'      => 'sqs',
            'key'         => env('AWS_KEY_ID'),
            'secret'      => env('AWS_KEY_SECRET'),
            'prefix'      => env('AWS_QUEUE_PREFIX'),
            'queue'       => env('AWS_EMAILS_QUEUE'),
            'region'      => env('AWS_REGION'),
            // See sqs.timeout configuration above.
            'timeout'     => 3.0,
            'credentials' => $awsCredentialsCache,
        ],

        'redis' => [
            'driver'     => 'redis',
            'connection' => 'default',
            'queue'      => 'default',
            'expire'     => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table'    => 'failed_jobs',
    ],


];
