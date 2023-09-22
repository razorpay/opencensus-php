<?php

namespace RZP\Jobs;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Services\RazorXClient;

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

    /**
     * Defines how the job is handled in an event of worker timeout
     */
    protected function beforeJobKillCleanUp($variant = RazorXClient::DEFAULT_CASE)
    {
        $this->trace->count(Metric::RAZORPAYX_PAYOUTS_BANKING_QUEUES_TIMEOUT_COUNT, [
            'job_name'   => $this->getJobName() ?? '',
            'mode'       => $this->getMode() ?? '',
        ]);

        parent::beforeJobKillCleanUp($variant);

        $this->trace->info(TraceCode::BANKING_QUEUE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
        ]);
    }
}
