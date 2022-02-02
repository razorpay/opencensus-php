<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\SendNotificationAction;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;
use RZP\Models\Merchant\Cron\Collectors\L1NotSubmittedDataCollector;

class L1NotSubmittedIn1DayCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "merchant_notification_data" => L1NotSubmittedDataCollector::class
    ];

    protected $actions = [SendNotificationAction::class];

    protected $lastCronTimestampCacheKey = "l1_not_submitted_in_1_day_cron_timestamp";

    protected $defaultArgs = [
        'event_name' => OnboardingEvents::L1_NOT_SUBMITTED_IN_1_DAY
    ];

    protected function getStartInterval():int
    {
        return $this->lastCronTime - (24 * 60 * 60);
    }

    protected function getEndInterval():int
    {
        return $this->cronStartTime - (24 * 60 * 60);
    }
}
