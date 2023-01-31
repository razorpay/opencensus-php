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

    // Errors Counters
    const TRANSACTION_VA_REQUEST_ERROR_COUNT                      = 'transaction_va_request_error_count';

    // Histogram
    const TRANSACTION_VA_REQUEST_LATENCY_MILLISECONDS             = 'transaction_va_request_latency_ms';
    const TRANSACTION_CA_REQUEST_LATENCY_MILLISECONDS             = 'transaction_ca_request_latency_ms';
    const TRANSACTION_LEGACY_REQUEST_LATENCY_MILLISECONDS         = 'transaction_legacy_request_latency_ms';
}
