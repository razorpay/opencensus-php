<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use Carbon\Carbon;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Actions\MerchantAutoKycFailureAction;
use RZP\Models\Merchant\Cron\Collectors\MerchantAutoKycFailureDataCollector;

class MerchantAutoKycFailureCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        Constants::AUTO_KYC_FAILURE_DATA => MerchantAutoKycFailureDataCollector::class
    ];

    protected $actions = [MerchantAutoKycFailureAction::class];

    protected $lastCronTimestampCacheKey = Constants::MERCHANT_AUTO_KYC_FAILURE_TIMESTAMP;

    protected function getDefaultLastCronValue(): ?int
    {
        return Carbon::now()->subHours(Constants::AUTO_KYC_LAST_CRON_DEFAULT_VALUE)->getTimestamp();
    }

}
