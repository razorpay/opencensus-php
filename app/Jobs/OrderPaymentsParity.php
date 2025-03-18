<?php

namespace RZP\Jobs;

use RZP\Constants\Metric;
use RZP\Constants\Mode;
use RZP\Jobs\Job;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;

class OrderPaymentsParity extends Job
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

        //Handling for data size greater than 100
        if (isset($this->responseBody["count"]) && $this->responseBody["count"] >= 100)
        {
            $dimensions = [
                "api"   => "order_payments",
                "size_greater_than_hundred" => true
            ];

            $this->trace->count(Metric::PAYMENTS_FETCH_RESPONSE_SIZE, $dimensions);

            return;
        }

        //The TPS for this route is 300, we only want 40-50 requests to be produced
        $rand = rand(1, 18000);
        if ($rand > 500)
        {
            return;
        }

        $producerKey = $this->timestamp . '_' . $this->requestInput["order_id"];

        $message = [
            "data" => [
                "api"           => "order_payments",
                "request_input" => $this->requestInput,
                "response"      => $this->responseBody,
                "timestamp"     => $this->timestamp,
            ],
        ];


        $message[LedgerConstants::KAFKA_MESSAGE_TASK_NAME] = LedgerConstants::ORDERS_PAYMENT_PARITY_EVENT;


        $topic = env('CREATE_LEDGER_JOURNAL_EVENT', LedgerConstants::CREATE_LEDGER_JOURNAL_EVENT);

        try
        {
            $kafkaProducer = (new KafkaProducer($topic, stringify($message), $producerKey));

            $kafkaProducer->Produce();

            $dimensions = [
                "api"   => "order_payments",
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
                "api"   => "order_payments",
            ];

            $this->trace->count(Metric::API_DECOMP_PARITY_PUSH_FAILURE, $dimensions);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::ORDER_PAYMENTS_ENTRY_IN_QUEUE_DELETED, [
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
