<?php

namespace RZP\Jobs;

use App;

use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use Razorpay\Trace\Logger;
use RZP\Models\Transaction;
use RZP\Models\BankTransfer;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Reversal\Core as ReversalCore;
use RZP\Models\FundAccount\Validation\Core as FavCore;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\CreditTransfer\Core as CreditTransferCore;

class Transactions extends Job
{
    const MAX_RETRY_ATTEMPTS = 4;
    // the delay is in seconds
    // used in an exponential backoff manner
    const MIN_RETRY_DELAY    = 5;

    protected $trace;

    /**
     * used to map the SQS queue worker to this code
     *
     * @var string
     */
    protected $queueConfigKey = 'ledger_transactions';

    protected $entityId;

    protected $entityName;

    protected $ledgerResponse;

    protected $feeSplit;

    const LEDGER_TRANSACTIONS_MUTEX_RESOURCE = 'LEDGER_TRANSACTIONS_%s_%s';

    const MUTEX_LOCK_TIMEOUT = 60;

    public function __construct(string $mode, string $entityId, string $entityName, array $ledgerResponse, PublicCollection $feeSplit = null)
    {
        parent::__construct($mode);

        $this->entityId       = $entityId;
        $this->entityName     = $entityName;
        $this->ledgerResponse = $ledgerResponse['body'];
        $this->feeSplit       = $feeSplit;
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $traceData = [
                'entityId'          => $this->entityId,
                'entityName'        => $this->entityName,
                'ledgerResponse'    => $this->ledgerResponse,
                'feeSplit'          => $this->feeSplit
            ];

            $this->trace->info(
                TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_INIT,
                $traceData);

            $resource = sprintf(self::LEDGER_TRANSACTIONS_MUTEX_RESOURCE, $this->entityName, $this->entityId);

            // check if dual write already happened
            $apiTransaction = $this->repoManager->transaction->find($this->ledgerResponse['id']);
            if ($apiTransaction != null)
            {
                $this->trace->info(TraceCode::LEDGER_API_TXN_DUAL_WRITE_DUPLICATE_REQUEST, [
                    'id' => $this->ledgerResponse['id']
                ]);
                $this->delete();
                return;
            }

            // logic
            switch ($this->entityName)
            {
                case Constants\Entity::BANK_TRANSFER :
                    $response = $this->processBankTransferJob($resource, $this->ledgerResponse);
                    break;

                case Constants\Entity::ADJUSTMENT :
                    $response = $this->processAdjustmentJob($resource, $this->ledgerResponse);
                    break;

                case Entity::PAYOUT :
                    $response = (new PayoutCore)
                        ->createTransactionInLedgerReverseShadowFlow($this->entityId, $this->ledgerResponse);

                    break;

                case Entity::CREDIT_TRANSFER :
                    $response = (new CreditTransferCore())
                        ->createTransactionInLedgerReverseShadowFlow($this->entityId, $this->ledgerResponse);

                    break;

                case Entity::FUND_ACCOUNT_VALIDATION :
                    $response = (new FavCore)
                        ->createTransactionInLedgerReverseShadowFlow($this->entityId, $this->ledgerResponse, $this->feeSplit);

                    break;

                case Entity::REVERSAL :
                    $response = (new ReversalCore)
                        ->createTransactionInLedgerReverseShadowFlow($this->entityId, $this->ledgerResponse);

                    break;

                default:
                    $response = [];

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
                    'response'    => $response,
                ]
            );

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
        list($entityId, $txnId) = $this->mutex->acquireAndRelease(
            $resource,
            function () use ($ledgerResponseBody)
            {
                $bankTransfer = $this->repoManager->bank_transfer->find($this->entityId);
                $journalId = $ledgerResponseBody["id"];
                $balance = Transaction\Processor\Ledger\FundLoading::getMerchantBalanceFromLedgerResponse($ledgerResponseBody);

                $tempBankTransfer = $bankTransfer;
                list($bankTransfer, $txn) = $this->repoManager->transaction(function() use ($tempBankTransfer, $journalId, $balance)
                {
                    $bankTransfer = clone $tempBankTransfer;

                    list ($txn, $feeSplit) = (new Transaction\Processor\BankTransfer($tempBankTransfer))->createTransactionWithIdAndLedgerBalance($journalId, intval($balance));
                    $this->repoManager->saveOrFail($txn);

                    $bankTransfer->setTransactionId($txn->getId());
                    $this->repoManager->saveOrFail($bankTransfer);

                    return [$bankTransfer, $txn];
                });

                // dispatch event for txn created
                (new BankTransfer\Processor())->dispatchEventForTransactionCreated($bankTransfer, $txn);
                return [
                    $bankTransfer->getPublicId(),
                    $txn->getPublicId(),
                ];
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        return [
            'entity_id' => $entityId,
            'txn_id'    => $txnId
        ];
    }

    protected function processAdjustmentJob($resource, $ledgerResponseBody)
    {
        list($entityId, $txnId) = $this->mutex->acquireAndRelease(
            $resource,
            function () use ($ledgerResponseBody)
            {
                $adjustment = $this->repoManager->adjustment->find($this->entityId);
                $journalId = $ledgerResponseBody["id"];
                $balance = Transaction\Processor\Ledger\Adjustment::getMerchantBalanceFromLedgerResponse($ledgerResponseBody);

                $tempAdjustment = $adjustment;
                list($adjustment, $txn) = $this->repoManager->transaction(function() use ($tempAdjustment, $journalId, $balance)
                {
                    $adjustment = clone $tempAdjustment;

                    list ($txn, $feeSplit) = (new Transaction\Processor\Adjustment($adjustment))->createTransactionWithIdAndLedgerBalance($journalId, intval($balance));
                    $this->repoManager->saveOrFail($txn);

                    // need to update txn id in adj table
                    $this->repoManager->saveOrFail($adjustment);

                    return [$adjustment, $txn];
                });

                // dispatch event for txn created
                (new Transaction\Core)->dispatchEventForTransactionCreated($txn);
                return [
                    $adjustment->getPublicId(),
                    $txn->getPublicId(),
                ];
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
        return [
            'entity_id' => $entityId,
            'txn_id'    => $txnId
        ];
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
