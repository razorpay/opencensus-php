<?php

namespace RZP\Jobs;

use App;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class QueuedPayouts extends Job
{
    protected $trace;

    protected $payoutId;

    /**
     * @var string
     */
    protected $queueConfigKey = 'queued_payouts';

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

            if ($payout !== null)
            {
                $this->trace->info(
                    TraceCode::PAYOUT_QUEUE_SUCCESS,
                    $traceData + [
                        'payout_status' => $payout->getStatus(),
                    ]);
            }
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
            //
            // If the queued payout's processing fails due to any reason,
            // we can safely delete the job. The cron will take care of
            // putting it back for dispatch again.
            // Most common reason to fail would be "not enough balance".
            // There could have been enough balance during dispatch but
            // could have gotten over during the processing.
            //
            $this->delete();
        }
    }
}
