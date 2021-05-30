<?php

namespace RZP\Models\Payout\SourceUpdater;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;

class XPayrollUpdater extends Base
{
    public function update()
    {
        try
        {
            $xpayrollService = $this->app['xpayroll'];

            $xpayrollService->pushPayoutStatusUpdate($this->payout, $this->mode);
        }
        catch (\Exception $e)
        {
            $trace = $this->app['trace'];

            $trace->traceException($e,
                                   Trace::ERROR,
                                   TraceCode::XPAYROLL_PAYOUT_UPDATER_ERROR,
                                   [
                                       'payout_id' => $this->payout->getPublicId(),
                                   ]);
            throw $e;
        }
    }
}
