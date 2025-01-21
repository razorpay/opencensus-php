<?php

namespace RZP\Jobs\Kafka;

use RZP\Models\Transaction\DualWriteCore;
use RZP\Trace\TraceCode;

class PGLedgerDualWriteJob extends Job
{
    /**
     * @throws \Throwable
     */
    protected $jobName = 'pg_ledger_dual_write_job';

    public function handle()
    {

    }
}
