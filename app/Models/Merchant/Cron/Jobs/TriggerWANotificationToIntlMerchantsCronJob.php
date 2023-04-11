<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Actions\TriggerWANotificationToIntlMerchantsAction;
use RZP\Models\Merchant\Cron\Collectors\TriggerWANotificationToIntlMerchantsDataCollector;

class TriggerWANotificationToIntlMerchantsCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "merchant_data" => TriggerWANotificationToIntlMerchantsDataCollector::class
    ];

    protected $actions = [TriggerWANotificationToIntlMerchantsAction::class];

    protected $lastCronTimestampCacheKey = "intl_merchants_wa_notification_timestamp";

    protected $defaultArgs = [
        'event_name' => Constants::CB_SIGNUP_JOURNEY
    ];
}
