<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\SendNotificationAction;
use RZP\Models\Merchant\Cron\Collectors\InstantlyActivatedButNotTransactedDataCollector;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;

class InstantlyActivatedButNotTransactedCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "merchant_notification_data" => InstantlyActivatedButNotTransactedDataCollector::class
    ];

    protected $actions = [SendNotificationAction::class];

    protected $lastCronTimestampCacheKey = "instantly_activated_but_not_transacted_cron_timestamp";

    protected $defaultArgs = [
        'event_name' => OnboardingEvents::INSTANTLY_ACTIVATED_BUT_NOT_TRANSACTED
    ];
}
