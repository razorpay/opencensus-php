<?php

namespace RZP\Jobs\Kafka;

use RZP\Models\LedgerOutbox\Core;
use RZP\Trace\TraceCode;

class PGLedgerAcknowledgmentJob extends Job
{
    /**
     * @throws \Throwable
     */
    public function handle()
    {
        $taskId = gen_uuid();

        $this->setTaskId($taskId);

        parent::handle();

        $tracePayload = [
            Constants::JOB_ATTEMPTS => $this->attempts(),
            Constants::MODE         => $this->mode,
            Constants::PAYLOAD      => $this->getPayload(),
            Constants::TASK_ID      => $taskId
        ];

        $this->trace->info(TraceCode::PG_LEDGER_CALLBACK_REQUEST, $tracePayload);

        $core = new Core();

        $core->processLedgerAcknowledgement($this->payload);
    }
}
