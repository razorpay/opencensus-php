<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\MtuTransactedAction;
use RZP\Models\Merchant\Cron\Collectors\MtuDatalakeCollector;
use RZP\Models\Merchant\Cron\Collectors\MtuTransactedDataCollector;
use RZP\Models\Merchant\Cron\Traits\RetryMechanismTrait;

class MtuTransactedCronJob extends BaseCronJob
{
    use RetryMechanismTrait;

    protected $dataCollectors = [
        "mtu_transacted_merchants"  => MtuDatalakeCollector::class
    ];

    protected $actions = [MtuTransactedAction::class];

    protected $lastCronTimestampCacheKey = "onboarding_segment_mtu_timestamp";

    function getRetryLimit(): int
    {
        return 2;
    }
}
