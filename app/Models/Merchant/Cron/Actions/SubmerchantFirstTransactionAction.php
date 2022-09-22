<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Services\Segment\EventCode as SegmentEvent;

class SubmerchantFirstTransactionAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $collectorData = $data[Constants::SUBMERCHANT_FIRST_TRANSACTION]; // since data collector is an array

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
        $merchantCore = new Merchant\Core();

        $partners = $merchantCore->fetchAffiliatedPartners($merchantId);

        foreach ($partners as $partner)
        {
            $properties = [
                'merchantId' => $merchantId,
                'partnerId'  => $partner->getId(),
            ];

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $partner, $properties, SegmentEvent::SUBMERCHANT_FIRST_TRANSACTION);
        }
    }
}
