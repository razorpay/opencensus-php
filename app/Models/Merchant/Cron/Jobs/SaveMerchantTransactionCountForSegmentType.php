<?php


namespace RZP\Models\Merchant\Cron\Jobs;


use Carbon\Carbon;
use RZP\Models\Merchant\Cron\Traits\RetryMechanismTrait;
use RZP\Models\Merchant\Cron\Actions\SaveMerchantAuthorizedTransactionCount;
use RZP\Models\Merchant\Cron\Collectors\AuthorizedPaymentsMerchantDataCollector;

class SaveMerchantTransactionCountForSegmentType extends BaseCronJob
{
    use RetryMechanismTrait;

    protected $dataCollectors = [
        "authorized_payments_merchants"   => AuthorizedPaymentsMerchantDataCollector::class
    ];

    protected $actions = [SaveMerchantAuthorizedTransactionCount::class];

    protected $lastCronTimestampCacheKey = "merchant_segment_cron_timestamp";

    protected function getStartInterval():int
    {
        return Carbon::now()->subDay(30)->getTimestamp();
    }

    protected function getEndInterval():int
    {
        return Carbon::now()->getTimestamp();
    }

    function getRetryLimit(): int
    {
        return 2;
    }

}
