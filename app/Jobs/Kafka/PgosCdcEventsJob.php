<?php


namespace RZP\Jobs\Kafka;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Service;

class PgosCdcEventsJob extends Job
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
            'mode'         => $this->mode,
            'payload'      => $this->getPayload(),
            'task_id'      => $taskId,
            'job'          => 'PgosCdcEventsJob'
        ];

        $this->trace->info(TraceCode::KAFKA_MESSAGE_PROCESSOR_PAYLOAD, $tracePayload);

        try
        {
            (new Service)->savePGOSDataToAPI($this->payload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::KAFKA_MESSAGE_PROCESSING_ERROR,
                $this->payload);
        }

    }
}
