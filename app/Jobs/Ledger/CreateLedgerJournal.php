<?php

namespace RZP\Jobs\Ledger;

use App;
use Exception;
use RZP\Constants\Metric;
use RZP\Jobs\Job;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Services\KafkaProducer;
use RZP\Models\Ledger\Constants as LedgerConstants;


class CreateLedgerJournal extends Job
{
    const RETRY_INTERVAL    = 300;
    const MAX_RETRY_ATTEMPT = 3;
    const TOPIC = LedgerConstants::CREATE_LEDGER_JOURNAL_EVENT;

    protected $transactionMessage;

    protected $merchant;

    protected $isBulkJournalRequest;

    public function __construct(string $mode, array $transactionMessage, $isBulkJournalRequest = false)
    {
        parent::__construct($mode);

        $this->transactionMessage = $transactionMessage;

        $this->isBulkJournalRequest = $isBulkJournalRequest;
    }

    public function handle()
    {
        parent::handle();

       if($this->mode === Mode::TEST)
       {
           return;
       }

        $producerKey =  $this->transactionMessage[LedgerConstants::TRANSACTOR_ID].'_'.$this->transactionMessage[LedgerConstants::TRANSACTOR_EVENT];

        $message = [
            LedgerConstants::KAFKA_MESSAGE_DATA      => $this->transactionMessage,
        ];

        if ($this->isBulkJournalRequest === true)
        {
            $message[LedgerConstants::KAFKA_MESSAGE_TASK_NAME] = LedgerConstants::REGISTER_EVENT_FOR_MULTI_MERCHANT_LEDGER_TRANSACTION;
        }
        else
        {
            $message[LedgerConstants::KAFKA_MESSAGE_TASK_NAME] = LedgerConstants::REGISTER_EVENT_FOR_LEDGER_TRANSACTION;
        }

        $topic = env('CREATE_LEDGER_JOURNAL_EVENT', LedgerConstants::CREATE_LEDGER_JOURNAL_EVENT);

        try
        {
            $kafkaProducer = (new KafkaProducer($topic, stringify($message), $producerKey));

            $kafkaProducer->Produce();

            $this->trace->info(TraceCode::KAFKA_JOURNAL_ENTRY_PUSH_SUCCESS, [
                "producer_key" => $producerKey,
                "topic" => $topic,
                "message" => $message
            ]);
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
                'transactor_event' => $this->transactionMessage[LedgerConstants::TRANSACTOR_EVENT],
            ];
            $this->trace->count(Metric::PG_LEDGER_KAFKA_PUSH_FAILURE, $dimensions);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::KAFKA_JOURNAL_ENTRY_QUEUE_DELETE, [
                'transaction_id' => $this->transactionMessage[Transaction\Entity::ENTITY_ID],
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
