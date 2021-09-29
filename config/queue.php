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

    'webhook_event' => [
        'test'       => env('AWS_WEBHOOKS_TEST_QUEUE'),
        'live'       => env('AWS_WEBHOOKS_LIVE_QUEUE'),
    ],
    'dashboard' => [
        'test'       => env('AWS_GENERAL_TEST_QUEUE'),
        'live'       => env('AWS_GENERAL_LIVE_QUEUE'),
    ],
    'queued_payouts_initiate' => [
        'test'       =>  env('AWS_QUEUED_PAYOUTS_INITIATE_TEST_QUEUE'),
        'live'       =>  env('AWS_QUEUED_PAYOUTS_INITIATE_LIVE_QUEUE'),
    ],
    'batch_payouts_process' => [
        'test'       =>  env('AWS_BATCH_PAYOUTS_PROCESS_TEST_QUEUE'),
        'live'       =>  env('AWS_BATCH_PAYOUTS_PROCESS_LIVE_QUEUE'),
    ],
    'queued_payouts' => [
        'test'       => env('AWS_PAYOUTS_TEST_QUEUE'),
        'live'       => env('AWS_PAYOUTS_LIVE_QUEUE'),
    ],
    'scheduled_payouts_process' => [
        'test'       =>  env('AWS_SCHEDULED_PAYOUTS_PROCESS_TEST_QUEUE'),
        'live'       =>  env('AWS_SCHEDULED_PAYOUTS_PROCESS_LIVE_QUEUE'),
    ],
    'cohort_dispatch' => [
        'live'       =>  env('AWS_COHORT_DISPATCH_LIVE_QUEUE'),
    ],
    'payout_post_create_process' => [
        'test'       =>  env('AWS_PAYOUT_POST_PROCESS_TEST_QUEUE'),
        'live'       =>  env('AWS_PAYOUT_POST_PROCESS_LIVE_QUEUE'),
    ],
    'payout_post_create_process_low_priority' => [
        'test'       =>  env('AWS_PAYOUT_POST_PROCESS_LOW_PRIORITY_TEST_QUEUE'),
        'live'       =>  env('AWS_PAYOUT_POST_PROCESS_LOW_PRIORITY_LIVE_QUEUE')
    ],
    'on_hold_payouts_process' => [
        'test'       =>  env('AWS_ON_HOLD_PAYOUTS_PROCESS_TEST_QUEUE'),
        'live'       =>  env('AWS_ON_HOLD_PAYOUTS_PROCESS_LIVE_QUEUE')
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
    'recon_method_batch' => [
        'test'       => env('AWS_RECON_QUEUE'),
        'live'       => env('AWS_RECON_QUEUE'),
    ],
    'reconciliation_batch' => [
        'test'       => env('AWS_RECON_BATCH_QUEUE'),
        'live'       => env('AWS_RECON_BATCH_QUEUE'),
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
    //order transfers queue
    'transfer_process' => [
        'test'       => env('AWS_ORDER_TRANSFER_TEST_QUEUE'),
        'live'       => env('AWS_ORDER_TRANSFER_LIVE_QUEUE'),
    ],
    // Second transfer process queue (for key merchants).
    'transfer_process_key_merchants' => [
        'test'       => env('AWS_TRANSFER_PROCESS_TEST_QUEUE'),
        'live'       => env('AWS_TRANSFER_PROCESS_LIVE_QUEUE'),
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

    'settlement_service_txns' => [
        'test'       => env('AWS_PROCESS_TXNS_TEST_QUEUE'),
        'live'       => env('AWS_PROCESS_TXNS_LIVE_QUEUE'),
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
    'merchant_balance_update' => [
        'test'       => env('AWS_MERCHANT_BALANCE_UPDATE_TEST_QUEUE'),
        'live'       => env('AWS_MERCHANT_BALANCE_UPDATE_LIVE_QUEUE'),
     ],
    'core_payment_service_sync' => [
        'test'       => env('AWS_CPS_SYNC_TEST_QUEUE'),
        'live'       => env('AWS_CPS_SYNC_LIVE_QUEUE'),
    ],
    'token_action_notify' => [
        'test'       => env('AWS_TOKEN_ACTION_NOTIFY_QUEUE'),
        'live'       => env('AWS_TOKEN_ACTION_NOTIFY_QUEUE'),
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
    'payout_source_updater' => [
        'test'       => env('AWS_PAYOUT_SOURCE_UPDATER_TEST_QUEUE'),
        'live'       => env('AWS_PAYOUT_SOURCE_UPDATER_LIVE_QUEUE'),
    ],
    'fund_account_details_propagator' => [
        'test'       => env('AWS_FUND_ACCOUNT_DETAILS_PROPAGATOR_TEST_QUEUE'),
        'live'       => env('AWS_FUND_ACCOUNT_DETAILS_PROPAGATOR_LIVE_QUEUE'),
    ],
    'payment_nbplus_api_reconciliation' => [
        'test'       =>  env('AWS_PAYMENT_NBPLUS_API_RECONCILIATION_TEST_QUEUE'),
        'live'       =>  env('AWS_PAYMENT_NBPLUS_API_RECONCILIATION_LIVE_QUEUE'),
    ],
    'rbl_banking_account_gateway_balance_update' => [
        'test'       =>  env('AWS_RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_TEST_QUEUE'),
        'live'       =>  env('AWS_RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_LIVE_QUEUE'),
    ],
    'icici_banking_account_gateway_balance_update' => [
        'live'       =>  env('AWS_ICICI_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_LIVE_QUEUE'),
    ],
    'fee_recovery' => [
        'test'       =>  env('AWS_RBL_FEE_RECOVERY_TEST_QUEUE'),
        'live'       =>  env('AWS_RBL_FEE_RECOVERY_LIVE_QUEUE'),
    ],
    'downtime' => [
        'test'       => env('AWS_DOWNTIME_QUEUE'),
        'live'       => env('AWS_DOWNTIME_QUEUE'),
    ],

    'low_balance_config_alerts_cron' => [
        'test'       =>  env('AWS_LOW_BALANCE_CONFIG_ALERTS_TEST_QUEUE'),
        'live'       =>  env('AWS_LOW_BALANCE_CONFIG_ALERTS_LIVE_QUEUE'),
    ],

    'payout_downtime' => [
        'test'       => env('AWS_DOWNTIME_COMMUNICATION_QUEUE'),
        'live'       => env('AWS_DOWNTIME_COMMUNICATION_QUEUE'),
    ],
    'payment_page_generic' => [
        'test'       => env('AWS_PAYMENT_PAGE_GENERIC_QUEUE_TEST'),
        'live'       => env('AWS_PAYMENT_PAGE_GENERIC_QUEUE_LIVE'),
    ],

    'fav_queue_for_fts' => [
        'test'       =>  env('AWS_FAV_QUEUE_FOR_FTS_TEST_QUEUE'),
        'live'       =>  env('AWS_FAV_QUEUE_FOR_FTS_LIVE_QUEUE'),
    ],

    /*
     | Lists various queues to be used per mailable
     */
    'mail' => [
        'default' => env('AWS_EMAILS_QUEUE'),
        'downtime_notification' => env('AWS_PAYMENT_DOWNTIME_WEBHOOK_QUEUE'),
    ],

    'poc_update'=>[
        'test'       =>  env('AWS_POC_UPDATE_QUEUE'),
        'live'       =>  env('AWS_POC_UPDATE_QUEUE'),
    ],

    'terminals_service_migrate' => [
        'test'      =>  env('TERMINALS_SERVICE_MIGRATE_QUEUE'),
        'live'      =>  env('TERMINALS_SERVICE_MIGRATE_QUEUE'),
    ],

    'onboarding_kyc_verification' => [
        'test'       => env('AWS_ONBOARDING_KYC_VERIFICATION_QUEUE'),
        'live'       => env('AWS_ONBOARDING_KYC_VERIFICATION_QUEUE'),
    ],

    'merchant_onboarding_escalation' => [
        'test'       => env('AWS_MERCHANT_ONBOARDING_ESCALATION_QUEUE'),
        'live'       => env('AWS_MERCHANT_ONBOARDING_ESCALATION_QUEUE'),
    ],

    'bank_transfer_create' => [
        'test'       => env('AWS_BANK_TRANSFER_CREATE_TEST_QUEUE'),
        'live'       => env('AWS_BANK_TRANSFER_CREATE_LIVE_QUEUE'),
    ],

    'sync_order_pg_router' => [
        'test'      => env('AWS_SYNC_ORDER_PG_ROUTER_TEST_QUEUE'),
        'live'      => env('AWS_SYNC_ORDER_PG_ROUTER_LIVE_QUEUE'),
    ],

    'pg_einvoice' => [
        'test'      => env('AWS_PG_EINVOICE_TEST_QUEUE'),
        'live'      => env('AWS_PG_EINVOICE_LIVE_QUEUE'),
    ],

    'apps_risk_check' => [
        'test'       => env('AWS_APPS_RISK_CHECK_TEST_QUEUE'),
        'live'       => env('AWS_APPS_RISK_CHECK_LIVE_QUEUE'),
    ],

    'notify_ras' => [
        'test'       => env('AWS_NOTIFY_RAS_TEST_QUEUE'),
        'live'       => env('AWS_NOTIFY_RAS_LIVE_QUEUE'),
    ],

    'risk_website_checker' => [
        'test'       => env('AWS_RISK_WEBSITE_CHECKER_TEST_QUEUE'),
        'live'       => env('AWS_RISK_WEBSITE_CHECKER_LIVE_QUEUE'),
    ],

    'risk_app_checker' => [
        'test'       => env('AWS_RISK_APP_CHECKER_TEST_QUEUE'),
        'live'       => env('AWS_RISK_APP_CHECKER_LIVE_QUEUE'),
    ],

    'rbl_banking_account_statement_fetch' => [
        'test'       => env('AWS_RBL_BANKING_ACCOUNT_STATEMENT_FETCH_TEST_QUEUE'),
        'live'       => env('AWS_RBL_BANKING_ACCOUNT_STATEMENT_FETCH_LIVE_QUEUE'),
    ],

    'icici_banking_account_statement_fetch' => [
        'test'       => env('AWS_ICICI_BANKING_ACCOUNT_STATEMENT_FETCH_TEST_QUEUE'),
        'live'       => env('AWS_ICICI_BANKING_ACCOUNT_STATEMENT_FETCH_LIVE_QUEUE'),
    ],

    'banking_account_statement_processor' => [
        'test'       => env('AWS_BANKING_ACCOUNT_STATEMENT_PROCESSOR_TEST_QUEUE'),
        'live'       => env('AWS_BANKING_ACCOUNT_STATEMENT_PROCESSOR_LIVE_QUEUE'),
    ],

    'trusted_badge' => [
        'test'       => env('AWS_TRUSTED_BADGE_TEST_QUEUE'),
        'live'       => env('AWS_TRUSTED_BADGE_LIVE_QUEUE'),
    ],
    /*
    |--------------------------------------------------------------------------
    | Raw SQS Mappings
    |--------------------------------------------------------------------------
    |
    | Here you may configure the job mapping for processing raw SQS messages for
    | the respective SQS queues. This can be used for receiving messages from other
    | sources than the API application itself, the connection used will be 'sqs-raw'.
    | The job instance will be initialised with the payload of the message.
    | Ref: SqsRawJob.php
    |
    */
    'raw_sqs_mappings'=>[

        // mapping for settlement service txn processing jobs
        env('AWS_PROCESS_TXNS_TEST_QUEUE') => 'RZP\\Jobs\\ProcessSettlementServiceTxns',
        env('AWS_PROCESS_TXNS_LIVE_QUEUE') => 'RZP\\Jobs\\ProcessSettlementServiceTxns',
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

        'sqs-raw' => [
            'driver' => 'sqs-raw',
            'key'    => env('AWS_KEY_ID'),
            'secret' => env('AWS_KEY_SECRET'),
            'prefix' => env('AWS_QUEUE_PREFIX'),
            'queue'  => env('AWS_DEFAULT_QUEUE'),
            'region' => env('AWS_REGION'),
            // See sqs.timeout configuration above.
            'timeout'     => 3.0,
            'credentials' => $awsCredentialsCache,
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
