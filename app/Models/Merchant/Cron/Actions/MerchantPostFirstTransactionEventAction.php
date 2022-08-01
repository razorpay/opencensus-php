<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Services\Segment\Constants as SegmentConstants;

class MerchantPostFirstTransactionEventAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $collectorData = $data["post_first_transacted_mids"]; // since data collector is an array

        $merchants = $collectorData->getData();

        if (count($merchants) === 0)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $successCount = 0;

        foreach ($merchants['merchantIds'] as $merchantId)
        {
            try
            {
                $this->pushSegmentEvent($merchantId['id']);

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
            $status = ($successCount < count($merchants)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        return new ActionDto($status);
    }

    private function pushSegmentEvent($merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $properties = [
             SegmentConstants::OBJECT     => SegmentConstants::ONE_MONTH_POST,
             SegmentConstants::ACTION     => SegmentConstants::MTU,
             SegmentConstants::SOURCE     => SegmentConstants::BE
        ];

        $userDeviceDetail = $this->repo->user_device_detail->fetchByMerchantIdAndUserRole($merchantId);

        if (empty($userDeviceDetail) === false)
        {
            $properties[SegmentConstants::SIGNUP_SOURCE] = $userDeviceDetail->getSignupSource();
        }

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $properties, SegmentEvent::ONE_MONTH_POST_MTU);
    }
}
