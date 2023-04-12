<?php

namespace RZP\Jobs;

use App;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class ScheduledPayoutsProcess extends Job
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
            TraceCode::PAYOUT_SCHEDULE_PROCESS_REQUEST,
            $traceData);

        try
        {
            $payout = (new Payout\Core)->processScheduledPayout($this->payoutId);

            if($payout !== null)
            {
                $this->trace->info(
                    TraceCode::PAYOUT_SCHEDULE_PROCESS_SUCCESS,
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
                TraceCode::PAYOUT_SCHEDULE_PROCESS_JOB_FAILURE_EXCEPTION,
                $traceData);
        }
        finally
        {
            $this->delete();
        }
    }
}
