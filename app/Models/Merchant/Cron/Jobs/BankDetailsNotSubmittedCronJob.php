<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\SendNotificationAction;
use RZP\Models\Merchant\Cron\Collectors\BankDetailsNotSubmittedDataCollector;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;

class BankDetailsNotSubmittedCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "merchant_notification_data" => BankDetailsNotSubmittedDataCollector::class
    ];

    protected $actions = [SendNotificationAction::class];

    protected $lastCronTimestampCacheKey = "bank_details_cron_timestamp";

    protected $defaultArgs = [
        'event_name' => OnboardingEvents::L2_BANK_DETAILS_NOT_SUBMITTED_IN_1_HOUR
    ];
}
