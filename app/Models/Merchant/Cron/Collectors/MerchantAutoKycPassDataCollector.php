<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants;
use RZP\Models\Merchant\Cron\Constants as CronConstants;
use RZP\Models\Merchant\Cron\Collectors\Core\DbDataCollector;

class MerchantAutoKycPassDataCollector extends DbDataCollector
{
    public function collectDataFromSource(): CollectorDto
    {
        $this->app['trace']->info(TraceCode::SELF_SERVE_CRON, [
            'type' => Constants::AMP
        ]);

        // fetch all merchants who are in ACTIVATED_MCC_PENDING.
        $merchantIdList = $this->repo->state->fetchAutoKycPassMerchants($this->lastCronTime);

        $this->app['trace']->info(TraceCode::SELF_SERVE_CRON, [
            'type' => Constants::AMP,
            'merchant_ids' => $merchantIdList
        ]);

        if (empty($merchantIdList) === true)
        {
            $this->app['trace']->info(TraceCode::NO_MERCHANT_FOUND_FOR_AUTO_KYC_PASS_ACTION, [
                'type' => Constants::AMP,
                'reason' => 'no merchants to run the cron'
            ]);
            return CollectorDto::create([]);
        }

        $data[CronConstants::MERCHANT_IDS] = $merchantIdList;

        $data[CronConstants::CASE_TYPE] = Constants::AMP;

        $data[CronConstants::LEVEL] = 1;

        return CollectorDto::create($data);
    }

}
