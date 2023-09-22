<?php

namespace RZP\Jobs;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Services\RazorXClient;

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
