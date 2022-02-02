<?php


namespace RZP\Models\Merchant\Cron\Jobs;


use RZP\Models\Merchant\Cron\Actions\TransactionDetailsAction;
use RZP\Models\Merchant\Cron\Collectors\TransactionDetailsCollector;
use RZP\Models\Merchant\Cron\Traits\RetryMechanismTrait;

class TransactionDetailsCronJob extends BaseCronJob
{
    use RetryMechanismTrait;

    protected $dataCollectors = [
        "transaction_details"   => TransactionDetailsCollector::class
    ];

    protected $actions = [TransactionDetailsAction::class];

    protected $lastCronTimestampCacheKey = "onboarding_transaction_cron_timestamp";

    function getRetryLimit(): int
    {
        return 2;
    }
}
