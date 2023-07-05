<?php

namespace RZP\Models\Admin;

use App;
use Cache;

use RZP\Models\Admin\Permission\Name;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Models\Merchant\Balance\FreePayout;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Services\Pagination\Entity as PaginationEntity;

class ConfigKey
{
    protected static $fetchedKeys = [];

    const WILDCARD_PERMISSION = '*';

    const PREFIX                                = 'config:';

    // Tenant Role isolation config keys
    // (created for RX/PG isolation)
    const TENANT_ROLES_ENTITY                   = SELF::PREFIX . 'tenant_roles_entity';
    const TENANT_ROLES_ROUTES                   = SELF::PREFIX . 'tenant_roles_routes';

    // Logs
    const TERMINAL_SELECTION_LOG_VERBOSE        = self::PREFIX . 'terminal_selection_log_verbose';
    const PRICING_RULE_SELECTION_LOG_VERBOSE    = self::PREFIX . 'pricing_rule_selection_log_verbose';
    const THROTTLE_MOCK_LOG_VERBOSE             = self::PREFIX . 'throttle_mock_log_verbose';
    const CURL_INFO_LOG_VERBOSE                 = self::PREFIX . 'curl_info_log_verbose';

    const GATEWAY_PROCESSED_REFUNDS             = self::PREFIX . 'GATEWAY_PROCESSED_REFUNDS';
    const GATEWAY_UNPROCESSED_REFUNDS           = self::PREFIX . 'GATEWAY_UNPROCESSED_REFUNDS';
    const BLOCK_BANK_TRANSFERS_FOR_CRYPTO       = self::PREFIX . 'block_bank_transfers_for_crypto';
    const MERCHANT_ENACH_CONFIGS                = self::PREFIX . 'merchant_enach_configs';
    const SKIP_SLAVE                            = self::PREFIX . 'skip_slave';

    const MASTER_PERCENT                        = self::PREFIX . 'master_percent';

    const DISABLE_MAGIC                         = self::PREFIX . 'disable_magic';
    const NPCI_UPI_DEMO                         = self::PREFIX . 'npci_upi_demo';
    const BLOCK_SMART_COLLECT                   = self::PREFIX . 'block_smart_collect';
    const BLOCK_YESBANK                         = self::PREFIX . 'block_yesbank';
    const BLOCK_AADHAAR_REG                     = self::PREFIX . 'block_aadhaar_reg';
    const HITACHI_DYNAMIC_DESCR_ENABLED         = self::PREFIX . 'hitachi_dynamic_descr_enabled';
    const HITACHI_NEW_URL_ENABLED               = self::PREFIX . 'hitachi_new_url_enabled';
    const FTA_CHANNELS                          = self::PREFIX . 'fta_channels';
    const FTS_PAYOUT_VPA                        = self::PREFIX . 'fts_payout_vpa';
    const FTS_PAYOUT_CARD                       = self::PREFIX . 'fts_payout_card';
    const FTS_TEST_MERCHANT                     = self::PREFIX . 'fts_test_merchant';
    const FTS_ROUTE_PERCENTAGE                  = self::PREFIX . 'fts_request_percentage';
    const FTS_PAYOUT_BANK_ACCOUNT               = self::PREFIX . 'fts_payout_bank_account';
    const CPS_SERVICE_ENABLED                   = self::PREFIX . 'cps_service_enabled';
    const PG_ROUTER_SERVICE_ENABLED             = self::PREFIX . 'pg_router_service_enabled';
    const PAYMENTS_DUAL_WRITE                   = self::PREFIX . 'payments_dual_write';
    const CARD_ARCHIVAL_FALLBACK_ENABLED        = self::PREFIX . 'card_archival_fallback_enabled';
    const PAYMENT_ARCHIVAL_FALLBACK_ENABLED     = self::PREFIX . 'payment_archival_fallback_enabled';
    const PAYMENT_ARCHIVAL_EAGER_LOAD           = self::PREFIX . 'payment_archival_eager_load';
    const DATA_WAREHOUSE_CONNECTION_FALLBACK    = self::PREFIX . 'data_warehouse_connection_fallback';
    const SETTLEMENT_TRANSACTION_LIMIT          = self::PREFIX . 'settlement_transaction_limit';
    const ENABLE_PAYMENT_DOWNTIMES              = self::PREFIX . 'enable_payment_downtimes';
    const DOWNTIME_THROTTLE                     = self::PREFIX . 'downtime:throttle';
    const DOWNTIME_DETECTION                    = self::PREFIX . '{downtime:detection}';
    const RX_SLA_FOR_IMPS_PAYOUT                = self::PREFIX . 'rx_sla_for_imps_payout';
    const DOWNTIME_DETECTION_CONFIGURATION      = self::PREFIX . 'downtime:detection:configuration';
    const DOWNTIME_DETECTION_CONFIGURATION_V2   = self::PREFIX . '{downtime}:detection:configuration_v2';
    const DOWNTIME_SLACK_NOTIFICATION_CHANNELS  = self::PREFIX . '{downtime}.slack.notification.channels';
    const BENEFICIARY_REGISTRATION              = self::PREFIX . 'beneficiary_registration:';
    const BENEFICIARY_VERIFICATION              = self::PREFIX . 'beneficiary_verification:';
    const FTS_BENEFICIARY                       = self::PREFIX . 'fts_beneficiary';
    const ENABLE_NB_KOTAK_ENCRYPTED_FLOW        = self::PREFIX . 'enable_nb_kotak_encrypted_flow';
    const ENABLE_PAYMENT_DOWNTIME_CARD          = self::PREFIX . 'enable_payment_downtimes_card';
    const ENABLE_PAYMENT_DOWNTIME_CARD_ISSUER   = self::PREFIX . 'enable_payment_downtimes_card_issuer';
    const ENABLE_PAYMENT_DOWNTIME_CARD_NETWORK  = self::PREFIX . 'enable_payment_downtimes_card_network';
    const ENABLE_PAYMENT_DOWNTIME_NETBANKING    = self::PREFIX . 'enable_payment_downtimes_netbanking';
    const ENABLE_PAYMENT_DOWNTIME_FPX           = self::PREFIX . 'enable_payment_downtimes_fpx';
    const ENABLE_PAYMENT_DOWNTIME_UPI           = self::PREFIX . 'enable_payment_downtimes_upi';
    const ENABLE_PAYMENT_DOWNTIME_WALLET        = self::PREFIX . 'enable_payment_downtimes_wallet';
    const ENABLE_PAYMENT_DOWNTIME_PHONEPE       = self::PREFIX . 'enable_payment_downtime_phonepe';

