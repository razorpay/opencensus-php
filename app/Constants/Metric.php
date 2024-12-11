<?php

namespace RZP\Constants;

/**
 * List of application metric names
 */
class Metric
{
    const CF_REQUEST_LATENCY_MILLISECONDS       = 'cf_request_latency_milliseconds';
    const SHIELD_SLACK_ALERT_METRIC             = 'shield_slack_alert_metric';

    // Counters type metric names
    const HTTP_REQUESTS_TOTAL                   = 'http_requests_total';
    const CACHE_HITS_TOTAL                      = 'cache_hits_total';
    const CACHE_MISSES_TOTAL                    = 'cache_misses_total';
    const CACHE_WRITES_TOTAL                    = 'cache_writes_total';
    const CACHE_FLUSHES_TOTAL                   = 'cache_flushes_total';
    const ASYNC_JOBS_RECEIVING_TOTAL            = 'async_jobs_receiving_total';
    const ASYNC_JOBS_RECEIVED_TOTAL             = 'async_jobs_received_total';
    const ASYNC_JOBS_PROCESSED_TOTAL            = 'async_jobs_processed_total';
    const ASYNC_JOBS_ERRORS_TOTAL               = 'async_jobs_errors_total';
    const SESSIONS_REDIS_CLUSTER_MISS           = 'sessions_redis_cluster_miss';
    const SESSIONS_REDIS_READ_MISS              = 'sessions_redis_read_miss';
    const VAULT_MIGRATION_READ_MISS             = 'vault_migration_read_miss';
    const ENTITY_RETRIEVED                      = 'entity_retrieved';
    const ENTITY_CREATED                        = 'entity_created';
    const ENTITY_UPDATED                        = 'entity_updated';
    const ENTITY_DELETED                        = 'entity_deleted';
    const ACS_SYNC_ALERT_UNREPORTED_ACCOUNTS    = 'acs_sync_alert_unreported_accounts';
    const ACS_SYNC_ALERT_UNKNOWN_MODE           = 'acs_sync_alert_unknown_mode';
    const ACS_SYNC_ALERT_UNKNOWN_OUTBOX_JOB     = 'acs_sync_alert_unknown_outbox_job';
    const ACS_SYNC_EVENT_PUBLISHED              = 'acs_sync_event_published';
    const ACS_SYNC_ALERT_EVENT_PUBLISH_FAILED   = 'acs_sync_alert_event_publish_failed';
    const ACS_SYNC_ALERT_UNKNOWN_TRIGGER        = 'acs_sync_alert_unknown_trigger';
    const ASV_SYNC_ACCOUNT_DEVIATION_TOTAL      = 'asv_sync_account_deviation_total';
    const ASV_ROLLBACK_EVENT_PUBLISHED          = 'asv_rollback_event_published';
    const ASV_ROLLBACK_EVENT_PUBLISH_FAILED     = 'asv_rollback_event_publish_failed';
    const TXN_LEVELS_MISMATCH                   = 'txn_levels_mismatch';
    const ASV_HTTP_CLIENT_REQUEST_TOTAL         = 'asv_http_client_request_total';
    const ASV_HTTP_CLIENT_RESPONSE_TOTAL        = 'asv_http_client_response_total';
    const ASV_COMPARE_MISMATCH                  = 'asv_compare_mismatch';
    const ASV_REQUEST_NOT_ROUTED_TO_ASV         = 'asv_request_not_routed_to_asv';

    const ASV_WRITE_REQUEST_ERROR              = 'asv_write_request_error';

    const ASV_DELETE_REQUEST_ERROR              = 'asv_delete_request_error';
    const ASV_WRITE_REQUEST_ROUTER_ERROR              = 'asv_router_error';

    const ASV_WRITE_REQUEST_ROUTER_RESULT       = 'asv_write_request_router_result';

    const KEY_WRITE_REQUEST       = 'key_write_request_result';
    const KEY_READ_REQUEST       = 'key_read_request_result';

    const EDIT_EMAIL_REQUEST_USER_MERCHANT_ACTIVATED            = 'EDIT_EMAIL_REQUEST_USER_MERCHANT_ACTIVATED';
    const EDIT_EMAIL_REQUEST_USER_MERCHANT_DEACTIVATED          = 'EDIT_EMAIL_REQUEST_USER_MERCHANT_DEACTIVATED';

