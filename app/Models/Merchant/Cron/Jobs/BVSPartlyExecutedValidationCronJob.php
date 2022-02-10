<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\BVSPartlyExecutedValidationActions;
use RZP\Models\Merchant\Cron\Collectors\BVSPartlyExecutedValidationsDataCollector;

class BVSPartlyExecutedValidationCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "bvs_partly_executed_validations" => BVSPartlyExecutedValidationsDataCollector::class
    ];

    protected $actions = [BVSPartlyExecutedValidationActions::class];

    protected $lastCronTimestampCacheKey = "bvs_partly_executed_validation_cron_cache_key";
}
