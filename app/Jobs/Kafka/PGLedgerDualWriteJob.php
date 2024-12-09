<?php

namespace RZP\Jobs\Kafka;

use RZP\Trace\TraceCode;

class PGLedgerDualWriteJob extends Job
{
    /**
     * @throws \Throwable
     */
    public function handle()
    {
        $this->trace->info(TraceCode::PG_LEDGER_DUAL_WRITE_REQUEST);
    }
}