    const ENABLE_DOWNTIME_SERVICE               = self::PREFIX . 'enable_downtime_service';
    const ENABLE_DOWNTIME_SERVICE_CARD          = self::PREFIX . 'enable_downtime_service_card';
    const ENABLE_DOWNTIME_SERVICE_UPI           = self::PREFIX . 'enable_downtime_service_upi';
    const ENABLE_DOWNTIME_SERVICE_NETBANKING    = self::PREFIX . 'enable_downtime_service_netbanking';
    const ENABLE_DOWNTIME_SERVICE_EMANDATE      = self::PREFIX . 'enable_downtime_service_emandate';
    const USE_MUTEX_FOR_DOWNTIMES               = self::PREFIX . 'use_mutex_for_downtimes';
    const ENABLE_DOWNTIME_WEBHOOKS              = self::PREFIX . 'enable_downtime_webhooks';

    const CARD_PAYMENT_SERVICE_ENABLED          = self::PREFIX . 'card_payment_service_enabled';
    const CARD_PAYMENT_SERVICE_EMI_FETCH        = self::PREFIX . 'card_payment_service_emi_fetch';

    // Atos and Worldline are same, key on redis is atos
    const WORLDLINE_TID_RANGE_LIST              = self::PREFIX . 'atos_tid_range_list';

    // Gateway level configs
    const PAYSECURE_BLACKLISTED_MCCS            = self::PREFIX . 'paysecure_blacklisted_mccs';

    const LOW_BALANCE_RX_EMAIL                  = self::PREFIX . 'low_balance_rx_email';

    const OFFER_LOG_VERBOSE                     = self::PREFIX . 'offer_log_verbose';

    //Banking account current accounts statement fetch for merchants limit on number of merchants for which to update in one run.
    const BANKING_ACCOUNT_STATEMENT_RATE_LIMIT  = self::PREFIX . 'banking_account_statement_rate_limit';

    const RBL_BANKING_ACCOUNT_STATEMENT_CRON_ATTEMPT_DELAY = self::PREFIX . 'rbl_banking_account_statement_cron_attempt_delay';

    const BANKING_ACCOUNT_STATEMENT_PROCESS_DELAY = self::PREFIX . 'banking_account_statement_process_delay';

    CONST RX_BAS_FORCED_FETCH_TIME_IN_HOURS               = self::PREFIX . 'rx_bas_forced_fetch_time_in_hours';

    const PAYOUT_SERVICE_DATA_MIGRATION_LIMIT_PER_BATCH = self::PREFIX . 'payout_service_data_migration_limit_per_batch';

    const PAYOUT_SERVICE_DATA_MIGRATION_BATCH_ATTEMPTS = self::PREFIX . 'payout_service_data_migration_batch_attempts';
    const PAYOUT_SERVICE_DATA_MIGRATION_BUFFER         = self::PREFIX . 'payout_service_data_migration_buffer';

    const TRANSFER_SYNC_PROCESSING_VIA_API_SEMAPHORE_CONFIG = self::PREFIX . 'transfer_sync_via_api_semaphore_config';

    const TRANSFER_SYNC_PROCESSING_VIA_API_HOURLY_RATE_LIMIT_PER_MID = self::PREFIX . 'transfer_sync_via_api_hourly_rate_limit_per_mid';

    const TRANSFER_PROCESSING_MUTEX_CONFIG = self::PREFIX . 'transfer_processing_mutex_config';

    // while creating payouts we fetch balance from gateway at a frequency decided in SLA.
    // So if last fetched at was while ago greater than this value, then we will fetch balance
    // again before creating a payout
    const GATEWAY_BALANCE_LAST_FETCHED_AT_RATE_LIMITING = self::PREFIX . 'gateway_balance_last_fetched_at_rate_limiting';

    //Banking account current accounts balance update for merchants .limit on number of merchants for which to update in one run
    const RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT = self::PREFIX . 'banking_account_gateway_balance_update_rate_limit';

    const RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE = self::PREFIX . 'rbl_banking_account_gateway_balance_update_delete_mode';

    const ICICI_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE = self::PREFIX . 'icici_banking_account_gateway_balance_update_delete_mode';

    const CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE = self::PREFIX . 'connected_banking_account_gateway_balance_update_delete_mode';
    //Merchant gateway balance is maintained at our end to display on dashboard and is updated by cron regularly.
    //This key puts limit on number of merchants for which to update in one run for icici
    const ICICI_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT = self::PREFIX . 'icici_banking_account_gateway_balance_update_rate_limit';

    const CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT = self::PREFIX . 'connected_banking_account_gateway_balance_update_rate_limit';

    // RBL_STATEMENT_FETCH_ATTEMPT_LIMIT is defining the number of attempt count for account statement fetch
    // per request. RBL has internal pagination with flag for statement fetch and we need to refetch with
    // last transaction mentioned to fetch more data.
    const RBL_STATEMENT_FETCH_ATTEMPT_LIMIT         = self::PREFIX . 'rbl_statement_fetch_attempt_limit';

