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
    const ASV_HTTP_CLIENT_REQUEST_TOTAL         = 'asv_http_client_request_total';
    const ASV_HTTP_CLIENT_RESPONSE_TOTAL        = 'asv_http_client_response_total';
    const ASV_COMPARE_MISMATCH                  = 'asv_compare_mismatch';
    const ASV_REQUEST_NOT_ROUTED_TO_ASV         = 'asv_request_not_routed_to_asv';
    const DB_REQUESTS_BEFORE_MIGRATION          = 'db_requests_before_migration';
    const ASV_SYNC_ACCOUNT_DEVIATION_FAILED     = 'asv_sync_account_deviation_failed';
    const DUAL_WRITES_TOTAL                     = 'dual_writes_total';
    const DUAL_WRITES_FAILED                    = 'dual_writes_failed';
    const DUAL_WRITES_TIME_TAKEN                = 'dual_writes_time_taken';
    const ARCHIVED_ENTITY_FETCH_TOTAL           = 'archived_entity_fetch_total';
    const ARCHIVED_ENTITY_FETCH_SUCCESS         = 'archived_entity_fetch_success';
    const ARCHIVED_ENTITY_FETCH_TIME_TAKEN      = 'archived_entity_fetch_time_taken';
    const DB_CONNECTION_CLASSIFICATION          = 'db_connection_classification';
    const MERCHANT_RELATED_ENTITIES_READ_TRAFFIC_TOTAL = 'merchant_related_entities_read_traffic_total';
    const MERCHANT_ENTITIES_READ_AFTER_WRITE_TOTAL = 'merchant_entities_read_after_write_total';
    const MERCHANT_RELATED_ENTITIES_WRITE_TRAFFIC_TOTAL = 'merchant_related_entities_write_traffic_total';
    const RAVEN_REQUEST_FAILED                  = 'raven_request_failed';

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
    const LABEL_RZP_TEAM                        = 'rzp_team';
    const LABEL_RZP_LATENCY_GROUP               = 'rzp_latency_group';
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
    const LABEL_EVENT_NAME                      = 'event_name';
    const LABEL_HOST                            = 'host';
    const LABEL_TABLE_NAME                      = 'table_name';
    const LABEL_ACTION                          = 'action';
    const LABEL_MESSAGE                         = 'message';
    const LABEL_IS_SUCCESS                      = 'is_success';
    const LABEL_ERROR_CODE                      = 'error_code';
    const LABEL_ROUTE_NAME                      = 'route_name';
    const LABEL_BANK_CODE                       = 'bank_code';

    // Default label values
    const LABEL_DEFAULT_VALUE                   = 'other';
    const LABEL_NONE_VALUE                      = 'none';

    // Additional label values
    const TYPE_QUERY_CACHE                      = 'query_cache';
    const TYPE_UPI_POLLING                      = 'upi_polling';

    const QUEUE_JOB_ATTEMPT_COUNT               = 'queue_job_attempt_count';
    const QUEUE_JOB_WORKER_TIMEOUT              = 'queue_job_worker_timeout';
    const QUEUE_JOB_WORKER_EXCEPTION            = 'queue_job_worker_exception';

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

    const KAFKA_ADJUSTMENT_API_TXN_PUSH_SUCCESS                 = 'kafka_adjustment_api_txn_push_success';
    const KAFKA_ADJUSTMENT_API_TXN_PUSH_FAILURE                 = 'kafka_adjustment_api_txn_push_failure';
    const PG_LEDGER_KAFKA_PUSH_FAILURE                          = 'pg_ledger_kafka_push_failure';
    const PG_LEDGER_OUTBOX_CRON_RETRIES_EXHAUSTED               = 'pg_ledger_outbox_cron_retries_exhausted';
    const PG_LEDGER_KAFKA_ACKNOWLEDGMENT_RECEIVED_FROM_PG       = 'pg_ledger_kafka_acknowledgement_received_from_pg';
    const PG_LEDGER_KAFKA_ACKNOWLEDGMENT_RECEIVED_FROM_LEDGER   = 'pg_ledger_kafka_acknowledgement_received_from_ledger';
    const LEDGER_JOURNAL_FETCH_TRANSACTION_ERROR_TOTAL          = 'ledger_journal_fetch_transaction_error_total';
    const REFUND_API_TXN_KAFKA_PUSH_FAILURE                     = 'refund_api_txn_kafka_push_failure';
    const PG_LEDGER_ACK_WORKER_FAILURE                          = 'pg_ledger_ack_worker_failure';
    const DB_TRANSACTION                                        = 'db_transaction';

}
