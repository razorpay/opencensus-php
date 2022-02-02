<?php


namespace RZP\Models\Merchant\Cron\Jobs;


use RZP\Models\Merchant\Cron\Actions\MtuReconAction;
use RZP\Models\Merchant\Cron\Actions\PushMetricsAction;
use RZP\Models\Merchant\Cron\Collectors\MtuDatalakeCollector;
use RZP\Models\Merchant\Cron\Collectors\MtuTransactedSumoLogsCollector;
use RZP\Models\Merchant\Cron\Traits\RetryMechanismTrait;

class MtuTransactedEventReconJob extends BaseCronJob
{
    use RetryMechanismTrait;

    protected $dataCollectors = [
        "segment_mtu_transacted_sumo_count" => MtuTransactedSumoLogsCollector::class,
        "segment_mtu_transacted_datalake"   => MtuDatalakeCollector::class
    ];

    protected $actions = [
        PushMetricsAction::class
    ];

    protected $lastCronTimestampCacheKey = "mtu_transacted_recon_job_timestamp";

    function getRetryLimit(): int
    {
        return 2;
    }
}
