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

    const TRANSACTION_CREATED_EVENT_DISPATCH_TIME                 = 'transaction_created_event_dispatch_time';

}
