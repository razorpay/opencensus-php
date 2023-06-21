<?php

use RZP\Services\Aws\Credentials\FileCache;

//
// By default aws's php sdk usage InstanceProfileProvider mechanism to get credentials from EC2 meta data server.
// We cache the result in file system(by using FileCache adapter).
//
$awsCredentialsCache = new FileCache;
if (env('APP_MODE') === "devserve")
{
    // for anonymous client as devstack uses localstack
    $awsCredentialsCache = false;
}

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
    'ledger_transactions' => [
        'test'       =>  env('AWS_LEDGER_TRANSACTIONS_TEST_QUEUE'),
        'live'       =>  env('AWS_LEDGER_TRANSACTIONS_LIVE_QUEUE'),
    ],
    'ledger_status' => [
        'test'       =>  env('AWS_LEDGER_STATUS_TEST_QUEUE'),
        'live'       =>  env('AWS_LEDGER_STATUS_LIVE_QUEUE'),
    ],
    'ledger_x_journal' => [
        'test'       =>  env('AWS_LEDGER_X_JOURNAL_TEST_QUEUE'),
        'live'       =>  env('AWS_LEDGER_X_JOURNAL_LIVE_QUEUE'),
    ],
    'approved_payout_distribute' => [
        'test'       => env('AWS_APPROVED_PAYOUT_DISTRIBUTION_TEST_QUEUE'),
        'live'       => env('AWS_APPROVED_PAYOUT_DISTRIBUTION_LIVE_QUEUE'),
        'connection' => 'sqs-fifo'
    ],
    'approved_payout_processor' => [
        'test'       => env('AWS_APPROVED_PAYOUT_PROCESSOR_TEST_QUEUE'),
        'live'       => env('AWS_APPROVED_PAYOUT_PROCESSOR_LIVE_QUEUE'),
    ],
    'approved_payout_processor_dlq' => [
        'test'       => env('AWS_APPROVED_PAYOUT_PROCESSOR_DLQ_TEST_QUEUE'),
        'live'       => env('AWS_APPROVED_PAYOUT_PROCESSOR_DLQ_LIVE_QUEUE'),
    ],
    'batch_payouts_process' => [
        'test'       =>  env('AWS_BATCH_PAYOUTS_PROCESS_TEST_QUEUE'),
        'live'       =>  env('AWS_BATCH_PAYOUTS_PROCESS_LIVE_QUEUE'),
    ],
    'queued_payouts' => [
        'test'       => env('AWS_PAYOUTS_TEST_QUEUE'),
        'live'       => env('AWS_PAYOUTS_LIVE_QUEUE'),
    ],
    'queued_credit_transfer_requests' => [
        'test'       => env('AWS_PAYOUTS_TEST_QUEUE'),
        'live'       => env('AWS_PAYOUTS_LIVE_QUEUE'),
    ],
    'free_payout_migration_to_payouts_service' => [
        'test'       => env('AWS_PAYOUTS_TEST_QUEUE'),
        'live'       => env('AWS_PAYOUTS_LIVE_QUEUE'),
    ],
    'payout_service_dual_write' => [
        'test'       => env('AWS_PAYOUT_SERVICE_DUAL_WRITE_LIVE_QUEUE'),
        'live'       => env('AWS_PAYOUT_SERVICE_DUAL_WRITE_LIVE_QUEUE'),
    ],
    'payout_service_data_migration' => [
        'test'       => env('AWS_PAYOUT_SERVICE_DATA_MIGRATION_LIVE_QUEUE'),
        'live'       => env('AWS_PAYOUT_SERVICE_DATA_MIGRATION_LIVE_QUEUE'),
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
    'partner_bank_on_hold_payouts_process' => [
        'test'       =>  env('AWS_PARTNER_BANK_ON_HOLD_PAYOUTS_PROCESS_TEST_QUEUE'),
        'live'       =>  env('AWS_PARTNER_BANK_ON_HOLD_PAYOUTS_PROCESS_LIVE_QUEUE')
    ],
    'payouts_auto_expire' => [
        'test'       =>  env('AWS_PAYOUTS_AUTO_EXPIRE_TEST_QUEUE'),
        'live'       =>  env('AWS_PAYOUTS_AUTO_EXPIRE_LIVE_QUEUE')
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
    // generic batch queue with high visibility timeout
    'linked_account_batch' => [
        'test'      => env('AWS_LINKED_ACCOUNT_BATCH'),
        'live'      => env('AWS_LINKED_ACCOUNT_BATCH')
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
    'nach_batch_process' => [
        'test'       => env('AWS_NACH_BATCH_PROCESS_QUEUE'),
        'live'       => env('AWS_NACH_BATCH_PROCESS_QUEUE'),
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
    // Dedicated transfer processing queue for Capital Float (9ARetirTY8olre).
    'transfer_process_capital_float' => [
        'live'       => env('AWS_TRANSFER_PROCESS_CF_LIVE_QUEUE'),
    ],
    // Dedicated transfer processing queue for Slice (5BX85IgTyYI20C).
    'transfer_process_slice' => [
        'live'       => env('AWS_TRANSFER_PROCESS_SL_LIVE_QUEUE'),
    ],
    // Dedicated transfer processing queue for payment_transfer_batch.
    'transfer_process_batch' => [
        'test'       => env('AWS_TRANSFER_PROCESS_BATCH_TEST_QUEUE'),
        'live'       => env('AWS_TRANSFER_PROCESS_BATCH_LIVE_QUEUE'),
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
    'cardvault_migration' => [
        'test'       => env('AWS_CARDVAULT_MIGRATION_QUEUE'),
        'live'       => env('AWS_CARDVAULT_MIGRATION_QUEUE'),
     ],
    'pushprovisioning' => [
        'test'       => env('AWS_PUSH_PROVISIONING_QUEUE'),
        'live'       => env('AWS_PUSH_PROVISIONING_QUEUE'),
    ],
    'merchant_balance_update' => [
        'test'       => env('AWS_MERCHANT_BALANCE_UPDATE_TEST_QUEUE'),
        'live'       => env('AWS_MERCHANT_BALANCE_UPDATE_LIVE_QUEUE'),
     ],
    'payment_reminder' => [
        'test'       => env('AWS_PAYMENT_REMINDER_TEST_QUEUE'),
        'live'       => env('AWS_PAYMENT_REMINDER_LIVE_QUEUE'),
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
    'partnerships_commission' => [
        'test'      => env('AWS_PARTNERSHIPS_COMMISSION_QUEUE_TEST'),
        'live'      => env('AWS_PARTNERSHIPS_COMMISSION_QUEUE_LIVE')
    ],
    'prts_commission_capture' => [
        'test'      => env('AWS_PARTNERSHIPS_COMMISSION_CAPTURE_QUEUE_TEST'),
        'live'      => env('AWS_PARTNERSHIPS_COMMISSION_CAPTURE_QUEUE_LIVE')
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
    'connected_banking_account_gateway_balance_update' => [
        'live'       => env('AWS_CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_LIVE_QUEUE'),
        'test'       => env('AWS_CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_TEST_QUEUE'),
    ],
    'fee_recovery' => [
        'test'       =>  env('AWS_RBL_FEE_RECOVERY_TEST_QUEUE'),
        'live'       =>  env('AWS_RBL_FEE_RECOVERY_LIVE_QUEUE'),
    ],
    'downtime' => [
        'test'       => env('AWS_DOWNTIME_QUEUE'),
        'live'       => env('AWS_DOWNTIME_QUEUE'),
    ],

    'low_balance_config_alerts' => [
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

    'payout_attachment_email' => [
        'test'       =>  env('AWS_PAYOUT_ATTACHMENT_EMAIL_TEST_QUEUE'),
        'live'       =>  env('AWS_PAYOUT_ATTACHMENT_EMAIL_LIVE_QUEUE'),
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

    'missed_orders_pl_create' => [
        'test'       => env('PAYMENT_LINKS_PAYMENT_FAILED_QUEUE'),
        'live'       => env('PAYMENT_LINKS_PAYMENT_FAILED_QUEUE'),
    ],

    'barricade_verify' => [
        'test'       => env('BARRICADE_VERIFY_QUEUE'),
        'live'       => env('BARRICADE_VERIFY_QUEUE'),
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

    'shield_create_rule_analytics'  => env('AWS_SHIELD_CREATE_RULE_ANALYTICS_QUEUE'),

    'rbl_banking_account_statement_fetch' => [
        'test'       => env('AWS_RBL_BANKING_ACCOUNT_STATEMENT_FETCH_TEST_QUEUE'),
        'live'       => env('AWS_RBL_BANKING_ACCOUNT_STATEMENT_FETCH_LIVE_QUEUE'),
    ],

    'icici_banking_account_statement_fetch' => [
        'test'       => env('AWS_ICICI_BANKING_ACCOUNT_STATEMENT_FETCH_TEST_QUEUE'),
        'live'       => env('AWS_ICICI_BANKING_ACCOUNT_STATEMENT_FETCH_LIVE_QUEUE'),
    ],

    'banking_account_statement_recon' => [
        'test'       => env('AWS_BANKING_ACCOUNT_STATEMENT_RECON_TEST_QUEUE'),
        'live'       => env('AWS_BANKING_ACCOUNT_STATEMENT_RECON_LIVE_QUEUE'),
    ],

    'banking_account_statement_recon_neo' => [
        'test'       => env('AWS_BANKING_ACCOUNT_STATEMENT_RECON_NEO_TEST_QUEUE'),
        'live'       => env('AWS_BANKING_ACCOUNT_STATEMENT_RECON_NEO_LIVE_QUEUE'),
    ],

    'banking_account_statement_recon_process_neo' => [
        'test'       => env('AWS_BANKING_ACCOUNT_STATEMENT_RECON_PROCESS_NEO_TEST_QUEUE'),
        'live'       => env('AWS_BANKING_ACCOUNT_STATEMENT_RECON_PROCESS_NEO_TEST_QUEUE'),
    ],

    'missing_account_statement_insert' => [
        'live'       => env('AWS_MISSING_ACCOUNT_STATEMENT_INSERT_LIVE_QUEUE'),
    ],

    'banking_account_statement_processor' => [
        'test'       => env('AWS_BANKING_ACCOUNT_STATEMENT_PROCESSOR_TEST_QUEUE'),
        'live'       => env('AWS_BANKING_ACCOUNT_STATEMENT_PROCESSOR_LIVE_QUEUE'),
    ],

    'banking_account_statement_update' => [
        'test'       => env('AWS_BANKING_ACCOUNT_STATEMENT_UPDATE_TEST_QUEUE'),
        'live'       => env('AWS_BANKING_ACCOUNT_STATEMENT_UPDATE_LIVE_QUEUE'),
    ],

    'banking_account_statement_source_linking' => [
        'live'       => env('AWS_BANKING_ACCOUNT_STATEMENT_SOURCE_LINKING_LIVE_QUEUE'),
    ],

    'fund_management_payout_check' => [
        'live'       => env('AWS_FUND_MANAGEMENT_PAYOUT_CHECK_LIVE_QUEUE'),
    ],

    'fund_management_payout_initiate' => [
        'live'       => env('AWS_FUND_MANAGEMENT_PAYOUT_INITIATE_LIVE_QUEUE'),
    ],

    'missing_account_statement_detect' => [
        'live'       => env('AWS_MISSING_ACCOUNT_STATEMENT_DETECT_LIVE_QUEUE'),
    ],

    'trusted_badge' => [
        'test'       => env('AWS_TRUSTED_BADGE_TEST_QUEUE'),
        'live'       => env('AWS_TRUSTED_BADGE_LIVE_QUEUE'),
    ],

    'merchant_async_tokenisation' => [
        'test'       => env('AWS_MERCHANT_ASYNC_TOKENISATION_TEST_QUEUE'),
        'live'       => env('AWS_MERCHANT_ASYNC_TOKENISATION_LIVE_QUEUE'),
    ],

    'par_migration' => [
        'test'       => env('AWS_PAR_ASYNC_TOKENISATION_TEST_QUEUE'),
        'live'       => env('AWS_PAR_ASYNC_TOKENISATION_LIVE_QUEUE'),
    ],

    'card_metadata_deletion' =>[
        'test'       => env('AWS_CARD_METADATA_DELETION_TEST_QUEUE'),
        'live'       => env('AWS_CARD_METADATA_DELETION_LIVE_QUEUE'),
    ],

    'firs_document_process' => [
        'test'       => env('AWS_FIRS_DOCUMENT_TEST_QUEUE'),
        'live'       => env('AWS_FIRS_DOCUMENT_LIVE_QUEUE'),
    ],

    'one_cc_shopify_create_order' => [
        'test'       => env('AWS_ONE_CC_SHOPIFY_CREATE_ORDER_TEST_QUEUE'),
        'live'       => env('AWS_ONE_CC_SHOPIFY_CREATE_ORDER_LIVE_QUEUE'),
    ],

    'one_cc_review_cod_order' => [
        'test'       => env('AWS_ONE_CC_REVIEW_COD_ORDER_TEST_QUEUE'),
        'live'       => env('AWS_ONE_CC_REVIEW_COD_ORDER_LIVE_QUEUE'),
    ],

    'zip-firs-documents' => [
        'test'       => env('AWS_ZIP_FIRS_DOCUMENTS_TEST_QUEUE'),
        'live'       => env('AWS_ZIP_FIRS_DOCUMENTS_LIVE_QUEUE'),
    ],

    'cross_border_merchant_email' => [
        'live'       => env('AWS_CROSSBORDER_MERCHANT_EMAIL_LIVE_QUEUE'),
        'test'       => env('AWS_CROSSBORDER_MERCHANT_EMAIL_TEST_QUEUE'),
    ],

    'import_flow_process_settlements' => [
        'live'       => env('AWS_IMPORT_FLOW_PROCESS_SETTLEMENTS_LIVE_QUEUE'),
        'test'       => env('AWS_IMPORT_FLOW_PROCESS_SETTLEMENTS_TEST_QUEUE'),
    ],

    'cross_border_use_case' => [
        'live'       => env('AWS_CROSSBORDER_USE_CASE_LIVE_QUEUE'),
        'test'       => env('AWS_CROSSBORDER_USE_CASE_TEST_QUEUE'),
    ],

    'generate_payment_e_invoice' => [
        'live'       => env('AWS_CROSSBORDER_DCC_EINVOICE_LIVE_QUEUE'),
        'test'       => env('AWS_CROSSBORDER_DCC_EINVOICE_TEST_QUEUE'),
    ],

    'partner_bank_health_notify' => [
        'test'       => env('AWS_PARTNER_BANK_HEALTH_NOTIFY_TEST_QUEUE'),
        'live'       => env('AWS_PARTNER_BANK_HEALTH_NOTIFY_LIVE_QUEUE'),
    ],

    'rbl_virtual_account_create' => [
        'live'       => env('AWS_RBL_VIRTUAL_ACCOUNT_CREATE_LIVE_QUEUE'),
    ],

    'emandate_files_instrumentation' => [
        'test'       => env('AWS_EMANDATE_FILES_INSTRUMENTATION_TEST_QUEUE'),
        'live'       => env('AWS_EMANDATE_FILES_INSTRUMENTATION_LIVE_QUEUE'),
    ],

    'nach_batch_process_async_balance' => [
        'test'       => env('AWS_NACH_BATCH_PROCESS_ASYNC_BAL_TEST_QUEUE'),
        'live'       => env('AWS_NACH_BATCH_PROCESS_ASYNC_BAL_LIVE_QUEUE'),
    ],

    'art_recon_entity_update'  => [
        'test'       => env('AWS_ART_RECON_ENTITY_UPDATE_QUEUE'),
        'live'       => env('AWS_ART_RECON_ENTITY_UPDATE_QUEUE'),
    ],

    'token_hq_pricing_events' =>[
        'test'       => env('AWS_TOKEN_HQ_PRICING_EVENTS_TEST_QUEUE'),
        'live'       => env('AWS_TOKEN_HQ_PRICING_EVENTS_LIVE_QUEUE'),
    ],

    'payments_upi_recon_entity_update' => [
        'test'       => env('AWS_PAYMENTS_UPI_RECON_ENTITY_UPDATE_QUEUE'),
        'live'       => env('AWS_PAYMENTS_UPI_RECON_ENTITY_UPDATE_QUEUE'),
    ],

    'fulfillment_event_update' => env('AWS_ORDER_STATUS_UPDATE_QUEUE'),

    'one_cc_address_ingestion_standardization' => env('AWS_ONE_CC_ADDRESS_INGESTION_STANDARDIZATION_QUEUE'),

    'merchant_based_balance_update_v1' => [
        'live'       => env('AWS_MERCHANT_BASED_BAL_UPDATE_V1'),
        'test'       => env('AWS_MERCHANT_BASED_BAL_UPDATE_V1'),
    ],

    'merchant_based_balance_update_v2' => [
        'live'       => env('AWS_MERCHANT_BASED_BAL_UPDATE_V2'),
        'test'       => env('AWS_MERCHANT_BASED_BAL_UPDATE_V2'),
    ],

    'merchant_based_balance_update_v3' => [
        'live'       => env('AWS_MERCHANT_BASED_BAL_UPDATE_V3'),
        'test'       => env('AWS_MERCHANT_BASED_BAL_UPDATE_V3'),
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

        env('AWS_FIRS_DOCUMENT_TEST_QUEUE') => 'RZP\\Jobs\\MerchantFirsDocuments',
        env('AWS_FIRS_DOCUMENT_LIVE_QUEUE') => 'RZP\\Jobs\\MerchantFirsDocuments',

        // mapping for ledger X journal created txn processing jobs
        env('AWS_LEDGER_X_JOURNAL_TEST_QUEUE') => 'RZP\\Jobs\\LedgerJournalTest',
        env('AWS_LEDGER_X_JOURNAL_LIVE_QUEUE') => 'RZP\\Jobs\\LedgerJournalLive',

        //mapping for ART refund recon entity update job
        env('AWS_ART_RECON_ENTITY_UPDATE_QUEUE') => 'RZP\\Jobs\\ArtReconProcess',

        env('AWS_APPROVED_PAYOUT_DISTRIBUTION_TEST_QUEUE') => 'RZP\\Jobs\\ApprovedPayoutDistribution',
        env('AWS_APPROVED_PAYOUT_DISTRIBUTION_LIVE_QUEUE') => 'RZP\\Jobs\\ApprovedPayoutDistribution',
    ],

    'fifo_sqs_mappings'=>[
        'beta-api-approved-payout-distribution-queue-test' => 'RZP\\Jobs\\ApprovedPayoutDistribution',
        'beta-api-approved-payout-distribution-queue-test' => 'RZP\\Jobs\\ApprovedPayoutDistribution',
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

        'sqs_localstack' => [
            'driver'      => 'sqs',
            'key'         => env('AWS_KEY_ID'),
            'secret'      => env('AWS_KEY_SECRET'),
            'prefix'      => 'https://localstack-services.dev.razorpay.in/000000000000/',
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

        'sqs-fifo' => [
            'driver' => 'sqs-fifo',
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
