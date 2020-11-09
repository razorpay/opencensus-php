<?php

namespace RZP\Models\Payout\SourceUpdater;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class VendorPaymentUpdater extends Base
{
    public function update()
    {
        try
        {
            $vendorPaymentService = $this->app['vendor-payment'];

            $vendorPaymentService->pushPayoutStatusUpdate($this->payout, $this->mode);
        }
        catch (\Exception $e)
        {
            $trace = $this->app['trace'];

            $trace->traceException($e,
                                   Trace::ERROR,
                                   TraceCode::VENDOR_PAYMENT_PAYOUT_UPDATER_ERROR,
                                   [
                                       'payout_id' => $this->payout->getPublicId(),
                                   ]);
        }
    }
}
