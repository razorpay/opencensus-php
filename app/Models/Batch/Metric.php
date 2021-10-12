<?php

namespace RZP\Models\Batch;

/**
 * List of metrics in Batch/ module
 */
final class Metric
{
    // Counters
    const BATCH_REQUESTS_TOTAL          = "batch_requests_total";

    // Histograms
    const BATCH_PROCESSED_ROWS_TOTAL         = "batch_processed_rows_total";
    const BATCH_FAILED_ROWS_TOTAL            = "batch_failed_rows_total";
    const BATCH_SUCCESS_ROWS_TOTAL           = "batch_success_rows_total";
    const BATCH_REQUEST_PROCESS_TIME_MS      = "batch_request_process_time_ms";
    const BATCH_CREATE_TOTAL_PROCESS_TIME_MS = "batch_create_total_process_time_ms";
    const BATCH_ROW_PROCESS_TIME_MS          = "batch_row_process_time_ms";
}