    const EDIT_MOBILE_REQUEST_USER_MERCHANT_ACTIVATED           = 'EDIT_MOBILE_REQUEST_USER_MERCHANT_ACTIVATED';
    const EDIT_MOBILE_REQUEST_USER_MERCHANT_DEACTIVATED         = 'EDIT_MOBILE_REQUEST_USER_MERCHANT_DEACTIVATED';

    const ASV_WRITE_MERCHANT_AND_MERCHANT_DETAIL_ROUTER_RESULT    = 'asv_write_merchant_and_merchant_detail_request_router_result';

    const ASV_FILTER_ROUTING_RESULT    = 'asv_filter_routing_result';
    const TIDB_FILTER_ROUTING_RESULT    = 'tidb_filter_routing_result';
    const DB_REQUESTS_BEFORE_MIGRATION          = 'db_requests_before_migration';
    const ASV_SYNC_ACCOUNT_DEVIATION_FAILED     = 'asv_sync_account_deviation_failed';
    const DUAL_WRITE_ENTITIES_USAGE     = 'dual_write_entities_usage';

    const ACCOUNT_SERVICE_CHECK_EXCLUSION_FLOW_RESULT = 'account_service_check_exclusion_flow_result';

    const ASV_CHANGE_ISOLATION_LEVEL_EXCEPTION_TOTAL = 'asv_change_isolation_level_exception_total';
    const ASV_TIDB_MIGRATION_SHADOW_MODE_DIFF_TOTAL = 'asv_tidb_migration_shadow_mode_diff_total';

    const ACCOUNT_SERVICE_CHECK_WRITE_FLOW_RESULT = 'account_service_check_write_flow_result';

    const ASV_REQUEST_NOT_ROUTED = 'asv_read_request_not_routed';


    const ASV_ENTITIES_STOP_WRITES_TO_API_DB = 'asv_entities_stop_writes_to_api_db';
    const ASV_READ_REQUEST_ROUTING_RESULT = 'asv_read_request_routing_result';

    const ASV_FALLBACK_TO_API_DB_RESULT = 'asv_fallback_to_api_db_result';

    const ASV_READ_REQUEST_ROUTED_FOR_WRITE_FLOW_RESULT = 'asv_read_request_routed_for_write_flow_result';
    const DUAL_WRITES_TOTAL                     = 'dual_writes_total';
    const DUAL_WRITES_FAILED                    = 'dual_writes_failed';
    const DUAL_WRITES_TIME_TAKEN                = 'dual_writes_time_taken';
    const ARCHIVED_ENTITY_FETCH_TOTAL           = 'archived_entity_fetch_total';
    const ARCHIVED_ENTITY_FETCH_SUCCESS         = 'archived_entity_fetch_success';
    const ARCHIVED_ENTITY_FETCH_TIME_TAKEN      = 'archived_entity_fetch_time_taken';
    const DB_CONNECTION_CLASSIFICATION          = 'db_connection_classification';
    const MERCHANT_RELATED_ENTITIES_READ_TRAFFIC_TOTAL = 'merchant_related_entities_read_traffic_total';

    const MERCHANT_RELATED_ENTITIES_READ_CONNECTIONS_TOTAL = 'merchant_related_entities_read_connections_total';
    const MERCHANT_ENTITIES_READ_AFTER_WRITE_TOTAL = 'merchant_entities_read_after_write_total';
    const MERCHANT_RELATED_ENTITIES_WRITE_TRAFFIC_TOTAL = 'merchant_related_entities_write_traffic_total';
    const RAVEN_REQUEST_FAILED                  = 'raven_request_failed';

    //Payment error tracking
    const PAYMENTS_ERROR = 'payments_error';

    //Merchant level metric
    const MERCHANT_HTTP_REQUESTS_TOTAL                   = 'merchant_http_requests_total';
    const MERCHANT_HTTP_REQUEST_LATENCY_MILLISECONDS     = 'merchant_http_request_latency_milliseconds.histogram';

    // Summary type metric names
    // Using '.histogram' as suffix for pattern match to work(refer statsd_mapping.yml) for statsd_exporter
    const HTTP_REQUEST_DURATION_MILLISECONDS    = 'http_request_duration_milliseconds.histogram';
    const HTTP_REQUEST_LATENCY_MILLISECONDS     = 'http_request_latency_milliseconds.histogram';

