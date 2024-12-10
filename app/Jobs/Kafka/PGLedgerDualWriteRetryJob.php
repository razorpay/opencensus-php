<?php

namespace RZP\Jobs\Kafka;

use RZP\Models\Transaction\DualWriteCore;
use RZP\Trace\TraceCode;

class PGLedgerDualWriteRetryJob extends Job
{
    /**
     * @throws \Throwable
     */
    protected $jobName = 'pg_ledger_dual_write_retry_job';

    public function handle()
    {
        $taskId = gen_uuid();

        $this->setTaskId($taskId);

        parent::handle();

        $tracePayload = [
            Constants::JOB_ATTEMPTS => $this->attempts(),
            Constants::MODE => $this->mode,
            Constants::PAYLOAD => $this->getPayload(),
            Constants::TASK_ID => $taskId
        ];

        $this->trace->info(TraceCode::PG_LEDGER_DUAL_WRITE_REQUEST, $tracePayload);

        $dualWriteCore = new DualWriteCore();

        $dualWriteCore->validateAttemptsAndProcessLedgerDualWrite($this->payload,$this->getJobName());
    }
}
