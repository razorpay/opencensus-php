<?php

namespace RZP\Jobs;

use App;

use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Transaction;
use RZP\Models\BankTransfer;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestValidationFailureException;

class Transactions extends Job
{
    const MAX_RETRY_ATTEMPTS = 4;
    // the delay is in seconds
    // used in an exponential backoff manner
    const MIN_RETRY_DELAY    = 5;

    protected $trace;

    /**
     * @var string
     */

    protected $queueConfigKey = 'ledger_transactions';

    protected $entityId;

    protected $entityName;

    protected $ledgerResponse;

    protected $feeSplit;

    const LEDGER_TRANSACTIONS_MUTEX_RESOURCE = 'LEDGER_TRANSACTIONS_%s_%s';

    const MUTEX_LOCK_TIMEOUT = 5400;

    public function __construct(string $mode, string $entityId, string $entityName, array $ledgerResponse, PublicCollection $feeSplit = null)
    {
        parent::__construct($mode);

        $this->entityId = $entityId;
        $this->entityName = $entityName;
        $this->ledgerResponse = $ledgerResponse;
        $this->feeSplit       = $feeSplit;
    }

    public function handle()
    {
        parent::handle();

        $traceData = [
            'entityId'          => $this->entityId,
            'entityName'        => $this->entityName,
            'ledgerResponse'    => $this->ledgerResponse,
        ];

        $this->trace->info(
            TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_INIT,
            $traceData);

        try
        {
            $txnId = null;
            $ledgerResponseBody = $this->ledgerResponse['body'];
            $resource = sprintf(self::LEDGER_TRANSACTIONS_MUTEX_RESOURCE, $this->entityName, $this->entityId);

            switch ($this->entityName)
            {
                case Constants\Entity::BANK_TRANSFER :
                    $txnId = $this->processBankTransferJob($resource, $ledgerResponseBody);
                    break;

                case Constants\Entity::ADJUSTMENT :
                    $txnId = $this->processAdjustmentJob($resource, $ledgerResponseBody);
                    break;

                default:
                    $this->trace->info(
                        TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_ENTITY_NAME_NOT_SUPPORTED,
                        $traceData
                    );
            }

            $this->trace->info(
                TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_SUCCESSFUL,
                [
                    'entity_id'   => $this->entityId,
                    'entity_name' => $this->entityName,
                    'response'    => $txnId,
                ]);

            $this->delete();
        }
        catch (BadRequestValidationFailureException $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::LEDGER_TRANSACTIONS_QUEUE_UNEXPECTED_RESPONSE);

            if ($e->getCode() === ErrorCode::BAD_REQUEST_LEDGER_JOURNAL_ENTRY_BALANCE_GET_ERROR)
            {
                // We won't retry in this case.
                // TODO: Set alerts for this exception
                $this->delete();

                return;
            }

            $this->checkRetry();
        }
        catch (LogicException $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_EXCEPTION);

            $this->delete();

            return;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_EXCEPTION);

            $this->checkRetry();
        }
    }

    protected function processBankTransferJob($resource, $ledgerResponseBody)
    {
        $txnId = $this->mutex->acquireAndRelease(
            $resource,
            function () use ($ledgerResponseBody)
            {
                $bankTransfer = $this->repoManager->bank_transfer->find($this->entityId);
                $journalId = $ledgerResponseBody["id"];
                $balance = (new Transaction\Processor\Ledger\FundLoading)->getMerchantBalanceFromLedger($ledgerResponseBody);

                $tempBankTransfer = $bankTransfer;
                list($bankTransfer, $txn) = $this->repoManager->transaction(function() use ($tempBankTransfer, $journalId, $balance)
                {
                    $bankTransfer = clone $tempBankTransfer;

                    list ($txn, $feeSplit) = (new Transaction\Processor\BankTransfer($tempBankTransfer))->createTransactionWithIdAndUpdateBalance($journalId, intval($balance));
                    $this->repoManager->saveOrFail($txn);
                    return [$bankTransfer, $txn];
                });

                // dispatch event for txn created
                (new BankTransfer\Processor())->dispatchEventForTransactionCreated($bankTransfer, $txn);
                return $txn->getId();
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        return $txnId;
    }

    protected function processAdjustmentJob($resource, $ledgerResponseBody)
    {
        $txnId = $this->mutex->acquireAndRelease(
            $resource,
            function () use ($ledgerResponseBody)
            {
                $adjustment = $this->repoManager->adjustment->find($this->entityId);
                $journalId = $ledgerResponseBody["id"];
                $balance = (new Transaction\Processor\Ledger\Adjustment)->getMerchantBalanceFromLedger($ledgerResponseBody);

                $tempAdjustment = $adjustment;
                list($adjustment, $txn) = $this->repoManager->transaction(function() use ($tempAdjustment, $journalId, $balance)
                {
                    $adjustment = clone $tempAdjustment;

                    list ($txn, $feeSplit) = (new Transaction\Processor\Adjustment($adjustment))->createTransactionWithIdAndUpdateBalance($journalId, intval($balance));
                    $this->repoManager->saveOrFail($txn);

                    // need to update txn id in adj table
                    $this->repoManager->saveOrFail($adjustment);

                    return [$adjustment, $txn];
                });

                // dispatch event for txn created
                (new Transaction\Core)->dispatchEventForTransactionCreated($txn);
                return $txn->getId();
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
        return $txnId;
    }

    protected function checkRetry()
    {
        $noOfAttempts = $this->attempts();

        if ($noOfAttempts < self::MAX_RETRY_ATTEMPTS)
        {
            $this->release(self::MIN_RETRY_DELAY * pow(2, ($noOfAttempts - 1)));

            $this->trace->info(
                TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_RELEASED,
                [
                    'entity_id'      => $this->entityId,
                    'entity_name'    => $this->entityName,
                    'no_of_attempts' => $noOfAttempts,
                ]
            );
        }
        else
        {
            // TODO: Add Sumo alert.
            $this->trace->error(
                TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_DELETED,
                [
                    'entity_id'      => $this->entityId,
                    'entity_name'    => $this->entityName,
                    'no_of_attempts' => $noOfAttempts,
                ]
            );

            $this->delete();
        }
    }
}