    const HTTP_OUTGOING_REQUEST_SIZE            = 'http_outgoing_request_size.histogram';
    const HTTP_OUTGOING_RESPONSE_SIZE           = 'http_outgoing_response_size.histogram';

    const HTTP_REQUEST_SIZE                     = 'http_request_size.histogram';
    const HTTP_RESPONSE_SIZE                    = 'http_response_size.histogram';

    const TRANSACTION_DURATION_MILLISECONDS     = 'transaction_duration_milliseconds.histogram';
    const ASV_SYNC_ACCOUNT_DEVIATION_DURATION_MS = 'asv_sync_account_deviation_duration_ms.histogram';
    const ASYNC_TRANSACTION_DURATION_SECONDS     = 'async_transaction_duration_secs.histogram';
    const ASV_HTTP_CLIENT_RESPONSE_DURATION_MS   = 'asv_http_client_response_duration_ms';

    // Labels
    const LABEL_RZP_MODE                        = 'rzp_mode';
    const LABEL_OUTBOX_JOB                      = 'outbox_job';
    const LABEL_STATUS                          = 'status';
    const LABEL_STATUS_CODE                     = 'status_code';
    const LABEL_METHOD                          = 'method';
    const LABEL_ROUTE                           = 'route';
    const LABEL_RZP_KEY_ID                      = 'rzp_key';
    const LABEL_RZP_MERCHANT_ID                 = 'rzp_merchant_id';
    const LABEL_RZP_OAUTH_CLIENT_ID             = 'rzp_oauth_client_id';
    const LABEL_RZP_AUTH                        = 'rzp_auth';
    const LABEL_RZP_PRODUCT                     = 'rzp_product';
    const LABEL_RZP_INTERNAL_APP_NAME           = 'rzp_internal_app_name';
    const LABEL_RZP_AUTH_FLOW_TYPE              = 'rzp_auth_flow_type';
    const LABEL_RZP_KEY_SOURCE                  = 'rzp_key_source';
    const LABEL_RZP_ACCOUNT_ID_SOURCE           = 'rzp_account_id_source';
    const LABEL_RZP_TEAM                        = 'rzp_team';
    const LABEL_RZP_LATENCY_GROUP               = 'rzp_latency_group';
    const LABEL_RZP_PAYMENT_METHOD              = 'rzp_payment_method';
    const LABEL_ASYNC_JOB_CONNECTION            = 'async_job_connection';
    const LABEL_ASYNC_JOB_QUEUE                 = 'async_job_queue';
    const LABEL_ASYNC_JOB_NAME                  = 'async_job_name';
    const LABEL_TRACE_CHANNEL                   = 'channel';
    const LABEL_TRACE_CODE                      = 'code';
    const LABEL_TRACE_FIELD                     = 'field';
    const LABEL_TRACE_SOURCE                    = 'source';
    const LABEL_TRACE_EXCEPTION_CLASS           = 'exception_class';
    const LABEL_TRACE_CONTEXT_CODE              = 'context_code';
    const LABEL_TRACE_LEVEL                     = 'level';
    const LABEL_TRACE_LEVEL_NAME                = 'level_name';
    const LABEL_INSTANCE                        = 'instance';
    const LABEL_TYPE                            = 'type';
    const LABEL_HAS_PASSPORT                    = 'has_passport';
    const LABEL_ENTITY_NAME                     = 'entity';

    const LABEL_DB_CONNECTION_NAME                    = 'db_connection_name';
    const LABEL_EVENT_NAME                      = 'event_name';
    const LABEL_HOST                            = 'host';
    const LABEL_TABLE_NAME                      = 'table_name';
    const LABEL_ACTION                          = 'action';
    const LABEL_MESSAGE                         = 'message';
    const LABEL_IS_SUCCESS                      = 'is_success';
    const LABEL_ERROR_CODE                      = 'error_code';
    const LABEL_ROUTE_NAME                      = 'route_name';
    const LABEL_BANK_CODE                       = 'bank_code';
    const LABEL_MERCHANT_ID                     = 'merchant_id';
    const LABEL_INTERNAL_ERROR_CODE             = 'internal_error_code';

    // Default label values
    const LABEL_DEFAULT_VALUE                   = 'other';
    const LABEL_NONE_VALUE                      = 'none';

    // Additional label values
    const TYPE_QUERY_CACHE                      = 'query_cache';
    const TYPE_UPI_POLLING                      = 'upi_polling';

