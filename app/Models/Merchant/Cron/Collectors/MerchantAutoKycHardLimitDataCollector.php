<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Models\Merchant\AutoKyc\Escalations\Constants;
use RZP\Models\Merchant\Constants as MConstants;
use RZP\Models\Merchant\Cron\Collectors\Core\DbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Trace\TraceCode;

class MerchantAutoKycHardLimitDataCollector extends DbDataCollector
{
    protected function collectDataFromSource(): CollectorDto
    {
        $this->app['trace']->info(TraceCode::SELF_SERVE_CRON, [
            'type' => Constants::HARD_LIMIT
        ]);

        // fetch all merchants who have not escalated to hard limit
        // this will fetch merchants who have crossed soft limit but not hard limit
        $merchantIdList = $this->repo->merchant_auto_kyc_escalations->fetchMerchantIdsNotEscalatedToType(
            Constants::HARD_LIMIT);

        // merchants who crossed soft limit, may get rejected, activated upon manual review
        // so filter only those who are in NC, under review, mcc pending
        $merchantIdList = $this->repo->merchant_detail->filterMerchantIdsByActivationStatus(
            $merchantIdList, [
            DetailStatus::NEEDS_CLARIFICATION,
            DetailStatus::UNDER_REVIEW,
            DetailStatus::ACTIVATED_MCC_PENDING
        ]);

        // filter merchants who have crossed payments above threshold
        $merchantsGmvList = $this->repo->transaction->fetchTotalAmountByTransactionTypeAboveThreshold(
            $merchantIdList, MConstants::PAYMENT, env(Constants::HARD_LIMIT_MCC_PENDING_THRESHOLD));

        $merchantIdList = array_map(function($element) {
            return $element[Entity::MERCHANT_ID];
        }, $merchantsGmvList);

        if (empty($merchantIdList) === true)
        {
            $this->app['trace']->info(TraceCode::SELF_SERVE_CRON_FAILURE, [
                'type'   => Constants::HARD_LIMIT,
                'reason' => 'no merchants to run the cron',
                'count'  => count($merchantIdList),
            ]);
            return CollectorDto::create([]);
        }

        $data["merchantIds"] = $merchantIdList;
        $data["merchantsGmvList"] = $merchantsGmvList;
        $data["limitType"] = Constants::HARD_LIMIT;
        $data["level"] = 1;

        return CollectorDto::create($data);
    }
}
