<?php

namespace RZP\Constants;

/**
 * List of application metric names
 */
class Metric
{
    // Counters type metric names
    const HTTP_REQUESTS_TOTAL                   = 'http_requests_total';
    const ELOQUENT_CACHE_HITS_TOTAL             = 'eloquent_cache_hits_total';
    const ELOQUENT_CACHE_MISSES_TOTAL           = 'eloquent_cache_misses_total';
    const ELOQUENT_CACHE_WRITES_TOTAL           = 'eloquent_cache_writes_total';
    const ELOQUENT_CACHE_FLUSHES_TOTAL          = 'eloquent_cache_flushes_total';
    const ASYNC_JOBS_RECEIVING_TOTAL            = 'async_jobs_receiving_total';
    const ASYNC_JOBS_RECEIVED_TOTAL             = 'async_jobs_received_total';
    const ASYNC_JOBS_PROCESSED_TOTAL            = 'async_jobs_processed_total';
    const ASYNC_JOBS_ERRORS_TOTAL               = 'async_jobs_errors_total';
    const TRACES_TOTAL                          = 'traces_total';

    // Summary type metric names
    const HTTP_REQUEST_DURATION_MICROSECONDS    = 'http_request_duration_microseconds';

    // Labels
    const LABEL_RZP_MODE                        = 'rzp_mode';
    const LABEL_STATUS                          = 'status';
    const LABEL_METHOD                          = 'method';
    const LABEL_ROUTE                           = 'route';
    const LABEL_RZP_KEY_ID                      = 'rzp_key';
    const LABEL_RZP_MERCHANT_ID                 = 'rzp_merchant_id';
    const LABEL_RZP_OAUTH_CLIENT_ID             = 'rzp_oauth_client_id';
    const LABEL_RZP_AUTH                        = 'rzp_auth';
    const LABEL_RZP_INTERNAL_APP_NAME           = 'rzp_internal_app_name';
    const LABEL_ASYNC_JOB_CONNECTION            = 'async_job_connection';
    const LABEL_ASYNC_JOB_QUEUE                 = 'async_job_queue';
    const LABEL_ASYNC_JOB_NAME                  = 'async_job_name';
    const LABEL_TRACE_CHANNEL                   = 'channel';
    const LABEL_TRACE_CODE                      = 'code';
    const LABEL_TRACE_CONTEXT_CODE              = 'context_code';
    const LABEL_TRACE_LEVEL                     = 'level';
    const LABEL_TRACE_LEVEL_NAME                = 'level_name';
    const LABEL_INSTANCE                        = 'instance';

    // Default label values
    const LABEL_DEFAULT_VALUE                   = 'other';
    const LABEL_NONE_VALUE                      = 'none';
}
