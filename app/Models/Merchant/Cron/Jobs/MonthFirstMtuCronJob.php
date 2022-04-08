<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\MonthFirstMtuAction;
use RZP\Models\Merchant\Cron\Traits\RetryMechanismTrait;
use RZP\Models\Merchant\Cron\Collectors\MonthFirstMtuDatalakeCollector;

class MonthFirstMtuCronJob extends BaseCronJob
{
    use RetryMechanismTrait;

    protected $dataCollectors = [
        "mtu_transacted_merchants"  => MonthFirstMtuDatalakeCollector::class
    ];

    protected $actions = [MonthFirstMtuAction::class];

    protected $lastCronTimestampCacheKey = "month-first-mtu-transacted";

    function getRetryLimit(): int
    {
        return 2;
    }
}
