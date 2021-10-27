<?php

namespace RZP\Services;

use RdKafka\Message;
use RdKafka\Producer;

class ThirdWatchKafkaProducer
{
    private $kafkaTopic;

    private $producer;

    protected $message;

    use KafkaTrait;

    protected $producerPollTimeOutMS = 0;

    protected $producerFlushTimeOutMS = 1200;

    public function __construct($topicName, $message, $key = null)
    {
        $conf = $this->getConfig();

        $this->producer = new Producer($conf);

        $this->kafkaTopic = $this->producer->newTopic($topicName);

        $this->message = $message;

        $this->key = $key;
    }

    // Returns error code for logging
    public function Produce()
    {
        $this->kafkaTopic->produce(RD_KAFKA_PARTITION_UA, 0, $this->message, $this->key);

        $this->producer->poll($this->producerPollTimeOutMS);

        return $this->producer->flush($this->producerFlushTimeOutMS);
    }
}
