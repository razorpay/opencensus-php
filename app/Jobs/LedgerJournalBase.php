<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Exception\LogicException;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\Reversal\Core as ReversalCore;
use RZP\Exception\BadRequestValidationFailureException;

class LedgerJournalBase extends Job
{
    const MAX_RETRY_ATTEMPTS = 10;
    // the delay is in seconds
    // used in an exponential backoff manner
    const MIN_RETRY_DELAY    = 5;

    protected $trace;

    protected $mode;

    protected $ledgerResponse;
    
    // ledger transactor id prefix
    const PAYOUT_PREFIX          = "pout_";
    const REVERSAL_PREFIX        = "rvrsl_";
    const TRANSACTOR_ID          = "transactor_id";
    const TRANSACTOR_EVENT       = "transactor_event";

    public function __construct(string $mode, array $payload)
    {
        parent::__construct($mode);
        $this->mode = $mode;
        $this->ledgerResponse = $payload;
    }

    public function handle()
    {
        $entityId = null;
        $entityName = null;
        $response = null;

        try
        {
            parent::handle();
            $this->trace->info(TraceCode::LEDGER_JOURNAL_QUEUE_JOB_INIT, $this->ledgerResponse);

            // dual writes API Transaction
            $transactorId = $this->ledgerResponse[self::TRANSACTOR_ID];
            $transactorEvent = $this->ledgerResponse[self::TRANSACTOR_EVENT];

            // get the entity id and name
            if (strpos($transactorId, self::PAYOUT_PREFIX) !== false) {
                $entityId = str_replace(self::PAYOUT_PREFIX, '', $transactorId);
                $entityName = Entity::PAYOUT;
            } else if (strpos($transactorId, self::REVERSAL_PREFIX) !== false) {
                $entityId = str_replace(self::REVERSAL_PREFIX, '', $transactorId);
                $entityName = Entity::REVERSAL;
            }

            $traceData = [
                'entityId'          => $entityId,
                'entityName'        => $entityName,
                'ledgerResponse'    => $this->ledgerResponse,
            ];

            $this->trace->info(
                TraceCode::LEDGER_JOURNAL_QUEUE_JOB_DECODED,
                $traceData);

            // perform operations based on entity name
            switch ($entityName)
            {
                case Entity::PAYOUT :
                    if ($transactorEvent === Ledger\Payout::PAYOUT_INITIATED)
                    {
                        $response = (new PayoutCore)
                            ->createTransactionInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);
                    }
                    break;


                case Entity::REVERSAL :
                    $response = (new ReversalCore)
                        ->createTransactionInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);

                    break;

                default:
                    $response = [];

                    $this->trace->info(
                        TraceCode::LEDGER_JOURNAL_QUEUE_JOB_ENTITY_NAME_NOT_SUPPORTED,
                        $traceData
                    );
            }

            $this->trace->info(
                TraceCode::LEDGER_JOURNAL_QUEUE_JOB_SUCCESSFUL,
                [
                    'entity_id'   => $entityId,
                    'entity_name' => $entityName,
                    'response'    => $response,
                ]
            );

            $this->delete();
        }
        catch (BadRequestValidationFailureException $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::LEDGER_JOURNAL_QUEUE_UNEXPECTED_RESPONSE);

            if ($e->getCode() === ErrorCode::BAD_REQUEST_LEDGER_JOURNAL_ENTRY_BALANCE_GET_ERROR)
            {
                $this->delete();
                return;
            }

            $this->checkRetry($entityId, $entityName);
        }
        catch (LogicException $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::LEDGER_JOURNAL_QUEUE_JOB_EXCEPTION);

            $this->delete();

            return;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::LEDGER_JOURNAL_QUEUE_JOB_EXCEPTION);

            $this->checkRetry($entityId, $entityName);
        }
    }


    protected function checkRetry($entityId, $entityName)
    {
        $noOfAttempts = $this->attempts();

        if ($noOfAttempts < self::MAX_RETRY_ATTEMPTS)
        {
            $this->release(self::MIN_RETRY_DELAY * pow(2, ($noOfAttempts - 1)));

            $this->trace->info(
                TraceCode::LEDGER_JOURNAL_QUEUE_JOB_RELEASED,
                [
                    'entity_id'      => $entityId,
                    'entity_name'    => $entityName,
                    'no_of_attempts' => $noOfAttempts,
                ]
            );
        }
        else
        {
            $this->trace->error(
                TraceCode::LEDGER_JOURNAL_QUEUE_JOB_DELETED,
                [
                    'entity_id'      => $entityId,
                    'entity_name'    => $entityName,
                    'no_of_attempts' => $noOfAttempts,
                ]
            );

            $this->delete();
        }
    }
}
