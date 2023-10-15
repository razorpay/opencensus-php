<?php

namespace RZP\Jobs;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Services\RazorXClient;
use RZP\Models\Settlement\SlackNotification;

class PayoutPostCreateProcess extends Job
{
    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 10;

    protected $payoutId;

    protected $queueFlag;

    protected $mode;

    /**
     * @param string $mode
     * @param string $payoutId
     * @param bool $queueFlag
     */
    public function __construct(string $mode, string $payoutId, bool $queueFlag)
    {
        parent::__construct($mode);

        $this->payoutId = $payoutId;

        $this->queueFlag = $queueFlag;
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
            (new Payout\Core)->processPayoutPostCreate($this->payoutId, $this->queueFlag);

            $this->delete();
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PAYOUT_CREATE_SUBMITTED_PROCESS_FAILED,
                $traceData);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        $data = [
                'payout_id'      => $this->payoutId,
            ];

        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            $this->release(self::MAX_RETRY_DELAY);

            $this->trace->info(TraceCode::PAYOUT_CREATE_SUBMITTED_PROCESS_JOB_RELEASED, $data);
        }
        else
        {
            $this->delete();

            $this->trace->error(TraceCode::PAYOUT_CREATE_SUBMITTED_PROCESS_JOB_DELETED, $data);

            $operation = 'Post payout create process fetch job failed';

            (new SlackNotification)->send($operation, $data, null, 1, 'x-payouts-core-alerts');
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

        $this->handleWorkerTimeoutGracefully($context, self::MAX_RETRY_ATTEMPT, self::MAX_RETRY_DELAY);

        $this->trace->info(TraceCode::BANKING_QUEUE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
        ]);
    }
}