    // ICICI_STATEMENT_FETCH_ATTEMPT_LIMIT is defining the number of attempt count for account statement fetch
    // per request. ICICI has internal pagination with flag for statement fetch and we need to refetch with
    // last transaction mentioned to fetch more data.
    const ICICI_STATEMENT_FETCH_ATTEMPT_LIMIT         = self::PREFIX . 'icici_statement_fetch_attempt_limit';

    const ICICI_STATEMENT_FETCH_ALLOW_DESCRIPTION     = self::PREFIX . 'icici_statement_fetch_allow_description';

    const ICICI_STATEMENT_FETCH_ENABLE_IN_OFF_HOURS     = self::PREFIX . 'icici_statement_fetch_enable_in_off_hours';

    // special attempt limit for merchants transacting more.
    const RBL_STATEMENT_FETCH_SPECIAL_ATTEMPT_LIMIT = self::PREFIX . 'rbl_statement_fetch_special_attempt_limit';

    const RBL_CA_BALANCE_UPDATE_LIMITS = self::PREFIX . 'rbl_ca_balance_update_limits';

    const RBL_STATEMENT_FETCH_RETRY_LIMIT           = self::PREFIX . 'rbl_statement_fetch_retry_limit';

    const FUND_MANAGEMENT_PAYOUTS_RETRIEVAL_THRESHOLD = self::PREFIX . 'fund_management_payouts_retrieval_threshold';

    const ICICI_STATEMENT_FETCH_RETRY_LIMIT           = self::PREFIX . 'icici_statement_fetch_retry_limit';

    const BLOCK_YESBANK_WALLET_PAYOUTS          = self::PREFIX . 'block_yesbank_wallet_payouts';

    const BLOCK_X_REGISTRATION                  = self::PREFIX. 'block_x_registration';

    const BLOCK_YESBANK_RX_FAV                  = self::PREFIX . 'block_yesbank_rx_fav';

    const REMOVE_SETTLEMENT_BA_COOL_OFF         = self::PREFIX . 'remove_settlement_ba_cool_off';

    const RX_ACCOUNT_NUMBER_SERIES_PREFIX       = self::PREFIX . 'rx_account_number_series_prefix';

    const PAYER_ACCOUNT_NUMBER_INVALID_REGEXES  = self::PREFIX . 'payer_account_number_invalid_regexes';

    const PAYER_ACCOUNT_NAME_INVALID_REGEXES  = self::PREFIX . 'payer_account_name_invalid_regexes';

    const RX_SHARED_ACCOUNT_ALLOWED_CHANNELS    = self::PREFIX . 'rx_shared_account_allowed_channels';

    const RBL_STATEMENT_FETCH_WINDOW_LENGTH     = self::PREFIX . 'rbl_statement_fetch_window_length';

    const ICICI_STATEMENT_FETCH_WINDOW_LENGTH   = self::PREFIX . 'icici_statement_fetch_window_length';

    const RBL_STATEMENT_FETCH_V2_API_MAX_RECORDS         = self::PREFIX . 'rbl_statement_fetch_v2_api_max_records';

    const RBL_STATEMENT_FETCH_RATE_LIMIT                 = self::PREFIX . 'rbl_statement_fetch_rate_limit';

    const RBL_STATEMENT_FETCH_RATE_LIMIT_RELEASE_DELAY   = self::PREFIX . 'rbl_statement_fetch_rate_limit_release_delay';

    const ICICI_STATEMENT_FETCH_RATE_LIMIT               = self::PREFIX . 'icici_statement_fetch_rate_limit';

    const ICICI_STATEMENT_FETCH_RATE_LIMIT_RELEASE_DELAY = self::PREFIX . 'icici_statement_fetch_rate_limit_release_delay';

    const RBL_ENABLE_RATE_LIMIT_FLOW                     = self::PREFIX . 'rbl_enable_rate_limit_flow';

    const ICICI_ENABLE_RATE_LIMIT_FLOW                   = self::PREFIX . 'icici_enable_rate_limit_flow';

    // This is used to do correction to closing balance in bank's response
    const RBL_STATEMENT_CLOSING_BALANCE_DIFF = self::PREFIX . 'rbl_statement_closing_balance_diff';

    // This regex is used to scrub credit card numbers from logs.
    // Currently only banking specific routes will be affected by this
    const CREDIT_CARD_REGEX_FOR_REDACTING       = self::PREFIX . 'credit_card_regex_for_redacting';

    //Following regex is used to scrub credit card numbers from logs only for banking specific routes.
    const EMAIL_REGEX_FOR_REDACTING             = self::PREFIX . 'email_regex_for_redacting';
    const PHONE_NUMBER_REGEX_FOR_REDACTING      = self::PREFIX . 'phone_number_regex_for_redacting';
    const CVV_REGEX_FOR_REDACTING               = self::PREFIX . 'cvv_regex_for_redacting';

    const RX_QUEUED_PAYOUTS_PAGINATION          = self::PREFIX . 'rx_queued_payouts_pagination';
    const RX_QUEUED_PAYOUTS_CRON_LAST_RUN_AT    = self::PREFIX . 'rx_queued_payouts_cron_last_run_at';

    const RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT  = self::PREFIX . 'event_notification_config_fts_to_payout';

    const RX_ON_HOLD_PAYOUTS_MERCHANT_SLA       = self::PREFIX . 'rx_on_hold_payouts_merchant_sla';
    const RX_ON_HOLD_PAYOUTS_DEFAULT_SLA        = self::PREFIX . 'rx_on_hold_payouts_default_sla';

    const RX_BLACKLISTED_VPA_REGEXES_FOR_MERCHANT_PAYOUTS = self::PREFIX . 'rx_blacklisted_vpa_regexes_for_merchant_payouts';

    const RX_ICICI_2FA_WEBHOOK_PROCESS_TYPE         = self::PREFIX . 'rx_icici_2fa_webhook_process_type';

