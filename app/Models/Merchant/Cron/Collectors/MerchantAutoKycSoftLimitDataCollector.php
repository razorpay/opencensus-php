<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants;
use RZP\Models\Merchant\Constants as MConstants;
use RZP\Models\Merchant\Cron\Collectors\Core\DbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Trace\TraceCode;

class MerchantAutoKycSoftLimitDataCollector extends DbDataCollector
{
    protected function collectDataFromSource(): CollectorDto
    {
        $this->app['trace']->info(TraceCode::SELF_SERVE_CRON, [
            'type' => Constants::SOFT_LIMIT
        ]);

        // fetch all the merchants who are in activated_mcc_pending state
        $merchantIdList = $this->repo->merchant_detail->fetchMerchantIdsByActivationStatus(
            [DetailStatus::ACTIVATED_MCC_PENDING], OrgEntity::ORG_ID_LIST
        );

        $this->app['trace']->info(TraceCode::SELF_SERVE_CRON, [
            'type' => Constants::SOFT_LIMIT,
            'allmidscount' => count($merchantIdList)
        ]);



        $merchantIdChunks = array_chunk($merchantIdList, 20);
        $finalMerchantIdList = [];
        $finalMerchantsGmvList = [];

        foreach ($merchantIdChunks as $merchantIdList) {
            // filter merchants who have not escalated to soft limit already
            $merchantIdList = $this->filterMerchantIdsNotEscalatedToType($merchantIdList, Constants::SOFT_LIMIT);

            // filter merchants who have crossed settlements above threshold
            $merchantsGmvList = $this->repo->transaction->fetchTotalAmountByTransactionTypeAboveThreshold(
                $merchantIdList, MConstants::PAYMENT, env(Constants::SOFT_LIMIT_MCC_PENDING_THRESHOLD));

            $merchantIdList = array_map(function ($element) {
                return $element[Entity::MERCHANT_ID];
            }, $merchantsGmvList);

            $finalMerchantIdList = array_merge($finalMerchantIdList, $merchantIdList);
            $finalMerchantsGmvList = array_merge($finalMerchantsGmvList, $merchantsGmvList);

        }

        if (empty($merchantIdList) === true) {
            $this->app['trace']->info(TraceCode::SELF_SERVE_CRON_FAILURE, [
                'type' => Constants::SOFT_LIMIT,
                'reason' => 'no merchants to run the cron'
            ]);
            return CollectorDto::create([]);
        }

        $data["merchantIds"] = $finalMerchantIdList;
        $data["merchantsGmvList"] = $finalMerchantsGmvList;
        $data["limitType"] = Constants::SOFT_LIMIT;
        $data["level"] = 1;

        return CollectorDto::create($data);
    }

    private function filterMerchantIdsNotEscalatedToType($merchantIdList, string $type)
    {
        $escalations = $this->repo->merchant_auto_kyc_escalations->fetchEscalationsForMerchants($merchantIdList, $type);
        $excludeIds  = [];

        foreach ($escalations as $escalation)
        {
            $excludeIds[] = $escalation->getMerchantId();
        }

        return array_diff($merchantIdList, $excludeIds);
    }
}
