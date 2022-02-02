<?php

namespace RZP\Services\Kafka\Consumers\FtsStatusUpdateConsumer;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Services\RxKafkaProducer;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\Kafka\Utils\Constants;
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

    const SOURCE_ID = 'source_id';

    public function __construct()
    {
        $consumerConfig = new Config();

        parent::__construct($consumerConfig);
    }

    protected function getRetryDelayInSeconds(): int
    {
        return self::DEFAULT_RETRY_DELAY;
    }

    public function getPayloadWithSensitiveDetailsMasked(array $payload): array
    {
        return $payload;
    }

    protected function getRetryTopicName(): string
    {
        return config('kafka_consumer.fts_status_update_retry_topic');
    }

    protected function addRetryDetailsToPayload($payload)
    {
        $retryDelay = $this->getRetryDelayInSeconds();

        $payload[Constants::RETRY_ATTEMPT_NO] = 1;

        $payload[Constants::RETRY_TIMESTAMP] = Carbon::now(Timezone::IST)->addSeconds($retryDelay)->getTimestamp();

        return $payload;
    }

    protected function getKeyForProducer()
    {
        return self::SOURCE_ID;
    }

    public function handle($topic, $payload, $mode): bool
    {
        $this->trace->info(TraceCode::KAFKA_FTS_STATUS_UPDATE_CONSUMER_INIT,
                           [
                               Constants::PAYLOAD => $this->getPayloadWithSensitiveDetailsMasked($payload),
                               Constants::MODE    => $mode
                           ]);

        $isProcessedSuccessfully = false;

        try
        {
            $response = (new FtaService)->updateFundTransferAttempt($payload);

            $this->trace->info(TraceCode::KAFKA_FTS_STATUS_UPDATE_CONSUMER_FINISHED,
                               [
                                   Constants::RESPONSE => $response,
                                   Constants::PAYLOAD  => $this->getPayloadWithSensitiveDetailsMasked($payload)
                               ]);

            $isProcessedSuccessfully = true;
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::KAFKA_CONSUMER_FOR_UPDATE_FTS_FUND_TRANSFER_FAILED,
                [
                    Constants::PAYLOAD => $this->getPayloadWithSensitiveDetailsMasked($payload)
                ]);

            $payload     = $this->addRetryDetailsToPayload($payload);
            $retryTopic  = $this->getRetryTopicName();
            $producerKey = $payload[$this->getKeyForProducer()];

            (new RxKafkaProducer($retryTopic, stringify($payload), $producerKey))->Produce();

            $isProcessedSuccessfully = true;
        }

        return $isProcessedSuccessfully;
    }
}
