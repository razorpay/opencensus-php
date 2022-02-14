<?php

namespace RZP\Models\Merchant\Cron\Actions;


use Carbon\Carbon;
use RZP\Diag\EventCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\Merchant\M2MReferral\Constants as M2MConstants;
use RZP\Models\Merchant\M2MReferral\FriendBuy\Constants as FBConstants;
use RZP\Models\Merchant\M2MReferral\Service as M2MService;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Trace\TraceCode;

class FriendBuySendPurchaseEventsAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $collectorData = $data["friend_buy_send_purchase_events"]; // since data collector is an array
        $transactedMerchantIds = $collectorData->getData();

        if (count($transactedMerchantIds) === 0)
        {
            $this->app['trace']->info(TraceCode::SEND_FRIENDBUY_PURCHASE_EVENT_CRON_TRACE, [
                'type'   => 'transacted_m2m_merchants',
                'reason' => 'no merchants to run the cron'
            ]);
            return new ActionDto(Constants::SKIPPED);
        }

        $successCount = 0;
        $this->app['trace']->info(TraceCode::SEND_FRIENDBUY_PURCHASE_EVENT_CRON_TRACE, [
            'type'            => 'transacted_m2m_merchants',
            'merchants_count' => count($transactedMerchantIds),
            'merchants'       => $transactedMerchantIds
        ]);

        foreach ($transactedMerchantIds as $merchantId)
        {
            try
            {
                $properties = [
                    FBConstants::AMOUNT   => env(M2MConstants::M2M_REFERRAL_MIN_TRANSACTION_AMOUNT),
                    FBConstants::CURRENCY => 'INR'
                ];

                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $code = (new M2MService())->sendPurchaseEventIfApplicable($merchant, $properties);

                $this->app['trace']->info(TraceCode::SEND_FRIENDBUY_PURCHASE_EVENT_CRON_TRACE, [
                    'type'     => 'send_event',
                    'merchant' => $merchantId
                ]);

                $properties = [
                    'purchase_timestamp' => Carbon::now()->getTimestamp(),
                    'referral_code'      => $code
                ];

                $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                    $merchant, $properties, SegmentEvent::PURCHASE_EVENT_SENT);

                $this->app['diag']->trackOnboardingEvent(EventCode::MERCHANT_PURCHASE_EVENT, $merchant, null, $properties);

                $successCount += 1;
            }
            catch (\Throwable $ex)
            {
                $this->app['trace']->traceException($ex, Trace::ERROR, TraceCode::CRON_ATTEMPT_ACTION_FAILURE, [
                    '$merchantId'   => $merchantId,
                    'args'        => $this->args
                ]);
            }
        }

        if ($successCount === 0)
        {
            $status = Constants::FAIL;
        }
        else
        {
            $status = ($successCount < count($transactedMerchantIds)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        return new ActionDto($status);
    }
}
