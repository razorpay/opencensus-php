<?php

namespace RZP\Models\Payment\Refund;

/**
 * List of metrics in Refund/ module
 */
final class Metric
{
    // Counters
    const REFUND_TOTAL_PROCESSED    = 'refund_total_processed';
    const REFUND_TOTAL_FAILED       = 'refund_total_failed';

    // Histograms
    const REFUND_TIME_MILLISECONDS  = 'refund_time_milliseconds';
}