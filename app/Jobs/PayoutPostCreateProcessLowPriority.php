<?php

namespace RZP\Jobs;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement\SlackNotification;

class PayoutPostCreateProcessLowPriority extends Job
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
            TraceCode::PAYOUT_CREATE_SUBMITTED_INITIATE_REQUEST_LOW_PRIORITY,
                     $traceData);

        $startTime = microtime(true);

        try
        {
            (new Payout\Core)->processPayoutPostCreateLowPriority($this->payoutId, $this->queueFlag);

            $this->delete();
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PAYOUT_CREATE_SUBMITTED_PROCESS_FAILED_LOW_PRIORITY,
                $traceData);

            $this->checkRetry();
        }

        $this->trace->info(
            TraceCode::PAYOUT_CREATE_SUBMITTED_RESPONSE_LOW_PRIORITY,
            [
                'worker_start_taken' => $startTime,
                'worker_end_time'    => microtime(true)
            ]);

    }

    protected function checkRetry()
    {
        $data = [
                'payout_id'      => $this->payoutId,
            ];

        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            $this->release(self::MAX_RETRY_DELAY);

            $this->trace->info(TraceCode::PAYOUT_CREATE_SUBMITTED_PROCESS_JOB_RELEASED_LOW_PRIORITY, $data);
        }
        else
        {
            $this->delete();

            $this->trace->error(TraceCode::PAYOUT_CREATE_SUBMITTED_PROCESS_JOB_DELETED_LOW_PRIORITY, $data);

            $operation = 'Post payout create process fetch job failed';

            (new SlackNotification)->send($operation, $data, null, 1, 'x-payouts-core-alerts');
        }
    }
}
