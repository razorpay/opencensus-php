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
     * @param string $topic
     * @param array $payload
     * @param string|null $mode
     * @return bool <TRUE/FALSE> - True - processing success, False - in case of failure
     */
    public function process(string $topic, array $payload, string $mode = null): bool
    {
        $this->mode = $mode;

        parent::__construct($mode);

        parent::handle();

        try
        {
            $traceTopicDetails = [
                'topic_name' => $topic,
                'mode' => $this->mode,
                'payload' => $payload
            ];

            $this->trace->info(TraceCode::KAFKA_MESSAGE_PROCESSOR_PAYLOAD,  $traceTopicDetails);

            switch ($topic) {
                //
                // BVS Validation Results
                //
                case self::API_BVS_EVENTS:

                    $tracePayload = [
                        'job_attempts' => $this->attempts(),
                        'mode' => $this->mode,
                        'payload' => $payload['data'],
                    ];

                    $this->trace->info(TraceCode::ONBOARDING_BVS_VERIFICATION_JOB_REQUEST, $tracePayload);

                    $core = new Core();

                    return $core->process($payload['data']);
                default:
                    $this->trace->error('no processor defined for the topic - ' . $topic);
                    return false;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::KAFKA_MESSAGE_PROCESSING_ERROR);
            return true;
        }
    }
}
