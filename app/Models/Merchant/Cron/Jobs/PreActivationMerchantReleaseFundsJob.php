<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use Carbon\Carbon;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Actions\PreActivationMerchantReleaseFundsDataAction;
use RZP\Models\Merchant\Cron\Collectors\PreActivationMerchantReleaseFundsDataCollector;

class PreActivationMerchantReleaseFundsJob extends BaseCronJob
{
    protected $dataCollectors = [
        "pre_activation_merchant_release_funds_data" => PreActivationMerchantReleaseFundsDataCollector::class
    ];

    protected $actions = [PreActivationMerchantReleaseFundsDataAction::class];

    protected $lastCronTimestampCacheKey = "pre_activation_merchant_release_funds_data_timestamp";

    protected function getDefaultLastCronValue(): ?int
    {
        return Carbon::now()->subHours(Constants::PRE_ACTIVATION_MERCHANT_RELEASE_FUNDS_LAST_CRON_DEFAULT_VALUE)->getTimestamp();
    }
}