    const QUEUE_JOB_ATTEMPT_COUNT               = 'queue_job_attempt_count';
    const QUEUE_JOB_WORKER_TIMEOUT              = 'queue_job_worker_timeout';
    const QUEUE_JOB_WORKER_EXCEPTION            = 'queue_job_worker_exception';

    const RAZORPAYX_PAYOUTS_BANKING_QUEUES_TIMEOUT_COUNT = 'razorpayx_payouts_banking_queues_timeout_count';

    // Order Outbox
    const ORDER_OUTBOX_SOFT_DELETE_FAILURE                  = 'order_outbox_soft_delete_failure';
    const ORDER_OUTBOX_CRON_RETRY_FAILURE                   = 'order_outbox_cron_retry_failure';
    const ORDER_OUTBOX_SYNC_UPDATE_FAILURE                  = 'order_outbox_sync_update_failure';

    // PG ledger Reverse shadow

    const PG_LEDGER_OUTBOX_PUSH_FAILURE                         = 'pg_ledger_outbox_push_failure';
    const PG_LEDGER_OUTBOX_PUSH_SUCCESS                         = 'pg_ledger_outbox_push_success';
    const REFUND_TRANSACTION_NOT_FOUND                          = 'refund_transaction_not_found';
    const PG_LEDGER_OUTBOX_SOFT_DELETE_FAILURE                  = 'pg_ledger_outbox_soft_delete_failure';
    const PG_LEDGER_OUTBOX_SOFT_DELETE_SUCCESS                  = 'pg_ledger_outbox_soft_delete_success';
    const LEDGER_REVERSE_SHADOW_JOURNAL_CREATE_FAILURE          = "ledger_reverse_shadow_journal_create_failure";
    const LEDGER_ACCOUNT_NOT_FOUND                              = "ledger_account_not_found";
    const MULTIPLE_LEDGER_ACCOUNTS_FOUND                        = "multiple_ledger_accounts_found";
    const PG_LEDGER_CREATE_JOURNAL_ENTRY_SUCCESS                = 'pg_ledger_create_journal_entry_success';
    const PG_LEDGER_OUTBOX_CRON_RETRY_FAILURE                   = 'pg_ledger_outbox_cron_retry_failure';
    const PG_LEDGER_CREATE_TRANSACTION_SUCCESS                  = 'pg_ledger_create_transaction_success';
    const PG_LEDGER_CREATE_TRANSACTION_FAILURE                  = 'pg_ledger_create_transaction_failure';
    const PG_LEDGER_OUTBOX_UPDATE_RETRY_COUNT_SUCCESS           = 'pg_ledger_outbox_update_retry_count_success';
    const PG_LEDGER_OUTBOX_UPDATE_RETRY_COUNT_FAILURE           = 'pg_ledger_outbox_update_retry_count_failure';
    const LEDGER_ACCOUNT_CREATION_FAILURE                       = 'ledger_account_creation_failure';
    const PG_LEDGER_API_TRANSACTION_JOURNAL_ID_MISMATCH         = 'pg_ledger_api_transaction_journal_id_mismatch';

    const API_LEDGER_DUAL_WRITE_EVENT_SUCCESS                   = 'api_ledger_dual_write_event_success';
    const API_LEDGER_DUAL_WRITE_EXPECTED_FUND_ACCOUNT_MISSING   = 'api_ledger_dual_write_expected_fund_account_missing';
    const API_LEDGER_DUAL_WRITE_INVALID_PAYLOAD                 = 'api_ledger_dual_write_invalid_payload';

    const CLS_ONBOARDING_FAILURE_ADJUSTMENT_CREATION            = 'cls_onboarding_failure_adjustment_creation';

    const PG_LEDGER_AMOUNT_CREDIT_EXPIRY_REMINDER_CREATED       = 'pg_ledger_amount_credit_expiry_reminder_created';
    const PG_LEDGER_AMOUNT_CREDIT_EXPIRY_REMINDER_FAILURE       = 'pg_ledger_amount_credit_expiry_reminder_failure';

    // Pricing
    const SERVER_ERROR_NO_PRICING_RULE_FOUND                    = 'server_error_no_pricing_rule_found';
    const SERVER_ERROR_MULTIPLE_PRICING_RULES_FOUND             = 'server_error_multiple_pricing_rules_found';
    const MERCHANT_ON_DEMAND_PRICING_FETCH_PLAN_MISMATCH        = 'merchant_on_demand_pricing_fetch_plan_mismatch';

