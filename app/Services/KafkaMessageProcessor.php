<?php

namespace RZP\Services;

use Illuminate\Foundation\Application;

use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\AutoKyc\OcrService\WebsitePolicyProcessor;
use RZP\Trace\TraceCode;
use RZP\Jobs\Kafka as KafkaJobs;
use RZP\Events\Kafka as KafkaEvents;
use RZP\Trace\Tracer;

class KafkaMessageProcessor
{
    // Topic name constants to map job
    const API_BVS_EVENTS                = 'api-bvs-validation-result-events';
    const ADDRESS_DEDUPE_EVENT          = 'address-dedupe-response';
    const RAW_ADDRESS_CONTACTS          = 'raw-address-contacts';
    const MERCHANT_WEBSITE_INFO         = 'merchant-website-info-result';
    const LEGAL_DOCUMENTS_EVENTS        = 'api-bvs-legal-document-result-events';
    const INVALID_ADDRESS_EVENTS        = 'invalid-address-events';
    const WEBSITE_POLICY_EVENTS         = 'pg-website-verification-notification-events';
    const NEGATIVE_KEYWORDS_EVENTS      = 'api-bvs-kyc-document-result-events';
    const MCC_NOTIFICATION_EVENTS       = 'pg-mcc-notification-events';
    const API_PG_LEDGER_ACKNOWLEDGMENTS = 'outbox_jobs_api';
    const MERCHANT_PAYMENTS_ENABLED_CALLBACK_EVENTS = 'merchant-payments-enabled-callback';
    const PGOS_STAGE_CDC_EVENTS               = 'cdc_events_mysql_stage_pg_onboarding';
    const PGOS_PROD_CDC_EVENTS               = 'cdc_events_mysql_prod_pg_onboarding';

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

        /** @var KafkaJobs\Job $job */
        $job = $this->getJob($topic, $payload, $mode);

        try
        {
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
        }
        catch (\Exception $e)
        {
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
        switch ($topic)
        {
            case self::API_BVS_EVENTS:
                return new KafkaJobs\BvsValidationJob($payload['data'], $mode);

            case self::ADDRESS_DEDUPE_EVENT:
                return new BulkUploadConsumer($payload, $mode);

            case self::RAW_ADDRESS_CONTACTS:
                return new RawAddressContactsConsumer($payload, $mode);

            case self::MERCHANT_WEBSITE_INFO:
                return new WebsiteUpdateProcessor($payload, $mode);

            case self::LEGAL_DOCUMENTS_EVENTS:
                return new KafkaJobs\BvsLegalDocumentsJob($payload['data'], $mode);

            case self::API_PG_LEDGER_ACKNOWLEDGMENTS:
                return new KafkaJobs\PGLedgerAcknowledgmentJob($payload, $mode);

            case self::INVALID_ADDRESS_EVENTS:
                return new InvalidAddressConsumer($payload, $mode);

            case self::WEBSITE_POLICY_EVENTS:
                return new WebsitePolicyConsumer($payload['data'], $mode);

            case self::NEGATIVE_KEYWORDS_EVENTS:
                return new NegativeKeywordsConsumer($payload['data'], $mode);

            case self::MCC_NOTIFICATION_EVENTS:
                return new MccCategorisationConsumer($payload['data'], $mode);

            case self::MERCHANT_PAYMENTS_ENABLED_CALLBACK_EVENTS:
                return new MerchantPaymentsEnabledCallbackConsumer($payload, $mode);

            case self::PGOS_STAGE_CDC_EVENTS:
            case self::PGOS_PROD_CDC_EVENTS:
                return new KafkaJobs\PgosCdcEventsJob($payload, $mode);

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
