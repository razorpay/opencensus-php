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
            $query = "select sum(base_amount) amount,merchant_id from payments_v1 where merchant_id in (%s) group by merchant_id having amount>=%s limit %s";

            $query = sprintf($query, "'" . implode("','", $merchantIdChunk) . "'", env(M2MConstants::M2M_REFERRAL_MIN_TRANSACTION_AMOUNT), count($merchantIdChunk) + 1);

            // fetch all merchants who've atleast breached lowest payments threshold
            $queryResponse = $this->app['apache.pinot']->getDataFromPinot($query);

            if (empty($queryResponse) === true)
            {
                $this->app['trace']->info(TraceCode::ESCALATION_ATTEMPT_SKIPPED, [
                    'type'   => 'm2m_send_purchase_event',
                    'reason' => 'no merchants found',
                    'step'   => 'transacted_merchants'
                ]);

                continue;
            }

            $this->app['trace']->info(TraceCode::ESCALATION_ATTEMPT, [
                'merchants_count' => count($queryResponse),
                'type'            => 'm2m_send_purchase_event',
                'step'            => 'transacted_merchants'
            ]);

            $merchantIdList = array_column($queryResponse, Entity::MERCHANT_ID);

            $filteredMerchantIdList = array_merge($filteredMerchantIdList, $merchantIdList);

        }

        return CollectorDto::create($filteredMerchantIdList);
    }
}
