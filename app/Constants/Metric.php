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
    const ASYNC_JOBS_RECIEVING_TOTAL            = 'async_jobs_recieving_total';
    const ASYNC_JOBS_RECIEVED_TOTAL             = 'async_jobs_recieved_total';
    const ASYNC_JOBS_PROCESSED_TOTAL            = 'async_jobs_processed_total';
    const ASYNC_JOBS_ERRORS_TOTAL               = 'ASYNC_JOBS_ERRORS_TOTAL';

    // Summary type metric names
    const HTTP_REQUEST_SIZE_BYTES               = 'http_request_size_bytes';
    const HTTP_REQUEST_DURATION_MICROSECONDS    = 'http_request_duration_microseconds';
    const HTTP_RESPONSE_SIZE_BYTES              = 'http_response_size_bytes';

    // Labels
    const LABEL_RZP_MODE                        = 'rzp_mode';
    const LABEL_STATUS                          = 'status';
    const LABEL_METHOD                          = 'method';
    const LABEL_RZP_KEY_ID                      = 'rzp_key';
    const LABEL_RZP_MERCHANT_ID                 = 'rzp_merchant_id';
    const LABEL_RZP_OAUTH_CLIENT_ID             = 'rzp_oauth_client_id';
    const LABEL_RZP_AUTH                        = 'rzp_auth';
    const LABEL_RZP_INTERNAL_APP_NAME           = 'rzp_internal_app_name';
    const LABEL_ASYNC_JOB_CONNECTION            = 'async_job_connection';
    const LABEL_ASYNC_JOB_QUEUE                 = 'async_job_queue';
    const LABEL_ASYNC_JOB_NAME                  = 'async_job_name';

    // Default label values
    const LABEL_DEFAULT_VALUE                   = 'other';
}
