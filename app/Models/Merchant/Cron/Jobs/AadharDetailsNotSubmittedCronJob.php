<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\SendNotificationAction;
use RZP\Models\Merchant\Cron\Collectors\AadhardetailsNotSubmittedDataCollector;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;

class AadharDetailsNotSubmittedCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "merchant_notification_data" => AadhardetailsNotSubmittedDataCollector::class
    ];

    protected $actions = [SendNotificationAction::class];

    protected $lastCronTimestampCacheKey = "aadhaar_details_cron_timestamp";

    protected $defaultArgs = [
        'event_name' => OnboardingEvents::L2_AADHAR_DETAILS_NOT_SUBMITTED_IN_1_HOUR
    ];
}
