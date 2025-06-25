<?php

namespace RZP\Jobs;

use RZP\Constants\Metric;
use RZP\Constants\Mode;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;

class PaymentsFetchParity extends Job
{

    const RETRY_INTERVAL    = 300;
    const MAX_RETRY_ATTEMPT = 1;
    const TOPIC = LedgerConstants::CREATE_LEDGER_JOURNAL_EVENT;

    const PAYMENTS_FETCH_MULTIPLE_PARITY_EVENT = 'payments_fetch_multiple_parity_event';

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

        //Handling for data size greater than 100
        if (isset($this->responseBody["count"]) && $this->responseBody["count"] >= 100)
        {
            $dimensions = [
                "api"   => "payments_fetch_multiple",
                "size_greater_than_hundred" => true
            ];

            $this->trace->count(Metric::PAYMENTS_FETCH_RESPONSE_SIZE, $dimensions);

            return;
        }

        $randString = $this->generateRandomString();
        $producerKey = $this->timestamp . '_' . $randString;

        $message = [
            "data" => [
                "api"           => "payment_fetch_multiple",
                "request_input" => $this->requestInput,
                "response"      => $this->responseBody,
                "timestamp"     => $this->timestamp,
            ],
        ];

        $message[LedgerConstants::KAFKA_MESSAGE_TASK_NAME] = self::PAYMENTS_FETCH_MULTIPLE_PARITY_EVENT;

        $topic = env('CREATE_LEDGER_JOURNAL_EVENT', LedgerConstants::CREATE_LEDGER_JOURNAL_EVENT);

        try
        {
            $kafkaProducer = (new KafkaProducer($topic, stringify($message), $producerKey));

            $kafkaProducer->Produce();

            $dimensions = [
                "api"   => "payments_fetch_multiple",
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
                "api"   => "payments_fetch_multiple",
            ];

            $this->trace->count(Metric::API_DECOMP_PARITY_PUSH_FAILURE, $dimensions);

            $this->checkRetry();
        }
    }

    public function generateRandomString($length = 16) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::PAYMENT_FETCH_ENTRY_IN_QUEUE_DELETED, [
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
