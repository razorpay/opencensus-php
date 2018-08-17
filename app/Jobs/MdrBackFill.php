<?php

namespace RZP\Jobs;

use Cache;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class MdrBackFill extends Job
{
    // using the batch queue as it has the max visibility timeout.
    protected $queueConfigKey = 'batch';

    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(TraceCode::PAYMENT_MDR_UPDATE_JOB_RECEIVED);

            $mutex = app('api.mutex');

            $mutexKey = $this->mode . '_' . 'payment_mdr_update';

            $mutex->acquireAndRelease($mutexKey, function ()
            {
                $lastUpdatedPaymentId = null;
                $lastUpdatedPaymentCapturedAt = 1514745000;

                $lastUpdatedPaymentData = Cache::get($this->mode . '_' . 'payment_mdr_update_data');

                if ($lastUpdatedPaymentData !== null)
                {
                    $lastUpdatedPaymentData = explode(':', $lastUpdatedPaymentData);

                    $lastUpdatedPaymentId         = $lastUpdatedPaymentData[0];
                    $lastUpdatedPaymentCapturedAt = intval($lastUpdatedPaymentData[1]);
                }

                $this->trace->info(TraceCode::PAYMENT_MDR_LAST_UPDATED_DATA, [
                    'payment_id'          => $lastUpdatedPaymentId,
                    'payment_captured_at' => $lastUpdatedPaymentCapturedAt,
                ]);

                (new Payment\Core)->updateMdr($lastUpdatedPaymentId, $lastUpdatedPaymentCapturedAt);
            }, $ttl = 120, $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_MDR_UPDATE_IN_PROGRESS);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL, TraceCode::PAYMENT_MDR_UPDATE_ERROR);
        }
        finally
        {
            $this->delete();
        }
    }
}
