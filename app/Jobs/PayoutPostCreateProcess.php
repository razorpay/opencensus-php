<?php

namespace RZP\Jobs;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class PayoutPostCreateProcess extends Job
{
    protected $payoutId;

    protected $mode;
    /**
     * @param string $mode
     * @param string $payoutId
     */
    public function __construct(string $mode, string $payoutId)
    {
        parent::__construct($mode);

        $this->payoutId = $payoutId;
    }

    public function handle()
    {
        parent::handle();

        $traceData = ['payout_id' => $this->payoutId];

        $this->trace->info(
            TraceCode::PAYOUT_CREATE_SUBMITTED_INITIATE_REQUEST,
                     $traceData);

        try
        {
            $payout = $this->repoManager->payout->find($this->payoutId);

            (new Payout\Core)->processPayoutPostCreate($payout);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PAYOUT_CREATE_SUBMITTED_PROCESS_FAILED,
                $traceData);
        }
        finally
        {
            $this->delete();
        }
    }
}
