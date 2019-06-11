<?php

namespace RZP\Models\Payout;

final class Metric
{
    // Counters
    const PAYOUT_CREATED_TOTAL = 'payout_created_total';

    // Histograms
    const PAYOUT_CREATED_TO_INITIATED_DURATION_SECONDS   = 'payout_created_to_initiated_duration_seconds.histogram';
    const PAYOUT_CREATED_TO_PROCESSED_DURATION_SECONDS   = 'payout_created_to_processed_duration_seconds.histogram';
    const PAYOUT_INITIATED_TO_PROCESSED_DURATION_SECONDS = 'payout_initiated_to_processed_duration_seconds.histogram';
    const PAYOUT_CREATED_TO_REVERSED_DURATION_SECONDS    = 'payout_created_to_reversed_duration_seconds.histogram';
    const PAYOUT_INITIATED_TO_REVERSED_DURATION_SECONDS  = 'payout_initiated_to_reversed_duration_seconds.histogram';

    // Dimension constants
    const CHANNEL = 'channel';
    const MODE    = 'mode';
}
