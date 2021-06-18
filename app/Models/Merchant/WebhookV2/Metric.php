<?php

namespace RZP\Models\Merchant\WebhookV2;

final class Metric
{
    // Counters
    const WEBHOOK_EVENTS_TRIGGERED_TOTAL = 'webhook_events_triggered_total';
    // Histograms
    const EVENT_PROCESS_DURATION_MILLISECONDS = 'event_process_duration_milliseconds.histogram';

    const ACCOUNT_V2_WEBHOOK_CREATE_SUCCESS_TOTAL    = 'account_v2_webhook_create_success_total';
    const ACCOUNT_V2_WEBHOOK_FETCH_SUCCESS_TOTAL     = 'account_v2_webhook_fetch_success_total';
    const ACCOUNT_V2_WEBHOOK_UPDATE_SUCCESS_TOTAL    = 'account_v2_webhook_update_success_total';
    const ACCOUNT_V2_WEBHOOK_DELETE_SUCCESS_TOTAL    = 'account_v2_webhook_delete_success_total';
    const ACCOUNT_V2_WEBHOOK_FETCH_ALL_SUCCESS_TOTAL = 'account_v2_webhook_fetch_all_success_total';
    const ACCOUNT_V2_WEBHOOK_FETCH_TIME_MS              = 'account_v2_webhook_fetch_time_ms';
    const ACCOUNT_V2_WEBHOOK_FETCH_ALL_TIME_MS          = 'account_v2_webhook_fetch_all_time_ms';
}