    const RX_CA_MISSING_STATEMENTS_RBL              = self::PREFIX . 'rx_ca_missing_statements_rbl';
    const RX_CA_MISSING_STATEMENTS_ICICI            = self::PREFIX . 'rx_ca_missing_statements_icici';
    const RBL_MISSING_STATEMENT_FETCH_MAX_RECORDS   = self::PREFIX . 'rbl_missing_statements_fetch_max_records';
    const ICICI_MISSING_STATEMENT_FETCH_MAX_RECORDS = self::PREFIX . 'icici_missing_statements_fetch_max_records';
    const RETRY_COUNT_FOR_ID_GENERATION             = self::PREFIX . 'retry_count_for_id_generation';
    const RX_MISSING_STATEMENTS_INSERTION_LIMIT     = self::PREFIX . 'rx_missing_statements_insertion_limit';
    const RX_CA_MISSING_STATEMENTS_UPDATION_PARAMS  = self::PREFIX . 'rx_ca_missing_statements_updation_params';
    const RX_CA_MISSING_STATEMENT_DETECTION_RBL     = self::PREFIX . 'rx_ca_missing_statement_detection_rbl';
    const RX_CA_MISSING_STATEMENT_DETECTION_ICICI   = self::PREFIX . 'rx_ca_missing_statement_detection_icici';
    const CA_RECON_PRIORITY_ACCOUNT_NUMBERS         = self::PREFIX . 'ca_recon_priority_account_numbers';

    // this is used to limit the number of records fetched while querying db to get low balance configs in order
    // to reduce the load
    const LOW_BALANCE_CONFIGS_FETCH_LIMIT_IN_ONE_BATCH = self::PREFIX . 'low_balance_configs_fetch_limit_in_one_batch';

    const MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT = self::PREFIX . 'merchant_notification_config_fetch_limit';

    // TODO : Remove after June 15 2020 once we can support 25k bulk payouts
    const RX_PAYOUTS_CUSTOM_BATCH_FILE_LIMIT_MERCHANTS  = self::PREFIX . 'rx_payouts_custom_batch_file_limit_merchants';
    const RX_PAYOUTS_DEFAULT_MAX_BATCH_FILE_COUNT       = self::PREFIX . 'rx_payouts_default_max_batch_file_count';

    const BATCH_PAYOUTS_FETCH_LIMIT                     = self::PREFIX . 'batch_payouts_fetch_limit';

    // Count of the number of free shared account payouts allowed per merchant in a month.
    const FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB1 = self::PREFIX . FreePayout::FREE_SHARED_ACCOUNT_PAYOUTS_COUNT . '_' . FreePayout::SLAB1;

    // Count of the number of free direct account payouts allowed per merchant in a month.
    const FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL_SLAB1 = self::PREFIX . FreePayout::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT . '_' . Channel::RBL . '_' . FreePayout::SLAB1;

    // Count of the number of free direct account payouts for ICICI allowed per merchant in a month.
    const FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_ICICI_SLAB1 = self::PREFIX . FreePayout::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT . '_' . Channel::ICICI . '_' . FreePayout::SLAB1;

    const FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_AXIS_SLAB1 = self::PREFIX . FreePayout::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT . '_' . Channel::AXIS . '_' . FreePayout::SLAB1;

    const FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_YESBANK_SLAB1 = self::PREFIX . FreePayout::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT . '_' . Channel::YESBANK . '_' . FreePayout::SLAB1;

    const FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB2 = self::PREFIX . FreePayout::FREE_SHARED_ACCOUNT_PAYOUTS_COUNT . '_' . FreePayout::SLAB2;

    const FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL_SLAB2 = self::PREFIX . FreePayout::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT . '_' . Channel::RBL . '_' . FreePayout::SLAB2;

    const FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_ICICI_SLAB2 = self::PREFIX . FreePayout::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT . '_' . Channel::ICICI . '_' . FreePayout::SLAB2;

    const FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_AXIS_SLAB2 = self::PREFIX . FreePayout::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT . '_' . Channel::AXIS . '_' . FreePayout::SLAB2;

    const FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_YESBANK_SLAB2 = self::PREFIX . FreePayout::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT . '_' . Channel::YESBANK . '_' . FreePayout::SLAB2;

    const FREE_PAYOUTS_SUPPORTED_MODES = self::PREFIX . FreePayout::FREE_PAYOUTS_SUPPORTED_MODES;

    const DELAY_RUPAY_CAPTURE    = self::PREFIX . 'delay_rupay_capture';

    const PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE = self::PREFIX . PaginationEntity::PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE;

    // This will be the cutoff based on which we shall decide which flow to show for bulk payouts.
    const BULK_PAYOUTS_NEW_MERCHANT_CUTOFF_TIMESTAMP = self::PREFIX . 'bulk_payouts_new_merchant_cutoff_timestamp';

    // Acts as a kill switch for cred eligibility call from /preferences api
    const ENABLE_CRED_ELIGIBILITY_CALL = self::PREFIX . 'enable_cred_eligibility_call';

    const RX_VA_TO_VA_PAYOUTS_WHITELISTED_DESTINATION_MERCHANTS = self::PREFIX . 'rx_va_to_va_payouts_whitelisted_destination_merchants';

    const UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS = self::PREFIX . 'updated_sms_templates_receiver_merchants';

    const SHIFT_BULK_PAYOUT_APPROVE_TO_BULK_APPROVE_PAYOUT_SMS_TEMPLATE = self::PREFIX . 'shift_bulk_payout_approve_to_bulk_approve_payout_sms_template';

    const RX_WEBHOOK_URL_FOR_MFN = self::PREFIX . 'rx_webhook_url_for_mfn';

    const RX_WEBHOOK_URL_FOR_MFN_TEST_MODE = self::PREFIX . 'rx_webhook_url_for_mfn_test_mode';

    const RX_LIMIT_STATEMENT_FIX_ENTITIES_UPDATE = self::PREFIX . 'rx_limit_statement_fix_entities_update';

