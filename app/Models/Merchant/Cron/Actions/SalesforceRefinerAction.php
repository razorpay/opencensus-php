<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Models\Merchant;
use RZP\Models\Partner\Service as PartnerService;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Services\Segment\EventCode as SegmentEvent;

class SalesforceRefinerAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $collectorData = $data[Constants::SALESFORCE_REFINER]; // since data collector is an array

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
                $this->app['trace']->traceException($ex, Trace::ERROR, TraceCode::SALESFORCE_REFINER_CRON_ATTEMPT_ACTION_FAILURE, [
                    'args'        => $this->args,
                    'merchant_id' => $merchantId
                ]);
            }
        }

        $this->app['segment-analytics']->buildRequestAndSend(true);

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
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $salesforceResponse = (new PartnerService())->getPartnerSalesPOC($merchantId);

        $isManaged = empty($salesforceResponse) === false;

        $properties = [
            'merchant_id'         => $merchantId,
            'is_managed_account'  => $isManaged,
        ];

        $this->app['segment-analytics']->pushIdentifyEvent(
            $merchant, $properties);
    }
}
