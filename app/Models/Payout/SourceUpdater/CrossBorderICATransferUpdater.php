<?php

namespace RZP\Models\Payout\SourceUpdater;

use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class CrossBorderICATransferUpdater extends Base
{
    public function update()
    {
        try
        {
            $crossBorderImportService = $this->app['cross_border_import_service'];

            $crossBorderImportService->pushPayoutStatusUpdate($this->payout, $this->mode);
        }
        catch (\Throwable $e)
        {
            $trace = $this->app['trace'];

            $trace->traceException($e,
                Trace::ERROR,
                TraceCode::CROSS_BORDER_IMPORT_PAYOUT_STATUS_UPDATER_ERROR,
                [
                    'payout_id' => $this->payout->getPublicId(),
                ]);

            throw $e;
        }
    }
}
