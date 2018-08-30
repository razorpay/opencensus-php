<?php

namespace RZP\Models\Merchant\Webhook;

final class Metric
{
    // Counters
    const WEBHOOK_EVENTS_TRIGGERED_TOTAL        = 'webhook_events_triggered_total';
    const WEBHOOK_EVENTS_CONSUMED_TOTAL         = 'webhook_events_consumed_total';
    const WEBHOOK_DEACTIVATED_TOTAL             = 'webhook_deactivated_total';
    const WEBHOOK_VALIDATION_FAILURES_TOTAL     = 'webhook_validation_failures_total';
    const WEBHOOK_REQUEST_SUCCESSFUL_TOTAL      = 'webhook_request_successful_total';
    const WEBHOOK_REQUEST_FAILED_TOTAL          = 'webhook_request_failed_total';

    // Histograms
    const WEBHOOK_REQUEST_DURATION_MILLISECONDS = 'webhook_request_duration_milliseconds.histogram';

    public static function getMetricDimensions(string $eventName, string $mode = null): array
    {
        return [
            'event' => $eventName,
            'mode'  => $mode,
        ];
    }
}
