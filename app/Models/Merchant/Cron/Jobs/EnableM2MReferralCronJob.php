<?php

namespace RZP\Models\Merchant\Cron\Jobs;


use Carbon\Carbon;
use RZP\Models\Merchant\Cron\Actions\EnableM2MReferralAction;
use RZP\Models\Merchant\Cron\Collectors\EnableM2MReferralDataCollector;
use RZP\Models\Merchant\Constants as MerchantConstants;
class EnableM2MReferralCronJob extends BaseCronJob
{
    protected $dataCollectors = [
        "enable_m2m_referral" => EnableM2MReferralDataCollector::class
    ];

    protected $actions = [EnableM2MReferralAction::class];

    protected $lastCronTimestampCacheKey = MerchantConstants::M2M_REFERRALS_ENABLE_CRON;

    protected function getDefaultLastCronValue(): ?int
    {
        return Carbon::now()->subDays(MerchantConstants::M2M_REFERRAL_TIME_BOUND_THRESHOLD)->getTimestamp();
    }
}
