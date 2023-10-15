<?php

namespace RZP\Jobs;

use App;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Services\RazorXClient;

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

        $context = [
            'payout_id' => $this->payoutId,
        ];

        $this->handleWorkerTimeoutGracefully($context);

        $this->trace->info(TraceCode::BANKING_QUEUE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
        ]);
    }
}