    const RX_POSTED_DATE_WINDOW_FOR_PREVIOUS_BAS_SEARCH = self::PREFIX . 'rx_posted_date_window_for_previous_bas_search';

    const RX_GLOBALLY_WHITELISTED_PAYER_ACCOUNTS_FOR_FUND_LOADING = self::PREFIX . 'rx_globally_whitelisted_payer_accounts_for_fund_loading';

    // This will be used to get the number of records to save at once in bulk
    const ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE = self::PREFIX . 'account_statement_records_to_save_at_once';

    // This will be used to get the number of records to fetch at once in bulk
    const RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE = self::PREFIX . 'rbl_account_statement_records_to_fetch_at_once';

    // This will be used to get the number of records to fetch at once in bulk
    const ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE = self::PREFIX . 'icici_account_statement_records_to_fetch_at_once';

    // This will be used to get the number of records to process[link with entities] at once in bulk
    const ACCOUNT_STATEMENT_RECORDS_TO_PROCESS_AT_ONCE = self::PREFIX . 'account_statement_records_to_process_at_once';

    // This is the number of times we are going to save records in chunks. This will be revisited. It can be high
    // for some a/cs and low for some.
    const ACCOUNT_STATEMENT_RECORDS_TO_SAVE_IN_TOTAL = self::PREFIX . 'account_statement_records_to_save_in_total';

    // Account numbers for which new flow applies where we divide fetch and process flow
    const ACCOUNT_STATEMENT_V2_FLOW = self::PREFIX . 'account_statement_v2_flow';

    const RBL_DIRECT_ACCOUNTS_ON_SINGLE_PAYMENTS_API = self::PREFIX . 'rbl_direct_accounts_on_single_payments_api';

    const REQUEST_LOG_STATE = self::PREFIX . 'request_log_state';

    const REARCH_CARD_PAYMENTS = self::PREFIX.'rearch_card_payments';

    // this is to route 0loc traffic to scrooge service
    const SCROOGE_0LOC_ENABLED = self::PREFIX.'scrooge_0loc_enabled';

    //Admin config used to control visibility of dcc markup on frontend
    const PAYMENT_SHOW_DCC_MARKUP = self::PREFIX.'payment_show_dcc_markup';

    const MIN_HOURS_TO_START_TICKET_CREATION_AFTER_ACTIVATION_FORM_SUBMISSION
        = self::PREFIX . 'min_hours_to_start_ticket_creation_after_activation_form_submission';

    const MAX_ACTIVATION_PROGRESS_FOR_POPUP_RANGE1
        = self::PREFIX . 'max_activation_progress_for_popup_range1';

    const RX_FUND_LOADING_REFUNDS_VIA_X = self::PREFIX . 'rx_fund_loading_refunds_via_x';

    const ASYNC_ESCALATION_HANDLING_ENABLED = self::PREFIX . 'async_escalation_handling_enabled';

    // This config contains the list of razorpay internal accounts with the following structure
    // [{"merchant_id": "sampleMerchant", "entity": "RZPX", "account_number": "100000000"}]
    // RZPX - Razorpay X, RSPL - Razorpay Private Limited
    const RZP_INTERNAL_ACCOUNTS = self::PREFIX . 'rzp_internal_accounts';

    const RZP_INTERNAL_TEST_ACCOUNTS = self::PREFIX . 'rzp_internal_test_accounts';

    const SUB_BALANCES_MAP = self::PREFIX . 'sub_balance_map';

    // This will be used to get the utrs facing credit before debit issue.
    const BAS_CREDIT_BEFORE_DEBIT_UTRS = self::PREFIX . 'bas_credit_before_debit_utrs';

    // This will be the cutoff based on which we shall include card payments starting from this time in settlement file for a window
    const CARD_PAYMENTS_SETTLEMENT_FILE_CUTOFF_TIMESTAMP = self::PREFIX . 'card_payments_settlement_file_cutoff_timestamp';

    // This will be the cutoff based on which we shall include card refunds starting from this time in settlement file for a window
    const CARD_REFUNDS_SETTLEMENT_FILE_CUTOFF_TIMESTAMP = self::PREFIX . 'card_refunds_settlement_file_cutoff_timestamp';

    // This is the last Timestamp of card ds payments in previous batch of gifu file
    const CARD_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP = self::PREFIX . 'card_ds_payments_last_batch_settlement_file_cutoff_timestamp';

    // This is the last Timestamp of upi ds payments in previous batch of gifu file
    const UPI_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP = self::PREFIX . 'upi_ds_payments_last_batch_settlement_file_cutoff_timestamp';

    // This key will be a flag for creating a DB connection with master instead of slave
    const USE_MASTER_DB_CONNECTION = self::PREFIX . 'use_master_db_connection';

    const ONDEMAND_SETTLEMENT_INTERNAL_MERCHANTS = SELF::PREFIX . 'ondemand_settlement_internal_merchants';

    const MCC_DEFAULT_MARKDOWN_PERCENTAGE = SELF::PREFIX . 'mcc_default_markdown_percentage';

    const MCC_DEFAULT_MARKDOWN_PERCENTAGE_CONFIG = SELF::PREFIX . 'mcc_default_markdown_percentage_config';

    const COMMISSION_FEE_FOR_CC_MERCHANT_PAYOUT = SELF::PREFIX. 'commission_fee_for_cc_merchant_payout';

    const DEFAULT_OPGSP_TRANSACTION_LIMIT_USD = SELF::PREFIX . 'default_opgsp_transaction_limit_usd';


    const SET_CARD_METADATA_NULL = SELF::PREFIX. 'set_card_metadata_null';

    const RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA = self::PREFIX . 'rx_block_non_2fa_non_baas_for_ca';

    // OD balance related keys for direct account
    const RX_OD_BALANCE_CONFIGURED_FOR_MAGICBRICKS = self::PREFIX . 'rx_od_balance_configured_for_magicbricks';

