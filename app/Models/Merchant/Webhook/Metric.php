<?php

namespace RZP\Models\Merchant\Webhook;

final class Metric
{
    // Counters
    const WEBHOOK_EVENTS_TRIGGERED_TOTAL        = 'webhook_events_triggered_total';

    // Histograms
    const EVENT_PROCESS_DURATION_MILLISECONDS   = 'event_process_duration_milliseconds.histogram';
}
