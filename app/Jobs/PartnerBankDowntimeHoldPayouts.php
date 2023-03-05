<?php

namespace RZP\Jobs;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class PartnerBankDowntimeHoldPayouts extends Job
{
    protected $payoutId;

    protected $queueConfigKey = 'partner_bank_on_hold_payouts_process';

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
            TraceCode::PARTNER_BANK_ON_HOLD_PROCESSING_STARTED,
            $traceData);

        try
        {
            //For a payout id first checks if partner bank uptime
            //If partner bank is found up, the payout is processed
            //If partner bank is down, fails the payout instantly

            (new Payout\Core)->processPartnerBankDowntimeHoldPayouts($this->payoutId);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PARTNER_BANK_ON_HOLD_PROCESSING_FAILED,
                $traceData
            );
        }
        finally
        {
            //if the partner_bank_on_hold payout auto cancellation fails we can safely delete it
            //since the cron will take care of pulling the payout again for auto cancel
            //so no need to retry
            $this->delete();
        }
    }
}
