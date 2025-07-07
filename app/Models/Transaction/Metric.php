<?php

namespace RZP\Models\Transaction;

/**
 * List of metrics in Transaction/ module
 */
final class Metric
{
    // Counters
    const TRANSACTION_VA_REQUEST_TOTAL                            = 'transaction_va_request_total';
    const TRANSACTION_CA_REQUEST_TOTAL                            = 'transaction_ca_request_total';
    const TRANSACTION_LEGACY_REQUEST_TOTAL                        = 'transaction_legacy_request_total';
    const TRANSACTION_CREATED_EVENT_TOTAL                         = 'transaction_created_event_total';

    // Errors Counters
    const TRANSACTION_VA_REQUEST_ERROR_COUNT                      = 'transaction_va_request_error_count';

    // Histogram
    const TRANSACTION_VA_REQUEST_LATENCY_MILLISECONDS             = 'transaction_va_request_latency_ms';
    const TRANSACTION_CA_REQUEST_LATENCY_MILLISECONDS             = 'transaction_ca_request_latency_ms';
    const TRANSACTION_LEGACY_REQUEST_LATENCY_MILLISECONDS         = 'transaction_legacy_request_latency_ms';
    const TRANSACTION_READ_API_LEDGER_CLS_MERCHANT                = 'transaction_read_api_ledger_cls_merchant';
    const TRANSACTION_WRITE_API_LEDGER_CLS_MERCHANT               = 'transaction_write_api_ledger_cls_merchant';
    const X_TRANSACTION_READ_API_LEDGER_MERCHANT                  = 'x_transaction_read_api_ledger_merchant';
    const X_TRANSACTION_WRITE_API_LEDGER_MERCHANT                 = 'x_transaction_write_api_ledger_merchant';

    const TRANSACTION_CREATED_EVENT_DISPATCH_TIME                 = 'transaction_created_event_dispatch_time';

    // -----------------------------------------
    // Statement Read Cutoff Metrics
    // -----------------------------------------
    // Success metrics
    const READ_CUTOFF_XPERIENCE_REQUEST_TOTAL = 'read_cutoff_xperience_request_total';
    const READ_CUTOFF_XPERIENCE_SUCCESS_TOTAL = 'read_cutoff_xperience_success_total';
    const READ_CUTOFF_XPERIENCE_REQUEST_LATENCY_MILLISECONDS = 'read_cutoff_xperience_request_latency_milliseconds';

    const READ_CUTOFF_XAS_REQUEST_TOTAL = 'read_cutoff_xas_request_total';

    const READ_CUTOFF_XAS_REQUEST_LATENCY_MILLISECONDS = 'read_cutoff_xas_request_latency_milliseconds';

    const READ_CUTOFF_XAS_SUCCESS_TOTAL = 'read_cutoff_xas_success_total';

    // Fallback metrics
    const READ_CUTOFF_XPERIENCE_FALLBACK_TOTAL = 'read_cutoff_xperience_fallback_total';

    const READ_CUTOFF_XAS_FALLBACK_TOTAL = 'read_cutoff_xas_fallback_total';

}
