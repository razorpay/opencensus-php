<?php


namespace RZP\Models\Merchant\Cron\Jobs;


use RZP\Models\Merchant\Cron\Actions\BvsAction;
use RZP\Models\Merchant\Cron\Collectors\BvsDataCollector;

class BvsCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "bvs_validations" => BvsDataCollector::class
    ];

    protected $actions = [BvsAction::class];

    protected $lastCronTimestampCacheKey = "bvs_validation_cron_cache_key";
}
