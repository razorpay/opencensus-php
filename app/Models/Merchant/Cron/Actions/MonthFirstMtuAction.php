<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Services\Segment\EventCode as SegmentEvent;

class MonthFirstMtuAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $collectorData = $data["mtu_transacted_merchants"]; // since data collector is an array

        $merchantsData = $collectorData->getData();

        if (count($merchantsData) === 0)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $successCount = 0;

        foreach ($merchantsData as $merchant)
        {
            try
            {
                $this->pushSegmentEvent($merchant);

                $successCount++;
            }
            catch (\Throwable $ex)
            {
                $this->app['trace']->traceException($ex, Trace::ERROR, TraceCode::CRON_ATTEMPT_ACTION_FAILURE, [
                    'args'        => $this->args,
                    'merchant_id' => $merchant[0]
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
            $status = ($successCount < count($merchantsData)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        return new ActionDto($status);
    }

    private function pushSegmentEvent($merchantData)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantData[0]);

        // fetch merchants first transaction details
        //$merchantsTransaction = $this->repo->transaction->fetchFirstTransactionDetails($merchantId);

        //$previousActivationStatus = $this->repo->state->getPreviousActivationStatus($merchant->getId());

        //$referralCode = (new M2MService())->getReferralCodeIfApplicable($merchant);

        $properties = [
            'month_first_mtu'             => true,
            'product'                     => $merchantData[1]

        ];

        $userDeviceDetail = $this->repo->user_device_detail->fetchByMerchantIdAndUserRole($merchantData[0]);

        if (empty($userDeviceDetail) === false)
        {
            $properties['signup_source'] = $userDeviceDetail->getSignupSource();
        }

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $properties, SegmentEvent::MONTH_FIRST_MTU_TRANSACTED);

    }
}