    const RISK_FOH_TEAM_EMAIL_IDS = self::PREFIX . 'risk_foh_team_email_ids';

    const PAYOUT_ASYNC_APPROVE_DISTRIBUTION_RATE_LIMIT = SELF::PREFIX . 'payout_async_approve_distribution_rate_limit';

    const PAYOUT_ASYNC_APPROVE_DISTRIBUTION_WINDOW_LENGTH = SELF::PREFIX . 'payout_async_approve_distribution_window_length';

    const PAYOUT_ASYNC_APPROVE_PROCESSING_RATE_LIMIT = SELF::PREFIX . 'payout_async_approve_processing_rate_limit';

    const PAYOUT_ASYNC_APPROVE_PROCESSING_WINDOW_LENGTH = SELF::PREFIX . 'payout_async_approve_processing_window_length';

    const ACCOUNT_SUB_ACCOUNT_RESTRICTED_PERMISSIONS_LIST = self::PREFIX . 'account_sub_account_restricted_permissions_list';

    // DCS READ ENABLED
    const DCS_READ_WHITELISTED_FEATURES           = self::PREFIX . 'dcs_reads_whitelisted_features';

    const UNEXPECTED_PAYMENT_DELAY_REFUND           = self::PREFIX . 'unexpected_payment_delay_refund';

    const DIRECT_TRANSFER_LIMITS                    = self::PREFIX . 'direct_transfer_limits';

    // default pricing plan values for intl_bank_transfer
    const DEFAULT_PRICING_FOR_ACH                   = self::PREFIX . 'default_pricing_for_ach';

    const DEFAULT_PRICING_FOR_SWIFT                 = self::PREFIX . 'default_pricing_for_swift';

