<?php

namespace RZP\Services;

use RdKafka\Message;
use RdKafka\Producer;

class KafkaProducer
{
    private $kafkaTopic;

    private $producer;

    protected $message;

    use KafkaTrait;

    protected $producerPollTimeOutMS = 0;

    protected $producerFlushTimeOutMS = 120000;

    public function __construct($topicName, $message)
    {
        $conf = $this->getConfig();

        $this->producer = new Producer($conf);

        $this->kafkaTopic = $this->producer->newTopic($topicName);

        $this->message = $message;

        $this->producerPollTimeOutMS = env('PRODUCER_POLL_TIMEOUT_MS', $this->producerPollTimeOutMS);

        $this->producerFlushTimeOutMS = env('PRODUCER_FLUSH_TIMEOUT_MS', $this->producerFlushTimeOutMS);
    }

    public function Produce()
    {
        $this->kafkaTopic->produce(RD_KAFKA_PARTITION_UA, 0, $this->message);

        $this->producer->poll($this->producerPollTimeOutMS);

        $result = $this->producer->flush($this->producerFlushTimeOutMS);

        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result)
        {
            throw new \RuntimeException('Was unable to flush, messages might be lost!');
        }
    }
}
