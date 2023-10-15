<?php

namespace RZP\Jobs;

use App;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Services\RazorXClient;

class QueuedPayoutsInitiate extends Job
{
    protected $trace;

    /**
     * Balance Id for which queued payouts have to be processed
     *
     * @var string
     */
    protected $balanceId;

    // Overriding timeout with 300 seconds
    public $timeout = 300;

    public function __construct(string $mode, string $balanceId)
    {
        parent::__construct($mode);

        $this->balanceId = $balanceId;
    }

    public function handle()
    {
        parent::handle();

        $traceData = ['balance_id' => $this->balanceId];

        $this->trace->info(
            TraceCode::PAYOUT_QUEUED_INITIATE_REQUEST,
            $traceData);

        try
        {
            $summary = (new Payout\Core)->processDispatchForQueuedPayoutsForBalance($this->balanceId);

            $this->trace->info(
                TraceCode::PAYOUT_QUEUED_INITIATE_SUCCESS,
                $traceData + [
                    'summary' => $summary,
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PAYOUT_QUEUED_INITIATE_JOB_FAILED,
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
            'balance_id' => $this->balanceId,
        ];

        $this->handleWorkerTimeoutGracefully($context);

        $this->trace->info(TraceCode::BANKING_QUEUE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
        ]);
    }
}
