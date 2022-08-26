<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\WebsiteComplianceGracePeriodReminderAction;
use RZP\Models\Merchant\Cron\Collectors\WebsiteComplianceGracePeriodReminderDataCollector;

class WebsiteComplianceGracePeriodReminderCronJob extends BaseCronJob
{
    protected $dataCollectors            = [
        "merchant_notification_data" => WebsiteComplianceGracePeriodReminderDataCollector::class
    ];

    protected $actions                   = [WebsiteComplianceGracePeriodReminderAction::class];

    protected $lastCronTimestampCacheKey = "website_compliance_grace_period_reminder_cron_timestamp";
}
