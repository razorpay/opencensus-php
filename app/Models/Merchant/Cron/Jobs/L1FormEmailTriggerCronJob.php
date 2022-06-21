<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\SendNotificationAction;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;
use RZP\Models\Merchant\Cron\Actions\L1FormEmailTriggerAction;
use RZP\Models\Merchant\Cron\Collectors\SignupStartedDataCollector;
use RZP\Models\Merchant\Cron\Collectors\L1FormEmailTriggerDataCollector;

class L1FormEmailTriggerCronJob extends BaseCronJob
{
    protected $dataCollectors            = [
        "merchant_notification_data" => L1FormEmailTriggerDataCollector::class
    ];

    protected $actions                   = [L1FormEmailTriggerAction::class];

    protected $lastCronTimestampCacheKey = "l1_form_email_trigger_cron_timestamp";

}