    const PRICING_WRITE_REQUEST      = 'pricing_write_request_result';
    const PRICING_DELETE_REQUEST     = 'pricing_delete_request_result';

    //ChargeCollections
    const CC_REQUEST_NOT_ROUTED                                 = 'cc_request_not_routed';
    const CC_REQUEST_ROUTED                                     = 'cc_request_routed';
    const CC_ROUTER_RESPONSE_MISMATCH                           = 'cc_router_response_mismatch';
    const CC_ROUTER_PRICING_LEGACY_CALL_TIME                    = 'cc_router_pricing_legacy_call_time';
    const CC_ROUTER_SPLITZ_RESPONSE_TIME                        = 'cc_router_splitz_response_time';
    const CC_ROUTER_TOTAL_TIME                                  = 'cc_router_total_time';
    const CHARGE_COLLECTIONS_RESPONSE_TIME                      = 'charge_collections_response_time';


    // External repo for Route
    const EXTERNAL_TRANSFER_REPO_FETCH_FAILURE                  = 'ext_trf_repo_fetch_failure_count';
    const EXTERNAL_TRANSFER_REPO_FETCH_FAILURE_TIME_TAKEN       = 'ext_trf_repo_fetch_failure_time_taken';
    const EXTERNAL_TRANSFER_REPO_FETCH_SUCCESS                  = 'ext_trf_repo_fetch_success_count';
    const EXTERNAL_TRANSFER_REPO_FETCH_SUCCESS_TIME_TAKEN       = 'ext_trf_repo_fetch_success_time_taken';
    const EXTERNAL_LA_PAYMENT_REPO_FETCH_FAILURE                = 'ext_la_pay_repo_fetch_failure_count';
    const EXTERNAL_LA_PAYMENT_REPO_FETCH_FAILURE_TIME_TAKEN     = 'ext_la_pay_repo_fetch_failure_time_taken';
    const EXTERNAL_LA_PAYMENT_REPO_FETCH_SUCCESS                = 'ext_la_pay_repo_fetch_success_count';
    const EXTERNAL_LA_PAYMENT_REPO_FETCH_SUCCESS_TIME_TAKEN     = 'ext_la_pay_repo_fetch_success_time_taken';


    const KAFKA_ADJUSTMENT_API_TXN_PUSH_SUCCESS                 = 'kafka_adjustment_api_txn_push_success';
    const KAFKA_TRANSFER_API_TXN_PUSH_SUCCESS                   = 'kafka_transfer_api_txn_push_success';
    const KAFKA_PAYMENT_API_TXN_PUSH_SUCCESS                    = 'kafka_payment_api_txn_push_success';
    const KAFKA_ADJUSTMENT_API_TXN_PUSH_FAILURE                 = 'kafka_adjustment_api_txn_push_failure';
    const KAFKA_TRANSFER_API_TXN_PUSH_FAILURE                   = 'kafka_transfer_api_txn_push_failure';
    const KAFKA_PAYMENT_API_TXN_PUSH_FAILURE                    = 'kafka_payment_api_txn_push_failure';
    const KAFKA_REVERSAL_API_TXN_PUSH_SUCCESS                   = 'kafka_reversal_api_txn_push_success';
    const KAFKA_REVERSAL_API_TXN_PUSH_FAILURE                   = 'kafka_reversal_api_txn_push_failure';
    const KAFKA_TRANSFER_REVERSAL_API_TXN_PUSH_SUCCESS          = 'kafka_transfer_reversal_api_txn_push_success';
    const KAFKA_TRANSFER_REVERSAL_API_TXN_PUSH_FAILURE          = 'kafka_transfer_reversal_api_txn_push_failure';
    const PG_LEDGER_KAFKA_PUSH_FAILURE                          = 'pg_ledger_kafka_push_failure';
    const PG_LEDGER_OUTBOX_CRON_RETRIES_EXHAUSTED               = 'pg_ledger_outbox_cron_retries_exhausted';
    const PG_LEDGER_KAFKA_ACKNOWLEDGMENT_RECEIVED_FROM_PG       = 'pg_ledger_kafka_acknowledgement_received_from_pg';
    const PG_LEDGER_KAFKA_ACKNOWLEDGMENT_RECEIVED_FROM_LEDGER   = 'pg_ledger_kafka_acknowledgement_received_from_ledger';

