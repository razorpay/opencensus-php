<?php

namespace RZP\Models\Payout\SourceUpdater;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class PettyCashUpdater extends Base
{
    public function update()
    {
        try
        {
            $xperienceService = $this->app['xperience'];

            $xperienceService->pushPayoutStatusUpdate($this->payout, $this->mode);
        }
        catch (\Exception $e)
        {
            $trace = $this->app['trace'];

            $trace->traceException($e,
                                   Trace::ERROR,
                                   TraceCode::PETTY_CASH_PAYMENT_PAYOUT_UPDATER_ERROR,
                                   [
                                       'payout_id' => $this->payout->getPublicId(),
                                   ]);

            throw $e;
        }
    }
}
