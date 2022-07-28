<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\AppsflyerInstallAction;
use RZP\Models\Merchant\Cron\Actions\AppsflyerUninstallAction;
use RZP\Models\Merchant\Cron\Collectors\AppsflyerInstallCollector;
use RZP\Models\Merchant\Cron\Collectors\AppsflyerUninstallCollector;
use RZP\Models\Merchant\Cron\Traits\RetryMechanismTrait;

class AppsflyerUninstallCronJob extends BaseCronJob
{
    use RetryMechanismTrait;

    protected $dataCollectors = [
        'appsflyer_uninstall_events'  => AppsflyerUninstallCollector::class,
        'appsflyer_install_events'    => AppsflyerInstallCollector::class
    ];

    protected $actions = [ AppsflyerUninstallAction::class, AppsflyerInstallAction::class ];

    protected $lastCronTimestampCacheKey = 'appsflyer_uninstall_event_timestamp';

    function getRetryLimit(): int
    {
        return 2;
    }
}
