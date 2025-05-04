<?php

namespace RZP\Jobs;

use RZP\Constants\Metric;
use RZP\Constants\Mode;
use RZP\Jobs\Job;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;

class PaymentFetchByIdParity extends Job
{

    const RETRY_INTERVAL    = 300;
    const MAX_RETRY_ATTEMPT = 1;
    const TOPIC = LedgerConstants::CREATE_LEDGER_JOURNAL_EVENT;

    protected $requestInput;
    protected $responseBody;
    protected $timestamp;

    public function __construct(string $mode, $requestInput, $responseBody, $timestamp)
    {
        parent::__construct($mode);
        $this->requestInput = $requestInput;
        $this->responseBody = $responseBody;
        $this->timestamp = $timestamp;
    }

    public function handle()
    {
        parent::handle();

        if ($this->mode === Mode::TEST)
        {
            return;
        }

        $producerKey = $this->timestamp . '_' . $this->requestInput["payment_id"];

        $message = [
            "data" => [
                "api"           => "payment_fetch_by_id",
                "request_input"   => $this->requestInput,
                "response"      => $this->responseBody,
                "timestamp"     => $this->timestamp
            ],
        ];


        $message[LedgerConstants::KAFKA_MESSAGE_TASK_NAME] = LedgerConstants::PAYMENT_FETCH_BY_ID_PARITY_EVENT;


        $topic = env('CREATE_LEDGER_JOURNAL_EVENT', LedgerConstants::CREATE_LEDGER_JOURNAL_EVENT);

        try
        {
            $kafkaProducer = (new KafkaProducer($topic, stringify($message), $producerKey));

            $kafkaProducer->Produce();

            $dimensions = [
                "api"   => "payment_fetch_by_id",
            ];

            $this->trace->count(Metric::API_DECOMP_PARITY, $dimensions);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::KAFKA_JOURNAL_ENTRY_PUSH_FAILED,
                [
                    "producer_key" => $producerKey,
                    "topic" => $topic,
                    "message" => $message
                ]);

            $dimensions = [
                "api"   => "payment_fetch_by_id",
            ];

            $this->trace->count(Metric::API_DECOMP_PARITY_PUSH_FAILURE, $dimensions);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::PAYMENT_FETCH_BY_ID_PARITY_ENTRY_IN_QUEUE_DELETED, [
                'request_input' => $this->requestInput,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
