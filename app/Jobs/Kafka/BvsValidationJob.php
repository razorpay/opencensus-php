<?php

namespace RZP\Jobs\Kafka;

use App;
use RZP\Http\Middleware\EventTracker;
use RZP\Models\Merchant\BvsValidation\Core as BvsCore;
use RZP\Trace\TraceCode;

class BvsValidationJob extends Job
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
            'job_attempts' => $this->attempts(),
            'mode' => $this->mode,
            'payload' => $this->getPayload(),
            'task_id' => $taskId
        ];

        $this->trace->info(TraceCode::ONBOARDING_BVS_VERIFICATION_JOB_REQUEST, $tracePayload);

        $bvsCore = new BvsCore();

        $bvsCore->process($this->payload);
    }
}
