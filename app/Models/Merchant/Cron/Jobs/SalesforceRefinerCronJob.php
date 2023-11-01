<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\SalesforceRefinerAction;
use RZP\Models\Merchant\Cron\Collectors\SalesforceRefinerDataLakeCollector;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Traits\RetryMechanismTrait;
use RZP\Models\Merchant\Cron\Actions\SubmerchantFirstTransactionAction;
use RZP\Models\Merchant\Cron\Collectors\SubmerchantFirstTransactionDatalakeCollector;


class SalesforceRefinerCronJob extends BaseCronJob
{
    use RetryMechanismTrait;

    protected $dataCollectors = [
        Constants::SALESFORCE_REFINER  => SalesforceRefinerDataLakeCollector::class
    ];

    protected $actions = [SalesforceRefinerAction::class];

    protected $lastCronTimestampCacheKey = Constants::SALESFORCE_REFINER;

    function getRetryLimit(): int
    {
        return 2;
    }
}
