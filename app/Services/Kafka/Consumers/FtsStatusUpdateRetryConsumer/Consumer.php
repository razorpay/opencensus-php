<?php

namespace RZP\Services\Kafka\Consumers\FtsStatusUpdateRetryConsumer;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Services\KafkaProducer;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\Kafka\Utils\Constants;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Attempt\Service as FtaService;
use RZP\Services\Kafka\Consumers\Base\Consumer as BaseConsumer;

class Consumer extends BaseConsumer
{
    // group.id is responsible for setting your consumer group ID and it should be unique
    // (and should not change). Kafka uses it to recognize applications and store offsets for them.

    // Therefore add group_id in comments. Adding that here in comments
    // will help us to remember what was group id for this consumer
    // group_id : sample_consumer_group_id

    const DEFAULT_RETRY_DELAY = 60; // it is in seconds

    const MAX_RETRIES = 2;

    const SOURCE_ID = 'source_id';

    public function __construct()
    {
        $consumerConfig = new Config();

        parent::__construct($consumerConfig);
    }

    public function getPayloadWithSensitiveDetailsMasked(array $payload): array
    {
        return $payload;
    }

    protected function checkAndApplyIfSleepIsNeededBeforeProcessing(array &$payload)
    {
        if(array_has($payload, Constants::RETRY_TIMESTAMP) === true)
        {
            $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

            $retryAt = array_pull($payload, Constants::RETRY_TIMESTAMP);

            if ($currentTimestamp < $retryAt)
            {
                sleep(min($retryAt - $currentTimestamp, 180));
            }
        }
    }

    protected function getRetryTopicName(): string
    {
        $appMode    = env('APP_MODE', 'prod');

        return $appMode . '-' . 'fts-status-update-retry-events';
    }

    protected function getRetryDelayInSeconds(): int
    {
        return self::DEFAULT_RETRY_DELAY;
    }


    protected function addRetryDetailsToPayload($payload, $retryAttemptNo)
    {
        $retryDelay = $this->getRetryDelayInSeconds();

        $payload[Constants::RETRY_ATTEMPT_NO] = $retryAttemptNo;
        $payload[Constants::RETRY_TIMESTAMP]  = Carbon::now(Timezone::IST)->addSeconds($retryDelay)->getTimestamp();

        return $payload;
    }

    protected function sendSlackAlert($payload)
    {
        $operation = 'kafka retry consumer failed after max retries.';

        $this->trace->info(TraceCode::KAFKA_FTS_STATUS_UPDATE_RETRY_CONSUMER_SLACK_NOTIFICATION_SENT, [
            "operation" => $operation,
            "payload"   => $this->getPayloadWithSensitiveDetailsMasked($payload),
        ]);

        (new SlackNotification)->send($operation, $payload, null, 1, 'rx_ca_rbl_alerts');
    }

    protected function getKeyForProducer()
    {
        return self::SOURCE_ID;
    }

    public function handle($topic, $payload, $mode): bool
    {
        $this->trace->info(TraceCode::KAFKA_FTS_STATUS_UPDATE_RETRY_CONSUMER_INIT,
                           [
                               Constants::PAYLOAD => $this->getPayloadWithSensitiveDetailsMasked($payload),
                               Constants::MODE    => $mode
                           ]);

        $this->checkAndApplyIfSleepIsNeededBeforeProcessing($payload);

        $retryAttemptNo = array_pull($payload, Constants::RETRY_ATTEMPT_NO, 1);

        $isProcessedSuccessfully = false;

        try
        {
            $response = (new FtaService)->updateFundTransferAttempt($payload);

            $isProcessedSuccessfully = true;

            $this->trace->info(TraceCode::KAFKA_FTS_STATUS_UPDATE_RETRY_CONSUMER_FINISHED,
                               [
                                   Constants::RESPONSE => $response,
                                   Constants::PAYLOAD  => $this->getPayloadWithSensitiveDetailsMasked($payload)
                               ]);
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::KAFKA_RETRY_CONSUMER_FOR_UPDATE_FTS_FUND_TRANSFER_FAILED,
                [
                    Constants::RETRY_ATTEMPT_NO => $retryAttemptNo,
                    Constants::PAYLOAD          => $this->getPayloadWithSensitiveDetailsMasked($payload)
                ]);

            $retryAttemptNo++;

            if ($retryAttemptNo <= self::MAX_RETRIES)
            {
                $payload     = $this->addRetryDetailsToPayload($payload, $retryAttemptNo);
                $retryTopic  = $this->getRetryTopicName();
                $producerKey = $payload[$this->getKeyForProducer()];

                (new KafkaProducer($retryTopic, stringify($payload), $producerKey))->Produce();

                $isProcessedSuccessfully = true;
            }
            else
            {
                $this->sendSlackAlert($payload);

                $isProcessedSuccessfully = true;
            }
        }

        return $isProcessedSuccessfully;
    }
}
