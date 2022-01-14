<?php


namespace RZP\Models\Merchant\Cron\Jobs;


use RZP\Models\Merchant\Cron\Actions\FirstTouchProductAction;
use RZP\Models\Merchant\Cron\Collectors\FirstTouchProductDataLakeCollector;

class FirstTouchProductCronJob extends BaseCronJob
{
    protected $dataCollectors = [FirstTouchProductDataLakeCollector::class];

    protected $actions = [FirstTouchProductAction::class];

    protected $lastCronTimestampCacheKey = "first_touch_product_cron_timestamp";
}
