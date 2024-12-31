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
    const API_BVS_EVENTS                            = 'api-bvs-validation-result-events';
    const ADDRESS_DEDUPE_EVENT                      = 'address-dedupe-response';
    const RAW_ADDRESS_CONTACTS                      = 'raw-address-contacts';
    const MERCHANT_WEBSITE_INFO                     = 'merchant-website-info-result';
    const LEGAL_DOCUMENTS_EVENTS                    = 'api-bvs-legal-document-result-events';
    const INVALID_ADDRESS_EVENTS                    = 'invalid-address-events';
    const WEBSITE_POLICY_EVENTS                     = 'pg-website-verification-notification-events';
    const NEGATIVE_KEYWORDS_EVENTS                  = 'api-bvs-kyc-document-result-events';
    const MCC_NOTIFICATION_EVENTS                   = 'pg-mcc-notification-events';

    const API_PG_LEDGER_ACKNOWLEDGMENTS             = 'outbox_jobs_api';

    const STAGE_TEST_PAYMENT_EVENTS                 = 'stage_test_payment_events';
    const STAGE_LIVE_PAYMENT_EVENTS                 = 'stage_live_payment_events';
    const PROD_LIVE_PAYMENT_EVENTS                 = 'prod_live_payment_events';
    const PROD_TEST_PAYMENT_EVENTS                 = 'prod_test_payment_events';
    const PROD_LIVE_API_LEDGER_DUAL_WRITE_EVENTS   = 'prod_live_api_cls_events';
    const PROD_TEST_API_LEDGER_DUAL_WRITE_EVENTS   = 'prod_test_api_cls_events';

    const PROD_LIVE_API_LEDGER_DUAL_WRITE_RETRY_EVENTS   = 'prod_live_api_ledger_dual_write_retry_events';
    const PROD_TEST_API_LEDGER_DUAL_WRITE_RETRY_EVENTS   = 'prod_test_api_ledger_dual_write_retry_events';

    const STAGE_LIVE_API_LEDGER_DUAL_WRITE_EVENTS   = 'stage_live_api_cls_events';
    const STAGE_TEST_API_LEDGER_DUAL_WRITE_EVENTS   = 'stage_test_api_cls_events';

    const STAGE_LIVE_API_LEDGER_DUAL_WRITE_RETRY_EVENTS   = 'stage_live_api_ledger_dual_write_retry_events';
    const STAGE_TEST_API_LEDGER_DUAL_WRITE_RETRY_EVENTS   = 'stage_test_api_ledger_dual_write_retry_events';

    const MERCHANT_PAYMENTS_ENABLED_CALLBACK_EVENTS = 'merchant-payments-enabled-callback';
    const PGOS_STAGE_CDC_EVENTS                     = 'cdc_events_mysql_stage_pg_onboarding';
    const PGOS_PROD_CDC_EVENTS                      = 'cdc_events_mysql_prod_pg_onboarding';

    const MERCHANT_POS_ACTIVATION_STAGE             = 'merchant_pos_activation_stage';
    const MERCHANT_POS_ACTIVATION_PROD              = 'merchant_pos_activation_prod';
    const PROD_LIVE_REFUND_EVENTS                   = 'prod_live_refund_events';
    const PROD_TEST_REFUND_EVENTS                   = 'prod_test_refund_events';
    const STAGE_LIVE_REFUND_EVENTS                  = 'stage_live_refund_events';
    const STAGE_TEST_REFUND_EVENTS                  = 'stage_test_refund_events';
    const PARTNER_WEBHOOK_CALLBACK_EVENTS           = "partner_webhook_callback_events";
    const PARTNERSHIPS_OUTBOX_EVENTS                = "api_outbox_partnerships";
    const API_KAFKA_CONSUMER_BVS_VIDEO_KYC_EVENTS   = "api-bvs-video-kyc-result-events";

    const ASV_MERCHANT_UPDATE_EVENTS = 'asv-merchant-update-events';

    const ES_SYNC_EVENTS = 'es-sync';

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
    public function process(string $topic, array $payload, string $mode = null, $refundApiLedgerDualWrite = false)
    {
        $traceTopicDetails = [
            'topicName' => $topic,
            'mode' => $mode,
            'payload' => $payload,
        ];
        $this->trace->info(TraceCode::KAFKA_MESSAGE_PROCESSOR_PAYLOAD, $traceTopicDetails);

        /** @var KafkaJobs\Job $job */
        $job = $this->getJob($topic, $payload, $mode, $refundApiLedgerDualWrite);

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

    protected function getJob(string $topic, array $payload, string $mode = null, $refundApiLedgerDualWrite = false)
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
            case self::STAGE_TEST_PAYMENT_EVENTS:
            case self::STAGE_LIVE_PAYMENT_EVENTS:
            case self::PROD_LIVE_PAYMENT_EVENTS:
            case self::PROD_TEST_PAYMENT_EVENTS:
            case self::PROD_LIVE_API_LEDGER_DUAL_WRITE_EVENTS:
            case self::PROD_TEST_API_LEDGER_DUAL_WRITE_EVENTS:
            case self::STAGE_LIVE_API_LEDGER_DUAL_WRITE_EVENTS:
            case self::STAGE_TEST_API_LEDGER_DUAL_WRITE_EVENTS:
                return new KafkaJobs\PGLedgerDualWriteJob($payload, $mode);

            case self::PROD_LIVE_API_LEDGER_DUAL_WRITE_RETRY_EVENTS:
            case self::PROD_TEST_API_LEDGER_DUAL_WRITE_RETRY_EVENTS:
            case self::STAGE_LIVE_API_LEDGER_DUAL_WRITE_RETRY_EVENTS:
            case self::STAGE_TEST_API_LEDGER_DUAL_WRITE_RETRY_EVENTS:
                return new KafkaJobs\PGLedgerDualWriteRetryJob($payload, $mode);

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
            case self::MERCHANT_POS_ACTIVATION_STAGE:
            case self::MERCHANT_POS_ACTIVATION_PROD:
                return new KafkaJobs\PosMerchantActivationEventsJob($payload,$mode);
            case self::PROD_LIVE_REFUND_EVENTS:
            case self::PROD_TEST_REFUND_EVENTS:
            case self::STAGE_LIVE_REFUND_EVENTS:
            case self::STAGE_TEST_REFUND_EVENTS:
                if ($refundApiLedgerDualWrite === true)
                {
                    return new KafkaJobs\PGLedgerDualWriteJob($payload, $mode);
                }
                return new KafkaJobs\EzetapRefundEventsJob($payload,$mode);
            case self::PARTNER_WEBHOOK_CALLBACK_EVENTS:
                return new KafkaJobs\PartnerWebhookEventHandlerJob($payload, $mode);
            case self::ASV_MERCHANT_UPDATE_EVENTS:
                return new KafkaJobs\AsvMerchantUpdateJob($payload, $mode);
            case self::PARTNERSHIPS_OUTBOX_EVENTS:
                return new KafkaJobs\PartnershipsOutboxEventHandlerJob($payload, $mode);
            case self::API_KAFKA_CONSUMER_BVS_VIDEO_KYC_EVENTS:
                return new KafkaJobs\BvsVideoKYCEventsJob($payload['data'], $mode);
            case self::ES_SYNC_EVENTS:
                return new KafkaJobs\EsPaymentEntitySync($payload, $mode);

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
