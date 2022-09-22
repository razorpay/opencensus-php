<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Traits\RetryMechanismTrait;
use RZP\Models\Merchant\Cron\Actions\SubmerchantFirstTransactionAction;
use RZP\Models\Merchant\Cron\Collectors\SubmerchantFirstTransactionDatalakeCollector;


class SubmerchantFirstTransactionCronJob extends BaseCronJob
{
    use RetryMechanismTrait;

    protected $dataCollectors = [
        Constants::SUBMERCHANT_FIRST_TRANSACTION  => SubmerchantFirstTransactionDatalakeCollector::class
    ];

    protected $actions = [SubmerchantFirstTransactionAction::class];

    protected $lastCronTimestampCacheKey = Constants::SUBMERCHANT_FIRST_TRANSACTION;

    function getRetryLimit(): int
    {
        return 2;
    }
}
