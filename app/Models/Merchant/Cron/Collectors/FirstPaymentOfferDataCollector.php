<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Services\ApachePinotClient;
use RZP\Models\DeviceDetail\Constants;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Escalations\Entity;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;

class FirstPaymentOfferDataCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $this->app['rzp.mode'] = Mode::LIVE;

        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'       => $this->args,
            'start_time' => $startTime,
            'end_time'   => $endTime
        ]);

        $merchantIdList = $this->repo->merchant->fetchAllLiveActivatedRegularMerchantsOfOrg($startTime, $endTime);

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'args'         => $this->args,
            'type'         => 'offermtu_communication',
            "step"         => 'fetchAllLiveActivatedRegularMerchantsOfOrg',
            'allmidscount' => count($merchantIdList)
        ]);

        $merchantIdList = $this->repo->user_device_detail->filterSignupCampaignAndSourceFromMerchantIdList($merchantIdList, Constants::EASY_ONBOARDING, Constants::MOBILE_APP_SOURCES);

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'args'         => $this->args,
            'type'         => 'offermtu_communication',
            "step"         => 'filterSignupCampaignAndSourceFromMerchantIdList',
            'allmidscount' => count($merchantIdList)
        ]);

        $subMerchants = $this->repo->merchant_access_map->fetchSubMerchants($merchantIdList);

        $merchantIdList = array_diff($merchantIdList, $subMerchants);

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'args'         => $this->args,
            'type'         => 'offermtu_communication',
            "step"         => 'fetchSubMerchants',
            'allmidscount' => count($merchantIdList)
        ]);

        $merchantList = $this->repo
            ->merchant_promotion
            ->fetchMerchantIdsWithAnyPromotion($merchantIdList);

        $merchantList = array_diff($merchantIdList, $merchantList);

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'args'         => $this->args,
            'type'         => 'offermtu_communication',
            "step"         => 'fetchSubMerchants',
            'allmidscount' => count($merchantList)
        ]);

        $merchantIdChunks = array_chunk($merchantList, 100);

        $transactedMerchants = [];

        foreach ($merchantIdChunks as $merchantIdChunk)
        {
            $query = 'select min(created_at) as first_transaction_timestamp, merchant_id from payments_v1 where merchant_id in (%s) and base_amount > 0 group by merchant_id limit %s';

            $query = sprintf($query, "'" . implode("','", $merchantIdChunk) . "'", count($merchantList));

            // fetch all merchants first transaction timestamp
            $queryResponse = (new ApachePinotClient())->getDataFromPinot($query);

            if (empty($queryResponse) === true)
            {
                $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                    'type'       => 'first_payment_offer',
                    'reason'     => 'no merchants found',
                    'step'       => 'first_transaction_timestamp',
                    'args'       => $this->args,
                    'start_time' => $startTime,
                    'end_time'   => $endTime
                ]);

            }
            else
            {


                $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
                    'merchants_count' => count($queryResponse),
                    'type'            => 'first_payment_offer',
                    'step'            => 'first_transaction_timestamp',
                    'args'            => $this->args,
                    'start_time'      => $startTime,
                    'end_time'        => $endTime,
                ]);

                $transactedMerchants = array_merge($transactedMerchants, array_keys(
                    array_filter(
                        array_column($queryResponse, 'first_transaction_timestamp', Entity::MERCHANT_ID),
                        function($firstTxnTimestamp) use ($startTime) {
                            return $firstTxnTimestamp >= $startTime;
                        }
                    )
                ));
            }
        }

        $merchantIdList = array_diff($merchantList, $transactedMerchants);

        $m2mMerchants = $this->repo->m2m_referral->filterMerchants($merchantIdList);

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'args'         => $this->args,
            'start_time'   => $startTime,
            'end_time'     => $endTime,
            'm2mMerchants' => count($m2mMerchants)
        ]);

        $merchantIdList = array_diff($merchantIdList, $m2mMerchants);

        $finalMidList = [];

        foreach ($merchantIdList as $merchantId)
        {
            $isMtuCouponExperimentEnabled = (new MerchantCore())->isRazorxExperimentEnable($merchantId,
                                                                                           RazorxTreatment::MTU_COUPON_CODE);

            if ($isMtuCouponExperimentEnabled === true)
            {
                array_push($finalMidList, $merchantId);
            }
        }

        $data['merchantIds'] = $finalMidList;

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
