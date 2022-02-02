<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Constants\Mode;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Core as MerchantCore;

class FirstPaymentOfferDataCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $this->app['rzp.mode'] = Mode::LIVE;

        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime
        ]);

        $merchantIdList = $this->repo->merchant->fetchAllLiveActivatedRegularMerchantsOfOrg($startTime, $endTime);

        $subMerchants = $this->repo->merchant_access_map->fetchSubMerchants($merchantIdList);

        $merchantIdList = array_diff($merchantIdList, $subMerchants);

        $merchantList = $this->repo
            ->merchant_promotion
            ->fetchMerchantIdsWithAnyPromotion(
                $merchantIdList
            );

        $merchantList =  array_diff($merchantIdList, $merchantList);

        $transactedMerchants = $this->repo->transaction->filterMerchantsWithFirstTransactionAboveTimestamp(
            $merchantList, $startTime);

        $merchantIdList =  array_diff($merchantList, $transactedMerchants);

        $finalMidList = [];

        foreach($merchantIdList as $merchantId)
        {
            $isMtuCouponExperimentEnabled = (new MerchantCore())->isRazorxExperimentEnable($merchantId,
                RazorxTreatment::MTU_COUPON_CODE);

            if($isMtuCouponExperimentEnabled === true)
            {
                array_push($finalMidList, $merchantId);
            }
        }

        $data["merchantIds"] = $finalMidList;

        return CollectorDto::create($data);
    }

    protected function getStartInterval(): int
    {
        return $this->lastCronTime - (2 * 24 * 60 * 60);
    }

    protected function getEndInterval(): int
    {
        return $this->cronStartTime - (2 * 24 * 60 * 60);
    }
}
