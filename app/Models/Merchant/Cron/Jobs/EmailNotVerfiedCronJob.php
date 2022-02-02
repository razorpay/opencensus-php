<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\SendNotificationAction;
use RZP\Models\Merchant\Cron\Collectors\EmailNotVerifiedDataCollector;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;

class EmailNotVerfiedCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "merchant_notification_data" => EmailNotVerifiedDataCollector::class
    ];

    protected $actions = [SendNotificationAction::class];

    protected $lastCronTimestampCacheKey = "email_not_verified_cron_timestamp";

    protected $defaultArgs = [
        'event_name' => OnboardingEvents::ONBOARDING_VERIFY_EMAIL
    ];
}
