<?php

namespace RZP\Models\Payout\SourceUpdater;

use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class SettlementsUpdater extends Base
{
    public function update()
    {
        try
        {
            $settlementsService = $this->app['settlements_payout'];

            $settlementsService->pushPayoutStatusUpdate($this->payout, $this->mode);
        }
        catch (\Exception $e)
        {
            $trace = $this->app['trace'];

            $trace->traceException($e,
                Trace::ERROR,
                TraceCode::SETTLEMENTS_PAYOUT_UPDATER_ERROR,
                [
                    'payout_id' => $this->payout->getPublicId(),
                ]);

            throw $e;
        }
    }
}
