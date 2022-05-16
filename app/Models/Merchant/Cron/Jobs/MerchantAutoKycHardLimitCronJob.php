<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\MerchantAutoKycLevel1EscalationsAction;
use RZP\Models\Merchant\Cron\Collectors\MerchantAutoKycHardLimitDataCollector;

class MerchantAutoKycHardLimitCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "merchant_escalation_data" => MerchantAutoKycHardLimitDataCollector::class
    ];

    protected $actions = [MerchantAutoKycLevel1EscalationsAction::class];

    protected $lastCronTimestampCacheKey = "autokyc_hard_limit_escalation";

//    protected function getStartInterval():int
//    {
//        return $this->lastCronTime - (24 * 60 * 60);
//    }
//
//    protected function getEndInterval():int
//    {
//        return $this->cronStartTime - (24 * 60 * 60);
//    }
}
