<?php

namespace RZP\Services\Kafka;

use App;
use Exception;
use RdKafka\Conf;
use RdKafka\Message;
use RdKafka\KafkaConsumer;
use RdKafka\TopicPartition;

use RZP\Jobs\Job;
use RZP\Services\Metric;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\Kafka\Utils\Constants;
use RZP\Services\Kafka\Utils\Consumer as ConsumerUtils;
use RZP\Services\Kafka\Consumers\Base\Consumer as BaseConsumer;

class Consumer extends Job
{
    protected $handler;
    protected $topics;
    protected $groupId;
    protected $consumer;
    protected $mode;
    protected $consumerUtils;

    public function __construct(BaseConsumer $handler, $topics, $groupId, $mode)
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];
        $this->consumerUtils = new ConsumerUtils();

        $this->handler = $handler;
        $this->topics  = $topics;
        $this->groupId = $groupId;
        $this->mode    = $mode;

        parent::__construct($mode);

        parent::handle();
    }

    public function consume(): void
    {
        $this->consumer = new KafkaConsumer($this->setConf(Constants::RX_CLUSTER));

        $this->consumer->subscribe($this->topics);

        while (true)
        {
            try
            {
                $startTime = millitime();

                $message = $this->consumer->consume($this->consumerUtils->getConsumerPollTimeoutMs());

                $timeTaken = millitime() - $startTime;

                switch ($message->err)
                {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:

                        $this->trace->info(TraceCode::KAFKA_CONSUMER_CONSUMPTION_DETAIL,
                                           [
                                               Constants::CONTENT => $message,
                                               Constants::CASE    => 'RD_KAFKA_RESP_ERR_NO_ERROR',
                                               Constants::INFO    => "successfully consumed message"
                                           ]);

                        $this->trace->histogram(Metric::KAFKA_CONSUMER_CONSUMPTION_DETAIL_LATENCY, $timeTaken,
                                                [
                                                    Constants::CASE    => 'RD_KAFKA_RESP_ERR_NO_ERROR',
                                                    Constants::INFO    => "successfully consumed message"
                                                ]);

                        $isProcessedSuccessfully = $this->processMessage($message);

                        $this->handlePostProcessing($isProcessedSuccessfully, $message);

                        break;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:

                        $this->trace->info(TraceCode::KAFKA_CONSUMER_CONSUMPTION_DETAIL,
                                           [
                                               Constants::CONTENT => $message,
                                               Constants::CASE    => 'RD_KAFKA_RESP_ERR__PARTITION_EOF',
                                               Constants::INFO    => "No more messages; will wait for more\n"
                                           ]);

                        break;
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        $this->trace->info(TraceCode::KAFKA_CONSUMER_CONSUMPTION_DETAIL,
                                           [
                                               Constants::CONTENT => $message,
                                               Constants::CASE    => 'RD_KAFKA_RESP_ERR__TIMED_OUT',
                                               Constants::INFO    => "time out"
                                           ]);
                        break;
                    default:
                        $this->trace->info(TraceCode::KAFKA_CONSUMER_CONSUMPTION_DETAIL,
                                           [
                                               Constants::CONTENT => $message,
                                               Constants::CASE    => 'default',
                                               Constants::INFO    => "default case"
                                           ]);
                        throw new Exception($message->errstr(), $message->err);
                }
            }
            catch (Exception $e)
            {

                $this->trace->traceException($e,
                                             Trace::ERROR,
                                             TraceCode::KAFKA_CONSUMER_FAILED_TO_CONSUME_FROM_TOPICS,
                                             [
                                                 Constants::INFO      => 'failed to consume topics from kafka',
                                                 Constants::CONTENT   => $message,
                                                 Constants::EXCEPTION => $e
                                             ]
                );
            }
        }
    }

    private function setConf(string $cluster = Constants::SHARED_CLUSTER): Conf
    {
        $this->conf = $this->consumerUtils->getNewConf($cluster);

        foreach ($this->handler->getConfig() as $key => $value)
        {
            $this->conf->set($key, $value);
        }

        if ($this->groupId !== null) {
            $this->conf->set('group.id', $this->groupId);
        }

        return $this->conf;
    }

    protected function processMessage(Message $kafkaMessage): bool
    {
        $payload = $this->consumerUtils->decodeKafkaMessage($kafkaMessage);

        // if any invalid payload, acknowledge queue.
        if ($payload === false)
        {
            $this->trace->info(TraceCode::KAFKA_MESSAGE_INVALID_PAYLOAD, [
                Constants::PAYLOAD => $kafkaMessage->payload,
            ]);

            return true;
        }

        $maskedPayload = $this->handler->getPayloadWithSensitiveDetailsMasked($payload);

        $this->trace->info(TraceCode::KAFKA_MESSAGE_DETAILS,
                           [
                               'topic'          => $kafkaMessage->topic_name,
                               'partition'      => $kafkaMessage->partition,
                               'offset'         => $kafkaMessage->offset,
                               'masked_payload' => $maskedPayload,
                               'handler'        => get_class($this->handler),
                               'group_id'       => $this->groupId
                           ]);

        $isProcessed = $this->handler->handle($kafkaMessage->topic_name, $payload, $this->mode);

        $this->trace->info(TraceCode::KAFKA_CONSUMER_HANDLER_RESPONSE, [
            'handler' => get_class($this->handler),
            'success' => $isProcessed,
        ]);

        return $isProcessed;

    }

    protected function handlePostProcessing(bool $isProcessed, Message $kafkaMessage): void
    {
        //
        // Commit offsets synchronously if processing is complete
        //
        $info = '';

        if ($isProcessed === true)
        {
            $info = "Message Successfully Processed, Committing Offset - ";

            $this->consumer->commit($kafkaMessage);
        }
        else
        {
            $info = 'Message processing failed, retrying' . ' Offset';

            $this->consumer->unsubscribe();

            $this->consumer->subscribe($this->topics);
        }

        $this->trace->info(TraceCode::KAFKA_CONSUMER_POST_PROCESSING_DETAILS,
                           [
                               'topic'     => $kafkaMessage->topic_name,
                               'partition' => $kafkaMessage->partition,
                               'offset'    => $kafkaMessage->offset,
                               'info'      => $info,
                               'handler'   => get_class($this->handler),
                               'group_id'  => $this->groupId
                           ]);
    }
}
