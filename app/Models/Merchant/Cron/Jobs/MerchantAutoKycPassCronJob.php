<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use Carbon\Carbon;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Actions\MerchantAutoKycPassAction;
use RZP\Models\Merchant\Cron\Collectors\MerchantAutoKycPassDataCollector;

class MerchantAutoKycPassCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "merchant_auto_kyc_pass_data" => MerchantAutoKycPassDataCollector::class
    ];

    protected $actions = [MerchantAutoKycPassAction::class];

    protected $lastCronTimestampCacheKey = "merchant_auto_kyc_pass_timestamp";

    protected function getDefaultLastCronValue(): ?int
    {
        return Carbon::now()->subHours(Constants::AUTO_KYC_LAST_CRON_DEFAULT_VALUE)->getTimestamp();
    }
}
