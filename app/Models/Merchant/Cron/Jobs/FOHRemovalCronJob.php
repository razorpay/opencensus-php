<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use Carbon\Carbon;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Actions\FOHRemovalAction;
use RZP\Models\Merchant\Cron\Collectors\FOHRemovalDataCollector;

class FOHRemovalCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "FOH_removal_data" => FOHRemovalDataCollector::class
    ];

    protected $actions = [FOHRemovalAction::class];

    protected $lastCronTimestampCacheKey = "foh_removal_timestamp";

    protected function getDefaultLastCronValue(): ?int
    {
        return Carbon::now()->subHours(Constants::FOH_REMOVAL_LAST_CRON_DEFAULT_VLAUE)->getTimestamp();
    }
}
