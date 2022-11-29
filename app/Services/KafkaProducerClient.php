<?php

namespace RZP\Services;

use App;
use RdKafka\Message;
use RdKafka\Producer;
use RZP\Trace\TraceCode;

class KafkaProducerClient
{
    private $kafkaTopic;

    private $producer;

    protected $message;

    use KafkaTrait;

    protected $producerPollTimeOutMS = 0;

    protected $producerFlushTimeOutMS = 10000;

    public function produce($topicName, $message, $key = null)
    {
        $conf = $this->getConfig();

        $this->producer = new Producer($conf);

        $this->kafkaTopic = $this->producer->newTopic($topicName);

        $this->message = $message;

        $this->key = $key;

        $this->producerPollTimeOutMS = env('PRODUCER_POLL_TIMEOUT_MS', $this->producerPollTimeOutMS);

        $this->producerFlushTimeOutMS = env('PRODUCER_FLUSH_TIMEOUT_MS', $this->producerFlushTimeOutMS);

        $this->kafkaTopic->produce(RD_KAFKA_PARTITION_UA, 0, $this->message, $this->key);

        $this->producer->poll($this->producerPollTimeOutMS);

        $app = App::getFacadeRoot();

        $startTime = microtime(true);

        $result = $this->producer->flush($this->producerFlushTimeOutMS);

        $endTime = get_diff_in_millisecond($startTime);

        $app['trace']->info(TraceCode::KAFKA_PRODUCER_FLUSH_TIME,
                            ['kafka_flush_time' => $endTime]
        );

        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result)
        {
            throw new \RuntimeException('Was unable to flush, messages might be lost!');
        }
    }
}
