<?php

namespace RZP\Services;

use Illuminate\Foundation\Application;

use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Jobs\Kafka as KafkaJobs;
use RZP\Events\Kafka as KafkaEvents;
use RZP\Trace\Tracer;

class KafkaMessageProcessor
{
    // Topic name constants to map job
    const API_BVS_EVENTS = 'api-bvs-validation-result-events';
    const ADDRESS_DEDUPE_EVENT = 'address-dedupe-response';

    /** @var Application $app */
    protected $app;

    /** @var Trace $trace */
    protected $trace;

    public function __construct()
    {
        $this->app = \App::getFacadeRoot();
        $this->trace = $this->app['trace'];
    }

    /**
     * Message Processor for Kafka Message,
     * Identifies and call modules/service based on the topicName
     *
     * @param string $topic
     * @param array $payload
     * @param string|null $mode
     *
     * @return bool <TRUE/FALSE> - True - processing success, False - in case of failure
     */
    public function process(string $topic, array $payload, string $mode = null)
    {
        $traceTopicDetails = [
            'topicName' => $topic,
            'mode' => $mode,
            'payload' => $payload,
        ];
        $this->trace->info(TraceCode::KAFKA_MESSAGE_PROCESSOR_PAYLOAD, $traceTopicDetails);

        try {
            /** @var KafkaJobs\Job $job */
            $job = $this->getJob($topic, $payload, $mode);
            if (empty($job) == false)
            {
                $this->processJob($job);
                return true;
            }
            else
            {
                $this->trace->error('no processor defined for the topic - ' . $topic);
                return false;
            }
        } catch (\Exception $e) {

            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::KAFKA_MESSAGE_PROCESSING_ERROR,
                [
                    'topicName' => $topic,
                    'mode' => $mode,
                    'jobName' => $job->getJobName(),
                    'payload' => $payload,
                ]
            );
            return false;
        }
    }

    /**
     * @throws \Exception throws back the error if there is one handling the job,
     *                    useful to avoid marking the message processed in kafka
     */
    protected function processJob(KafkaJobs\Job $job)
    {
        try {
            $this->handleJobProcessing($job);

            $attrs = [
                'jobName' => $job->getJobName(),
                'mode' => $job->getMode(),
            ];

            Tracer::inSpan(['name' => 'Kafka/ProcessJob', 'attributes' => $attrs],
                function () use ($job) {
                    $job->handle();
                }
            );

            $this->handleJobProcessed($job);
        } catch (\Exception $e) {
            $this->handleJobFailed($job);
            throw $e;
        }
    }

    protected function getJob(string $topic, array $payload, string $mode = null)
    {
        switch ($topic) {
            case self::API_BVS_EVENTS:
                return new KafkaJobs\BvsValidationJob($payload['data'], $mode);
            case self::ADDRESS_DEDUPE_EVENT:
                return new BulkUploadConsumer($payload, $mode);
            default:
                return null;
        }
    }

    protected function handleJobProcessed(KafkaJobs\Job $job)
    {
        event(new KafkaEvents\JobProcessed($job));
    }

    protected function handleJobProcessing(KafkaJobs\Job $job)
    {
        event(new KafkaEvents\JobProcessing($job));
    }

    protected function handleJobFailed(KafkaJobs\Job $job)
    {
        event(new KafkaEvents\JobFailed($job));
    }
}
