<?php


namespace RZP\Models\Merchant\Cron\Jobs;


use RZP\Models\Merchant\Cron\Actions\WebAttributionAction;
use RZP\Models\Merchant\Cron\Collectors\WebAttributionDataLakeCollector;

class WebAttributionCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "web_attribution"   => WebAttributionDataLakeCollector::class
    ];

    protected $actions = [WebAttributionAction::class];

    protected $lastCronTimestampCacheKey = "web_attribution_cron_timestamp";
}
