<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\NcRevampSendReminder;
use RZP\Models\Merchant\Cron\Collectors\NcRevampReminderDataCollector;

class NcRevampReminderCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "merchant_notification_data" => NcRevampReminderDataCollector::class
    ];

    protected $actions = [NcRevampSendReminder::class];

    protected $lastCronTimestampCacheKey = "nc_reminder_cron_timestamp";
}
