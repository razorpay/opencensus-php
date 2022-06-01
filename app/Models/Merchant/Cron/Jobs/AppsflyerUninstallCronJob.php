<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\AppsflyerUninstallAction;
use RZP\Models\Merchant\Cron\Collectors\AppsflyerUninstallCollector;
use RZP\Models\Merchant\Cron\Traits\RetryMechanismTrait;

class AppsflyerUninstallCronJob extends BaseCronJob
{
    use RetryMechanismTrait;

    protected $dataCollectors = [
        'appsflyer_uninstall_events'  => AppsflyerUninstallCollector::class
    ];

    protected $actions = [AppsflyerUninstallAction::class];

    protected $lastCronTimestampCacheKey = 'appsflyer_uninstall_event_timestamp';

    function getRetryLimit(): int
    {
        return 2;
    }
}
