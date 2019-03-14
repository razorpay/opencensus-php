<?php

namespace RZP\Jobs;

use App;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class QueuedPayouts extends Job
{
    protected $trace;

    protected $payoutId;

    public function __construct(string $mode, string $payoutId)
    {
        parent::__construct($mode);

        $this->payoutId = $payoutId;
    }

    public function handle()
    {
        parent::handle();

        $traceData = [ 'payout_id' => $this->payoutId ];

        $this->trace->info(
            TraceCode::PAYOUT_QUEUE_REQUEST,
            $traceData);

        try
        {
            $payout = (new Payout\Core)->processQueuedPayout($this->payoutId);

            $this->trace->info(
                TraceCode::PAYOUT_QUEUE_SUCCESS,
                $traceData + [
                    'payout_status' => $payout->getStatus(),
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PAYOUT_QUEUE_JOB_FAILURE_EXCEPTION,
                $traceData);
        }
        finally
        {
            $this->delete();
        }
    }
}
