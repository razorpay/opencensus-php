<?php


namespace RZP\Models\Merchant\Cron;


class Metrics
{
    // Counter Metrics
    const CRON_STARTED_TOTAL    = 'cron_job_started_total';
    const CRON_FAILED_TOTAL     = 'cron_job_failed_total';
    const CRON_COMPLETE_TOTAL   = 'cron_job_complete_total';

    const MTU_TRANSACTED_EVENT_RECON_TOTAL = 'mtu_transacted_event_recon_total';

    // Histograms
    const CRON_DURATION_MILLISECONDS = 'cron_job_duration_milliseconds.histogram';

    // Labels
    const LABEL_CRON_NAME       = 'cron_name';
    const LABEL_CRON_ATTEMPTS   = 'cron_attempts';
    const LABEL_CRON_STATUS     = 'cron_status';
}
