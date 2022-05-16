<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\MerchantAutoKycLevel1EscalationsAction;
use RZP\Models\Merchant\Cron\Collectors\MerchantAutoKycSoftLimitDataCollector;

class MerchantAutoKycSoftLimitCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "merchant_escalation_data" => MerchantAutoKycSoftLimitDataCollector::class
    ];

    protected $actions = [MerchantAutoKycLevel1EscalationsAction::class];

    protected $lastCronTimestampCacheKey = "autokyc_soft_limit_escalation";

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
