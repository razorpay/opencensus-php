<?php

namespace RZP\Jobs;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class OnHoldPayoutsProcess extends Job
{
    protected $payoutId;

    protected $queueConfigKey = 'on_hold_payouts_process';

    public function __construct(string $mode, string $payoutId)
    {
        parent::__construct($mode);

        $this->payoutId = $payoutId;
    }

    public function handle()
    {
        parent::handle();

        $traceData =
            [
                'payout_id' => $this->payoutId,
            ];

        $this->trace->info(
            TraceCode::ON_HOLD_PAYOUT_PROCESSING_JOB_STARTED,
            $traceData);

        try
        {
            //For a payout id first checks if bene bank is up even if merchant sla is breached for payout
            //If bene bank is found up, the payout is processed
            //If bene bank is down, checks for merchant sla and if sla is breached, fails the payout instantly

            (new Payout\Core)->processOnHoldPayouts($this->payoutId);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::ON_HOLD_PAYOUT_PROCESSING_JOB_FAILED,
                $traceData
            );
        }
        finally
        {
            //if the on_hold payout auto cancellation fails we can safely delete it
            //since the cron will take care of pulling the payout again for auto cancel
            //so no need to retry
            $this->delete();
        }
    }
}