    const PG_LEDGER_ACKNOWLEDGMENT_RETRY_COUNT_ATTEMPT          = 'pg_ledger_acknowledgement_retry_count_attempt';

    const PG_LEDGER_DUAL_WRITE_RETRY_COUNT_ATTEMPT              = 'pg_ledger_dual_write_retry_count_attempt';
    const PG_LEDGER_KAFKA_ACKNOWLEDGMENT_PROCESSED              = 'pg_ledger_kafka_acknowledgement_processed';

    const PG_LEDGER_KAFKA_ACKNOWLEDGMENT_FAILED                 = 'pg_ledger_kafka_acknowledgement_failed';

    const PG_LEDGER_KAFKA_DUAL_WRITE_FAILED                     = 'pg_ledger_kafka_dual_write_failed';
    const LEDGER_JOURNAL_FETCH_TRANSACTION_ERROR_TOTAL          = 'ledger_journal_fetch_transaction_error_total';
    const REFUND_API_TXN_KAFKA_PUSH_FAILURE                     = 'refund_api_txn_kafka_push_failure';
    const PG_LEDGER_ACK_WORKER_FAILURE                          = 'pg_ledger_ack_worker_failure';
    const DB_TRANSACTION                                        = 'db_transaction';
    const PG_LEDGER_REVERSE_SHADOW_ONBOARD_FAILURE              = 'pg_ledger_reverse_shadow_onboard_failure';
    const TRANSFER_REVERSAL_API_TXN_DISPATCH_TO_QUEUE_FAILURE   = 'transfer_reversal_api_txn_dispatch_to_queue_failure';
    const TRANSFER_REVERSAL_API_TXN_JOB_RETRY_EXHAUSTED         = 'transfer_reversal_api_txn_job_retry_exhausted';
    const TRANSFER_REVERSAL_TXN_CREATE_FROM_QUEUE_FAILURE       = 'transfer_reversal_api_txn_create_from_queue_failure';

    const PROXYSQL_OR_DB_CONNECTION                             = 'proxysql_or_db_connection';

    const CIRCUIT_BREAKER_OPEN                                  = 'circuit_breaker_open';

    const PGOS_DUAL_WRITE_CONSUMER_ERROR                        = 'pgos_dual_write_consumer_error';

    const SETTLEMENT_ONDEMAND_GLOBAL_LIMIT_BREACHED             = 'settlement_ondemand_global_limit_breached';

    const SETTLEMENT_ONDEMAND_INVALID_CAPPING_SCALE_FACTOR      = 'settlement_ondemand_invalid_capping_scale_factor';

    const SETTLEMENT_ONDEMAND_ALLOWED_LIMIT_BREACHED            = 'settlement_ondemand_allowed_limit_breached';

    const SETTLEMENT_ONDEMAND_MERCHANT_LIMIT_BREACHED           = 'settlement_ondemand_merchant_limit_breached';

    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_EXCEPTION          = 'settlement_ondemand_feature_config_exception';

    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_NOT_FOUND          = 'settlement_ondemand_feature_config_not_found';

    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_LIMIT_NOT_FOUND = 'settlement_ondemand_feature_config_max_limit_not_found';

    const BANK_TRANSFER_CREATE_PROCESS_JOB_INIT_COUNT            = 'bank_transfer_create_process_job_init_count';

    const OPGSP_UFH_FILE_PUSH                                   = 'opgsp_ufh_file_push';
    const OPGSP_BEAM_PUSH                                       = 'opgsp_beam_push';
    const OPGSP_FILE_SEND_STARTED                               = 'opgsp_file_send_started';
    const OPGSP_IMPORT_NO_SETTLEMENTS_FOUND                     = 'opgsp_import_no_settlements_found';
    const API_DECOMP_ENTITY_FETCH                               = 'api_decomp_entity_fetch';
    const API_DECOMP_PARAMETERS                                 = 'api_decomp_parameters';
    const API_DECOMP_AUTH_DISTRIBUTION                          = 'api_decomp_auth_distribution';

    const CREDCASE_READ_COUNT_MISMATCH = 'credcase_read_count_mismatch';

    const CREDCASE_READ_RESPONSE_MISMATCH = 'credcase_read_response_mismatch';

    const CREDCASE_REQUEST_FAILED = 'credcase_request_failed';
    const CREDCASE_REQUEST_LATENCY_MS = 'credcase_request_latency_milliseconds.histogram';

}
