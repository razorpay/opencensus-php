<?php

namespace RZP\Models\Merchant\Cron\Collectors;


use RZP\Models\Merchant\Constants as MConstants;
use RZP\Models\Merchant\Cron\Collectors\Core\DbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\M2MReferral\Constants as M2MConstants;
use RZP\Models\Merchant\M2MReferral\Status;
use RZP\Trace\TraceCode;

class FriendBuySendPurchaseEventsDataCollector extends DbDataCollector
{
    protected function collectDataFromSource(): CollectorDto
    {
        // fetch a list of merchants whose status are either in signup or signup_event_sent
        $merchantIdList = $this->repo->m2m_referral->fetchMerchantsInReferralState([Status::SIGN_UP, Status::SIGNUP_EVENT_SENT]);

        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'                  => $this->args,
            '$merchantIdList'       => $merchantIdList
        ]);

        // create chunks of 100 for batch queries to database
        $merchantIdChunks = array_chunk($merchantIdList, 100);
        $filteredMerchantIdList = [];

        foreach ($merchantIdChunks as $merchantIdChunk) {
            // filter merchants who have crossed settlements above threshold
            $merchantsGmvList = $this->repo->transaction->fetchTotalAmountByTransactionTypeAboveThreshold(
                $merchantIdChunk, MConstants::PAYMENT, env(M2MConstants::M2M_REFERRAL_MIN_TRANSACTION_AMOUNT));

            $transactedMerchantIds = array_map(function ($element) {
                return $element[Entity::MERCHANT_ID];
            }, $merchantsGmvList);

            if (empty($transactedMerchantIds) === false)
            {
                $filteredMerchantIdList = array_merge($filteredMerchantIdList, $transactedMerchantIds);
            }

        }

        return CollectorDto::create($filteredMerchantIdList);
    }
}
