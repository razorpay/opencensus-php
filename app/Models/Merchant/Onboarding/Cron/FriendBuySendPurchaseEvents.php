<?php


namespace RZP\Models\Merchant\Onboarding\Cron;

use App;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Table;
use RZP\Base\RepositoryManager;
use RZP\Models\Merchant\Service;
use RZP\Models\Merchant\Constants;
use Illuminate\Foundation\Application;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\M2MReferral\Status;
use RZP\Models\Merchant\Constants as MConstants;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Merchant\M2MReferral\Service as M2MService;
use RZP\Models\Merchant\Onboarding\Cron\Constants as CronConstants;
use RZP\Models\Merchant\M2MReferral\Constants as M2MConstants;
use RZP\Models\Merchant\M2MReferral\FriendBuy\Constants as FBConstants;

class FriendBuySendPurchaseEvents extends BaseJobProcessor
{
    public function execute($input)
    {
        $merchantIdList = $this->repo->m2m_referral->fetchMerchantsInReferralState([Status::SIGN_UP, Status::SIGNUP_EVENT_SENT]);

        $this->trace->info(TraceCode::SEND_FRIENDBUY_PURCHASE_EVENT_CRON_TRACE, [
            'type'            => 'm2m_merchants',
            'merchants_count' => count($merchantIdList),
            'merchants'       => $merchantIdList
        ]);

        $merchantIdChunks = array_chunk($merchantIdList, 100);

        foreach ($merchantIdChunks as $merchantIdChunk)
        {
            // filter merchants who have crossed settlements above threshold
            $merchantsGmvList = $this->repo->transaction->fetchTotalAmountByTransactionTypeAboveThreshold(
                $merchantIdChunk, MConstants::PAYMENT, env(M2MConstants::M2M_REFERRAL_MIN_TRANSACTION_AMOUNT));

            $transactedMerchantIds = array_map(function($element) {
                return $element[Entity::MERCHANT_ID];
            }, $merchantsGmvList);

            if (empty($transactedMerchantIds) === true)
            {
                $this->trace->info(TraceCode::SEND_FRIENDBUY_PURCHASE_EVENT_CRON_TRACE, [
                    'type'   => 'transacted_m2m_merchants',
                    'reason' => 'no merchants to run the cron'
                ]);

                return;
            }

            $this->trace->info(TraceCode::SEND_FRIENDBUY_PURCHASE_EVENT_CRON_TRACE, [
                'type'            => 'transacted_m2m_merchants',
                'merchants_count' => count($transactedMerchantIds),
                'merchants'       => $transactedMerchantIds
            ]);

            foreach ($transactedMerchantIds as $merchantId)
            {
                $properties = [
                    FBConstants::AMOUNT   => env(M2MConstants::M2M_REFERRAL_MIN_TRANSACTION_AMOUNT),
                    FBConstants::CURRENCY => 'INR'
                ];

                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $code = (new M2MService())->sendPurchaseEventIfApplicable($merchant, $properties);

                $this->trace->info(TraceCode::SEND_FRIENDBUY_PURCHASE_EVENT_CRON_TRACE, [
                    'type'     => 'send_event',
                    'merchant' => $merchantId
                ]);

                $properties = [
                    'purchase_timestamp' => Carbon::now()->getTimestamp(),
                    'referral_code'      => $code
                ];

                $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                    $merchant, $properties, SegmentEvent::PURCHASE_EVENT_SENT);
            }
        }
    }
}
