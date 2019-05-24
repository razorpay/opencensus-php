<?php

namespace RZP\Models\Payout;

final class Metric
{
    // =========== COUNTERS ===========

    const PAYOUT_CREATED_TOTAL = 'payout_created_total';

    // =========== END COUNTERS ===========

    // =========== HISTOGRAMS ===========

    const PAYOUT_CREATED_TO_INITIATED_DURATION_MILLISECONDS   = 'payout_created_to_initiated_duration_millseconds.histogram';
    const PAYOUT_CREATED_TO_PROCESSED_DURATION_MILLISECONDS   = 'payout_created_to_processed_duration_millseconds.histogram';
    const PAYOUT_INITIATED_TO_PROCESSED_DURATION_MILLISECONDS = 'payout_initiated_to_processed_duration_millseconds.histogram';
    const PAYOUT_CREATED_TO_REVERSED_DURATION_MILLISECONDS    = 'payout_created_to_reversed_duration_millseconds.histogram';
    const PAYOUT_PROCESSED_TO_REVERSED_DURATION_MILLISECONDS  = 'payout_processed_to_reversed_duration_millseconds.histogram';

    // =========== END HISTOGRAMS ===========

    // ======================= DIMENSIONS =======================

    const CHANNEL   = 'channel';
    const MODE      = 'mode';

    // ======================= END DIMENSIONS =======================
}
