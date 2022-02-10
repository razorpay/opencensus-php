<?php


namespace RZP\Models\Merchant\Cron\Jobs;


use RZP\Models\Merchant\Cron\Actions\SignupAppAttributionAction;
use RZP\Models\Merchant\Cron\Actions\SignupWebAttributionAction;
use RZP\Models\Merchant\Cron\Collectors\SignupAppAttributionDataCollector;
use RZP\Models\Merchant\Cron\Collectors\SignupWebAttributionDataCollector;

class SignupAttributedCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "signup_web_attribution"   => SignupWebAttributionDataCollector::class,
        "signup_app_attribution"   => SignupAppAttributionDataCollector::class
    ];

    protected $actions = [SignupWebAttributionAction::class, SignupAppAttributionAction::class];

    protected $lastCronTimestampCacheKey = "signup_attribution_cron_timestamp";
}