    const PUBLIC_KEYS = [
        self::TENANT_ROLES_ENTITY,
        self::TENANT_ROLES_ROUTES,
        self::ASYNC_ESCALATION_HANDLING_ENABLED,
        self::TERMINAL_SELECTION_LOG_VERBOSE,
        self::PRICING_RULE_SELECTION_LOG_VERBOSE,
        self::THROTTLE_MOCK_LOG_VERBOSE,
        self::GATEWAY_PROCESSED_REFUNDS,
        self::GATEWAY_UNPROCESSED_REFUNDS,
        self::BLOCK_BANK_TRANSFERS_FOR_CRYPTO,
        self::SKIP_SLAVE,
        self::MASTER_PERCENT,
        self::DISABLE_MAGIC,
        self::NPCI_UPI_DEMO,
        self::BLOCK_SMART_COLLECT,
        self::BLOCK_YESBANK,
        self::BLOCK_AADHAAR_REG,
        self::HITACHI_DYNAMIC_DESCR_ENABLED,
        self::CPS_SERVICE_ENABLED,
        self::SETTLEMENT_TRANSACTION_LIMIT,
        self::ENABLE_PAYMENT_DOWNTIMES,
        self::FTS_TEST_MERCHANT,
        self::CURL_INFO_LOG_VERBOSE,
        self::HITACHI_NEW_URL_ENABLED,
        self::PAYSECURE_BLACKLISTED_MCCS,
        self::DOWNTIME_THROTTLE,
        self::RX_SLA_FOR_IMPS_PAYOUT,
        self::WORLDLINE_TID_RANGE_LIST,
        self::FTS_PAYOUT_VPA,
        self::FTS_PAYOUT_CARD,
        self::FTS_PAYOUT_BANK_ACCOUNT,
        self::CARD_PAYMENT_SERVICE_ENABLED,
        self::PG_ROUTER_SERVICE_ENABLED,
        self::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT,
        self::LOW_BALANCE_RX_EMAIL,
        self::GATEWAY_BALANCE_LAST_FETCHED_AT_RATE_LIMITING,
        self::RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT,
        self::ICICI_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE,
        self::CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE,
        self::RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE,
        self::ICICI_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT,
        self::CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT,
        self::RBL_STATEMENT_FETCH_ATTEMPT_LIMIT,
        self::RBL_STATEMENT_FETCH_SPECIAL_ATTEMPT_LIMIT,
        self::RBL_STATEMENT_FETCH_RETRY_LIMIT,
        self::BLOCK_X_REGISTRATION,
        self::BLOCK_YESBANK_RX_FAV,
        self::REMOVE_SETTLEMENT_BA_COOL_OFF,
        self::BLOCK_YESBANK_WALLET_PAYOUTS,
        self::RX_ACCOUNT_NUMBER_SERIES_PREFIX,
        self::RX_SHARED_ACCOUNT_ALLOWED_CHANNELS,
        self::CREDIT_CARD_REGEX_FOR_REDACTING,
        self::EMAIL_REGEX_FOR_REDACTING,
        self::PHONE_NUMBER_REGEX_FOR_REDACTING,
        self::CVV_REGEX_FOR_REDACTING,
        self::RX_QUEUED_PAYOUTS_PAGINATION,
        self::RX_QUEUED_PAYOUTS_CRON_LAST_RUN_AT,
        self::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT,
        self::RX_ON_HOLD_PAYOUTS_MERCHANT_SLA,
        self::RX_ON_HOLD_PAYOUTS_DEFAULT_SLA,
        self::RX_BLACKLISTED_VPA_REGEXES_FOR_MERCHANT_PAYOUTS,
        self::RX_ICICI_2FA_WEBHOOK_PROCESS_TYPE,
        self::RX_CA_MISSING_STATEMENTS_RBL,
        self::RX_CA_MISSING_STATEMENTS_ICICI,
        self::RBL_MISSING_STATEMENT_FETCH_MAX_RECORDS,
        self::ICICI_MISSING_STATEMENT_FETCH_MAX_RECORDS,
        self::RX_PAYOUTS_CUSTOM_BATCH_FILE_LIMIT_MERCHANTS,
        self::RX_PAYOUTS_DEFAULT_MAX_BATCH_FILE_COUNT,
        self::CARD_PAYMENT_SERVICE_EMI_FETCH,
        self::LOW_BALANCE_CONFIGS_FETCH_LIMIT_IN_ONE_BATCH,
        self::MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT,
        self::BATCH_PAYOUTS_FETCH_LIMIT,
        self::ENABLE_NB_KOTAK_ENCRYPTED_FLOW,
        self::FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB1,
        self::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL_SLAB1,
        self::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_ICICI_SLAB1,
        self::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_AXIS_SLAB1,
        self::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_YESBANK_SLAB1,
        self::FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB2,
        self::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL_SLAB2,
        self::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_ICICI_SLAB2,
        self::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_AXIS_SLAB2,
        self::FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_YESBANK_SLAB2,
        self::FREE_PAYOUTS_SUPPORTED_MODES,
        self::DOWNTIME_SLACK_NOTIFICATION_CHANNELS,
        self::ENABLE_PAYMENT_DOWNTIME_CARD,
        self::ENABLE_PAYMENT_DOWNTIME_CARD_ISSUER,
        self::ENABLE_PAYMENT_DOWNTIME_CARD_NETWORK,
        self::ENABLE_PAYMENT_DOWNTIME_NETBANKING,
        self::ENABLE_PAYMENT_DOWNTIME_UPI,
        self::ENABLE_PAYMENT_DOWNTIME_WALLET,
        self::ENABLE_DOWNTIME_SERVICE,
        self::ENABLE_DOWNTIME_SERVICE_CARD,
        self::ENABLE_DOWNTIME_SERVICE_NETBANKING,
        self::ENABLE_DOWNTIME_SERVICE_EMANDATE,
        self::ENABLE_DOWNTIME_SERVICE_UPI,
        self::ENABLE_PAYMENT_DOWNTIME_PHONEPE,
        self::TRANSFER_SYNC_PROCESSING_VIA_API_SEMAPHORE_CONFIG,
        self::TRANSFER_SYNC_PROCESSING_VIA_API_HOURLY_RATE_LIMIT_PER_MID,
        self::RX_BAS_FORCED_FETCH_TIME_IN_HOURS,
        self::DELAY_RUPAY_CAPTURE,
        self::PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE,
        self::BULK_PAYOUTS_NEW_MERCHANT_CUTOFF_TIMESTAMP,
        self::ENABLE_CRED_ELIGIBILITY_CALL,
        self::RX_VA_TO_VA_PAYOUTS_WHITELISTED_DESTINATION_MERCHANTS,
        self::RX_WEBHOOK_URL_FOR_MFN,
        self::RX_WEBHOOK_URL_FOR_MFN_TEST_MODE,
        self::RX_LIMIT_STATEMENT_FIX_ENTITIES_UPDATE,
        self::RX_POSTED_DATE_WINDOW_FOR_PREVIOUS_BAS_SEARCH,
        self::RX_GLOBALLY_WHITELISTED_PAYER_ACCOUNTS_FOR_FUND_LOADING,
        self::RBL_STATEMENT_FETCH_RATE_LIMIT,
        self::RBL_STATEMENT_FETCH_WINDOW_LENGTH,
        self::ICICI_STATEMENT_FETCH_RATE_LIMIT,
        self::ICICI_STATEMENT_FETCH_WINDOW_LENGTH,
        self::RBL_STATEMENT_CLOSING_BALANCE_DIFF,
        self::REQUEST_LOG_STATE,
        self::RBL_BANKING_ACCOUNT_STATEMENT_CRON_ATTEMPT_DELAY,
        self::ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE,
        self::RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE,
        self::RBL_STATEMENT_FETCH_RATE_LIMIT_RELEASE_DELAY,
        self::ICICI_STATEMENT_FETCH_RATE_LIMIT_RELEASE_DELAY,
        self::RBL_ENABLE_RATE_LIMIT_FLOW,
        self::ICICI_ENABLE_RATE_LIMIT_FLOW,
        self::ICICI_STATEMENT_FETCH_ATTEMPT_LIMIT,
        self::ICICI_STATEMENT_FETCH_RETRY_LIMIT,
        self::ACCOUNT_STATEMENT_V2_FLOW,
        self::REARCH_CARD_PAYMENTS,
        self::MIN_HOURS_TO_START_TICKET_CREATION_AFTER_ACTIVATION_FORM_SUBMISSION,
        self::MAX_ACTIVATION_PROGRESS_FOR_POPUP_RANGE1,
        self::RX_FUND_LOADING_REFUNDS_VIA_X,
        self::PAYMENT_SHOW_DCC_MARKUP,
        self::RBL_STATEMENT_FETCH_V2_API_MAX_RECORDS,
        self::PAYER_ACCOUNT_NUMBER_INVALID_REGEXES,
        self::RBL_CA_BALANCE_UPDATE_LIMITS,
        self::RZP_INTERNAL_ACCOUNTS,
        self::RZP_INTERNAL_TEST_ACCOUNTS,
        self::SUB_BALANCES_MAP,
        self::BAS_CREDIT_BEFORE_DEBIT_UTRS,
        self::PAYER_ACCOUNT_NAME_INVALID_REGEXES,
        self::SCROOGE_0LOC_ENABLED,
        self::PAYMENT_ARCHIVAL_FALLBACK_ENABLED,
        self::PAYMENT_ARCHIVAL_EAGER_LOAD,
        self::DATA_WAREHOUSE_CONNECTION_FALLBACK,
        self::CARD_ARCHIVAL_FALLBACK_ENABLED,
        self::PAYMENTS_DUAL_WRITE,
        self::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS,
        self::SHIFT_BULK_PAYOUT_APPROVE_TO_BULK_APPROVE_PAYOUT_SMS_TEMPLATE,
        self::PAYOUT_SERVICE_DATA_MIGRATION_LIMIT_PER_BATCH,
        self::PAYOUT_SERVICE_DATA_MIGRATION_BATCH_ATTEMPTS,
        self::ONDEMAND_SETTLEMENT_INTERNAL_MERCHANTS,
        self::MCC_DEFAULT_MARKDOWN_PERCENTAGE,
        self::MCC_DEFAULT_MARKDOWN_PERCENTAGE_CONFIG,
        self::COMMISSION_FEE_FOR_CC_MERCHANT_PAYOUT,
        self::DEFAULT_OPGSP_TRANSACTION_LIMIT_USD,
        self::PAYOUT_SERVICE_DATA_MIGRATION_BUFFER,
        self::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA,
        self::RISK_FOH_TEAM_EMAIL_IDS,
        self::DCS_READ_WHITELISTED_FEATURES,
        self::UNEXPECTED_PAYMENT_DELAY_REFUND,
        self::DIRECT_TRANSFER_LIMITS,
        self::RX_CA_MISSING_STATEMENT_DETECTION_RBL,
        self::RX_CA_MISSING_STATEMENT_DETECTION_ICICI,
        self::DEFAULT_PRICING_FOR_ACH,
        self::DEFAULT_PRICING_FOR_SWIFT,
    ];

