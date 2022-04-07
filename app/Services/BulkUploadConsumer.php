<?php


namespace RZP\Services;

use App;
use Exception;
use RdKafka\Conf;
use RdKafka\Message;
use RdKafka\KafkaConsumer;
use RdKafka\TopicPartition;

use RZP\Jobs\Kafka\Job;
use RZP\Trace\TraceCode;

class BulkUploadConsumer extends Job
{

    public function handle()
    {
        parent::handle();
        $tracePayload = [
            'job_attempts' => $this->attempts(),
            'mode' => $this->mode
        ];

        $this->trace->info(TraceCode::RAW_ADDRESS_KAFKA_CONSUME_REQUEST,$tracePayload);
        (new BulkUploadClient())->pushKafkaMessageToDB($this->getPayload());
    }
}