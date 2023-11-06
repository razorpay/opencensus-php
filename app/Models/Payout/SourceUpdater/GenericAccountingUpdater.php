<?php

namespace RZP\Models\Payout\SourceUpdater;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\Status;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\GenericAccountingIntegration\Service as AccountingService;

class GenericAccountingUpdater extends Base
{
    public function update()
    {
        $merchantId = $this->payout->getMerchantId();

        if(AccountingService::isSyncPayoutsEnabled($merchantId) === false)
        {
            return null;
        }

        if (($this->payout->getStatus() != Status::PROCESSED) and
            ($this->payout->getStatus() != Status::REVERSED))
        {
            return null;
        }

        $trace = $this->app['trace'];

        $trace->info(TraceCode::GENERIC_ACCOUNTING_PAYOUT_UPDATER_INFO, [
            "merchant_id" => $merchantId,
        ]);

        if ($this->mode != Mode::LIVE)
        {
            return null;
        }

        try
        {
            $gaiService = $this->app['accounting-integration-service'];

            $gaiService->pushPayoutStatusUpdate($this->payout, $this->mode);
        }
        catch (\Exception $e)
        {
            $trace->traceException($e,
                                   Trace::ERROR,
                                   TraceCode::GENERIC_ACCOUNTING_PAYOUT_UPDATER_ERROR,
                                   [
                                       'payout_id' => $this->payout->getPublicId(),
                                   ]);
        }
    }
}
