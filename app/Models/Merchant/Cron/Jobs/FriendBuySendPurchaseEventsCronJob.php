<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\FriendBuySendPurchaseEventsAction;
use RZP\Models\Merchant\Cron\Collectors\FriendBuySendPurchaseEventsDataCollector;

class FriendBuySendPurchaseEventsCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "friend_buy_send_purchase_events" => FriendBuySendPurchaseEventsDataCollector::class
    ];

    protected $actions = [FriendBuySendPurchaseEventsAction::class];

    protected $lastCronTimestampCacheKey = "friend_buy_send_purchase_events_cron_cache_key";
}
