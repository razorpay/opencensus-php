<?php

namespace RZP\Jobs;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class PayoutsAutoExpire extends Job
{
    protected $payoutId;

    protected $queueConfigKey = 'payouts_auto_expire';

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
            TraceCode::PAYOUT_AUTO_EXPIRY_JOB_STARTED,
            $traceData
            );

        try
        {
            (new Payout\Core)->processAutoExpiryOfPayouts($this->payoutId);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PAYOUT_AUTO_EXPIRY_JOB_FAILED,
                $traceData
            );
        }
        finally
        {
            $this->delete();
        }
    }
}
