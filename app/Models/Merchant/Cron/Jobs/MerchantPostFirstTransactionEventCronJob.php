<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\MerchantPostFirstTransactionEventAction;
use RZP\Models\Merchant\Cron\Traits\RetryMechanismTrait;
use RZP\Models\Merchant\Cron\Collectors\MerchantPostFirstTransactionEventDataLakeCollector;

class MerchantPostFirstTransactionEventCronJob extends BaseCronJob
{
    use RetryMechanismTrait;

    protected $dataCollectors = [
        "post_first_transacted_mids"  => MerchantPostFirstTransactionEventDataLakeCollector::class
    ];

    protected $actions = [MerchantPostFirstTransactionEventAction::class];

    protected $lastCronTimestampCacheKey = "post-first-transacted-mids";

    function getRetryLimit(): int
    {
        return 2;
    }
}