    const REDIS_CONFIG_MAP = [
        self::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [Name::SET_RX_ACCOUNT_PREFIX],
        self::RX_SHARED_ACCOUNT_ALLOWED_CHANNELS => [Name::SET_SHARED_ACCOUNT_ALLOWED_CHANNELS],
        self::DOWNTIME_SLACK_NOTIFICATION_CHANNELS => [Name::CREATE_GATEWAY_DOWNTIME],
        self::ENABLE_DOWNTIME_SERVICE => [Name::CREATE_GATEWAY_DOWNTIME],
        self::ENABLE_DOWNTIME_SERVICE_NETBANKING => [Name::CREATE_GATEWAY_DOWNTIME],
        self::ENABLE_DOWNTIME_SERVICE_EMANDATE => [Name::CREATE_GATEWAY_DOWNTIME],
        self::ENABLE_DOWNTIME_SERVICE_UPI => [Name::CREATE_GATEWAY_DOWNTIME],
        self::ENABLE_DOWNTIME_SERVICE_CARD => [Name::CREATE_GATEWAY_DOWNTIME],
        self::ENABLE_PAYMENT_DOWNTIME_CARD => [Name::CREATE_GATEWAY_DOWNTIME],
        self::ENABLE_PAYMENT_DOWNTIME_CARD_ISSUER => [Name::CREATE_GATEWAY_DOWNTIME],
        self::ENABLE_PAYMENT_DOWNTIME_CARD_NETWORK => [Name::CREATE_GATEWAY_DOWNTIME],
        self::ENABLE_PAYMENT_DOWNTIME_NETBANKING => [Name::CREATE_GATEWAY_DOWNTIME],
        self::ENABLE_PAYMENT_DOWNTIME_UPI => [Name::CREATE_GATEWAY_DOWNTIME],
        self::ENABLE_PAYMENT_DOWNTIME_WALLET => [Name::CREATE_GATEWAY_DOWNTIME],
        self::ENABLE_PAYMENT_DOWNTIMES => [Name::CREATE_GATEWAY_DOWNTIME],
        self::ENABLE_PAYMENT_DOWNTIME_PHONEPE => [Name::CREATE_GATEWAY_DOWNTIME],
        self::RX_GLOBALLY_WHITELISTED_PAYER_ACCOUNTS_FOR_FUND_LOADING => [Name::SET_RX_GLOBALLY_WHITELISTED_PAYER_ACCOUNTS],
        self::RX_VA_TO_VA_PAYOUTS_WHITELISTED_DESTINATION_MERCHANTS => [Name::EDIT_DESTINATION_MIDS_TO_WHITELIST_VA_TO_VA_PAYOUTS],
        self::PAYER_ACCOUNT_NUMBER_INVALID_REGEXES => [Name::SET_PAYER_ACCOUNT_INVALID_REGEX],
        self::USE_MASTER_DB_CONNECTION => [Name::USE_MASTER_DB_CONNECTION],
        self::PAYER_ACCOUNT_NAME_INVALID_REGEXES => [Name::SET_PAYER_NAME_INVALID_REGEX],
        self::RX_ON_HOLD_PAYOUTS_MERCHANT_SLA => [Name::SET_MERCHANT_SLA_FOR_ON_HOLD_PAYOUTS],
        self::RX_BLACKLISTED_VPA_REGEXES_FOR_MERCHANT_PAYOUTS => [Name::SET_BLACKLISTED_VPA_REGEXES_FOR_MERCHANTS],
        self::TENANT_ROLES_ENTITY => [Name::SET_TENANT_ROLES_CONFIG],
        self::DCS_READ_WHITELISTED_FEATURES => [Name::SET_DCS_READ_WRITE_CONFIG],
    ];

    /**
     * @param string $key
     * @return array
     */
    public static function fetchPermissionsForKey(string $key) : array
    {
        return self::REDIS_CONFIG_MAP[$key] ?? [];
    }

    public static function isSensitive(string $key)
    {
        if (in_array($key, self::PUBLIC_KEYS, true) === true)
        {
            return false;
        }

        return true;
    }

    public static function resetFetchedKeys()
    {
        static::$fetchedKeys = [];
    }

    public static function get($key, $default = null)
    {
        if (isset(static::$fetchedKeys[$key]) === true)
        {
            return static::$fetchedKeys[$key];
        }

        $app = App::getFacadeRoot();

        $data = $default;

        try
        {
            $data = Cache::get($key);
        }
        catch (\Throwable $ex)
        {
            $app['trace']->traceException($ex);
        }

        static::$fetchedKeys[$key] = $data;

        return $data;
    }
}
