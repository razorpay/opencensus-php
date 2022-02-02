<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\SendNotificationAction;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;
use RZP\Models\Merchant\Cron\Collectors\SignupStartedDataCollector;

class SignupStartedCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "merchant_notification_data" => SignupStartedDataCollector::class
    ];

    protected $actions = [SendNotificationAction::class];

    protected $lastCronTimestampCacheKey = "signup_started_cron_timestamp";

    protected $defaultArgs = [
        'event_name' => OnboardingEvents::SIGNUP_STARTED_NOTIFY
    ];
}
