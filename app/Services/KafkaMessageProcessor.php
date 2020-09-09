<?php

namespace RZP\Services;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\BvsValidation\Core;

class KafkaMessageProcessor extends Job
{
    const API_BVS_EVENTS = 'api-bvs-validation-result-events';

    /**
     * Message Processor for Kafka Message,
     * Identifies and call modules/service based on the topicName
     *
     * @param string $topicName
     * @param array $payload
     * @param string|null $mode
     * @return bool <TRUE/FALSE> - True - processing success, False - in case of failure
     */
    public function process(string $topicName, array $payload, string $mode = null): bool
    {
        $this->mode = $mode;

        parent::__construct($mode);

        parent::handle();

        try
        {
            $tracePayload = [
                'job_attempts' => $this->attempts(),
                'mode' => $this->mode,
                'payload' => $payload['data'],
            ];

            $this->trace->info(TraceCode::ONBOARDING_BVS_VERIFICATION_JOB_REQUEST, $tracePayload);

            switch ($topicName) {
                //
                // BVS Validation Results
                //
                case self::API_BVS_EVENTS:
                    $core = new Core();

                    $core->process($payload['data']);

                    return true;
                default:
                    $this->trace->error('no processor defined for the topic - ' . $topicName);
                    return false;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::ONBOARDING_BVS_VERIFICATION_JOB_ERROR);
            return false;
        }
    }
}
