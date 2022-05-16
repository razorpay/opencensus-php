<?php

namespace RZP\Models\Merchant\Cron\Jobs;

use RZP\Models\Merchant\Cron\Actions\MerchantAutoKycEscalationsAction;
use RZP\Models\Merchant\Cron\Collectors\MerchantAutoKycEscalationsDataCollector;

class MerchantAutoKycEscalationsCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "merchant_escalation_data" => MerchantAutoKycEscalationsDataCollector::class
    ];

    protected $actions = [MerchantAutoKycEscalationsAction::class];

    protected $lastCronTimestampCacheKey = "autokyc_escalations";

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
