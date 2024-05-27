<?php

namespace RZP\Models\Payout\SourceUpdater;

use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class ChargeCollections extends Base
{
    public function update()
    {
        try
        {
            $chargeCollectionsService = $this->app['charge_collections'];

            $chargeCollectionsService->pushPayoutStatusUpdate($this->payout, $this->mode);
        }
        catch (\Exception $e)
        {
            $trace = $this->app['trace'];

            $trace->traceException($e,
                Trace::ERROR,
                TraceCode::CHARGE_COLLECTIONS_PAYOUT_UPDATER_ERROR,
                [
                    'payout_id' => $this->payout->getPublicId(),
                ]);

            throw $e;
        }
    }
}
