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

class InvalidAddressConsumer extends Job
{

    public function handle()
    {
        parent::handle();
        $tracePayload = [
            'job_attempts' => $this->attempts(),
            'mode' => $this->mode
        ];

        $bulkUploadClient = new BulkUploadClient();

        $this->trace->info(TraceCode::INVALID_ADDRESS_CONSUMER_MESSAGE_REQUEST, $tracePayload);
        $start = $bulkUploadClient->getCurrentTimeInMillis();
        $bulkUploadClient->findAndDeleteInvalidAddress($this->getPayload());
        $timeTaken = $bulkUploadClient->getCurrentTimeInMillis() - $start;
        $this->trace->info(TraceCode::INVALID_ADDRESS_CONSUMER_PROCESSING_TIME, ["InvalidAddressConsumer:33" => $timeTaken]);
    }
}
