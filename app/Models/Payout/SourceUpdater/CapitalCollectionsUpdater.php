<?php

namespace RZP\Models\Payout\SourceUpdater;

use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class CapitalCollectionsUpdater extends Base
{
    public function update()
    {
        try
        {
            $collectionsService = $this->app['capital_collections'];

            $collectionsService->pushPayoutStatusUpdate($this->payout, $this->mode);
        }
        catch (\Exception $e)
        {
            $trace = $this->app['trace'];

            $trace->traceException($e,
                                   Trace::ERROR,
                                   TraceCode::CAPITAL_COLLECTIONS_PAYOUT_UPDATER_ERROR,
                                   [
                                       'payout_id' => $this->payout->getPublicId(),
                                   ]);
            throw $e;
        }
    }
}
