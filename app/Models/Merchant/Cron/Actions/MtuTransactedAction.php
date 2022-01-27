<?php

namespace RZP\Models\Merchant\Cron\Actions;

use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\Merchant\Escalations\Core as EscalationCore;
use RZP\Models\Merchant\M2MReferral\Service as M2MService;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Trace\TraceCode;

class MtuTransactedAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $collectorData = $data["mtu_transacted_merchants"]; // since data collector is an array

        $merchantIdList = $collectorData->getData();

        if (count($merchantIdList) === 0)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $successCount = 0;

        foreach ($merchantIdList as $merchantId)
        {
            try
            {
                $this->pushSegmentEvent($merchantId);

                $successCount++;
            }
            catch (\Throwable $ex)
            {
                $this->app['trace']->traceException($ex, Trace::ERROR, TraceCode::CRON_ATTEMPT_ACTION_FAILURE, [
                    'args'        => $this->args,
                    'merchant_id' => $merchantId
                ]);
            }
        }

        $this->app['segment-analytics']->buildRequestAndSend();

        if ($successCount === 0)
        {
            $status = Constants::FAIL;
        }
        else
        {
            $status = ($successCount < count($merchantIdList)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        return new ActionDto($status);
    }

    private function pushSegmentEvent($merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        // fetch merchants first transaction details
        $merchantsTransaction = $this->repo->transaction->fetchFirstTransactionDetails($merchantId);

        $previousActivationStatus = $this->repo->state->getPreviousActivationStatus($merchant->getId());

        $referralCode = (new M2MService())->getReferralCodeIfApplicable($merchant);

        $properties = [
            'mtu'                         => true,
            'first_transaction_timestamp' => $merchantsTransaction['created_at'],
            'activation_status'           => $merchant->merchantDetail->getActivationStatus(),
            'previous_activation_status'  => $previousActivationStatus['name'],
            'is_m2m_referral'             => ($referralCode == null ? false : true),
            '$referralCode'               => $referralCode,
            'amount'                      => $merchantsTransaction['amount']
        ];

        $userDeviceDetail = $this->repo->user_device_detail->fetchByMerchantIdAndUserRole($merchantId);

        if (empty($userDeviceDetail) === false)
        {
            $properties['signup_source'] = $userDeviceDetail->getSignupSource();
        }

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $properties, SegmentEvent::MTU_TRANSACTED, $merchantsTransaction['created_at']);

        (new EscalationCore())->applyMtuCouponIfEligible($merchant);
    }
}
