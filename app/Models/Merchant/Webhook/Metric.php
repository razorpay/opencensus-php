<?php

namespace RZP\Models\Merchant\Webhook;

final class Metric
{
    // Counters
    const WEBHOOK_EVENTS_TRIGGERED_TOTAL        = 'webhook_events_triggered_total';
    const WEBHOOK_EVENTS_CONSUMED_TOTAL         = 'webhook_events_consumed_total';
    const WEBHOOK_DEACTIVATED_TOTAL             = 'webhook_deactivated_total';
    const WEBHOOK_VALIDATION_FAILURES_TOTAL     = 'webhook_validation_failures_total';
    const WEBHOOK_REQUEST_COMPLETED_TOTAL       = 'webhook_request_completed_total';
    const WEBHOOK_REQUEST_FAILURES_TOTAL        = 'webhook_request_failures_total';

    // Histograms
    const WEBHOOK_REQUEST_DURATION_MILLISECONDS = 'webhook_request_duration_milliseconds.histogram';
    // Captures time taken from queuing a webhook event to having it fired with attempts range.
    const WEBHOOK_QUEUED_TO_FIRED_MILLISECONDS  = 'webhook_queued_to_fired_milliseconds.histogram';
}
