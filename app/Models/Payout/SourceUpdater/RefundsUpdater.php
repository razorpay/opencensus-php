<?php

namespace RZP\Models\Payout\SourceUpdater;

use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class RefundsUpdater extends Base
{
    public function update()
    {
        try
        {
            $refundsService = $this->app['scrooge'];

            $refundsService->pushPayoutStatusUpdate($this->payout, $this->mode);
        }
        catch (\Exception $e)
        {
            $trace = $this->app['trace'];

            $trace->traceException($e,
                Trace::ERROR,
                TraceCode::REFUNDS_PAYOUT_UPDATER_ERROR,
                [
                    'payout_id' => $this->payout->getPublicId(),
                ]);

            //Throw exception for enabling retries to handle errors while updating refund status.
            //Default retry mechanism of payout source updater will be used.
            throw $e;
        }
    }
}
