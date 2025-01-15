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

    }
}
