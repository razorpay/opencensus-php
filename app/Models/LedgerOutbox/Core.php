<?php

namespace RZP\Models\LedgerOutbox;

use App;
use Exception;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as E;
use RZP\Constants\Metric;
use RZP\Diag\EventCode;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Reversal;
use RZP\Models\Transfer;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant;
use RZP\Trace\Tracer;
use RZP\Error\PublicErrorDescription;
use RZP\Services\Ledger as LedgerService;
use RZP\Exception\BadRequestException;
use RZP\Models\Ledger\ReverseShadow;
use RZP\Models\Adjustment\Status;
use RZP\Models\Merchant\Balance\Type;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Models\Settlement\Ondemand\Repository;
use RZP\Models\Settlement\Ondemand\Service as Service;
use RZP\Models\Transfer\OrderTransfer;
use RZP\Models\Transfer\PaymentTransfer;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Models\Ledger\ReverseShadow\Constants as LedgerReverseShadowConstants;
use RZP\Models\Payment\Processor\Capture as CaptureTrait;
use RZP\Models\Settlement\Ondemand\Status as OndemandStatus;
use \RZP\Models\Settlement\Ondemand\Core as OndemandCore;

class Core extends Base\Core
{
    const PAYMENT_TRANSACTION_CREATION_MUTEX_TTL = 60;
    const PAYMENT_TRANSACTION_CREATION_MUTEX_RETRIES = 40;
    const PAYMENT_TRANSACTION_CREATION_MUTEX_MIN_RETRY_DELAY = 100;
    const PAYMENT_TRANSACTION_CREATION_MUTEX_MAX_RETRY_DELAY = 200;

    use ReverseShadowTrait;
    use CaptureTrait;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function processLedgerAcknowledgement(array $outboxPayload)
    {
        $this->trace->info(TraceCode::PG_LEDGER_ACK_WORKER_REQUEST_RECEIVED);

        $outboxPayload= $outboxPayload['after'];

        $serialisedPayload= $outboxPayload['payload_serialized'];

        $payload = base64_decode($serialisedPayload);

        $payload = json_decode($payload, true);

        $this->trace->info(TraceCode::PG_LEDGER_ACK_WORKER_PAYLOAD_DECODED, $payload);

        $request = $payload[Constants::REQUEST];

        if($request === null)
        {
            $request = [];
        }

        $transactorId = $request[LedgerConstants::TRANSACTOR_ID] ?? "";

        $transactorEvent = $request[LedgerConstants::TRANSACTOR_EVENT] ?? "";

        // Metric calculates latency for Outbox entry creation at Ledger Worker -> Ack Recieved on pg worker
        $ledgerCreatedAt = (int)($outboxPayload[Entity::CREATED_AT]*1000);

        $durationFromLedger = millitime() - $ledgerCreatedAt;

        $this->trace->histogram(
            Metric::PG_LEDGER_KAFKA_ACKNOWLEDGMENT_RECEIVED_FROM_LEDGER,
            $durationFromLedger,
        );

        $isBulkJournal = false;

        $response = $payload[Constants::RESPONSE];

        $errorResponse = $payload[Constants::ERROR_RESPONSE];

        $accountAlreadyExistsForCapitalInNewLedger = false;

        if((isset($payload[Constants::ERROR_RESPONSE]) === true) and
            ($errorResponse !== null) and
            ($errorResponse[Constants::MSG] !== ""))
        {
            $this->trace->info(TraceCode::PG_LEDGER_ACK_WORKER_ERROR_RECEIVED,
                [
                    LedgerConstants::TRANSACTOR_EVENT       => $transactorEvent,
                    LedgerConstants::TRANSACTOR_ID          => $transactorId,
                    Constants::SOURCE                       => Constants::ACK_WORKER
                ]
            );


            $journalData = $this->handleLedgerJournalCreateWorkerFailures($transactorId, $transactorEvent, $errorResponse);

            if ($journalData === null)
            {
                return;
            }
            else
            {
                $payload[Constants::RESPONSE] = $journalData;

                $response =  $payload[Constants::RESPONSE];

                $accountAlreadyExistsForCapitalInNewLedger = true;
            }
        }

        $journal = $payload[Constants::RESPONSE];

        if(isset($journal[LedgerConstants::TRANSACTOR_EVENT]) &&
            ($journal[LedgerConstants::TRANSACTOR_EVENT] === Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_PROCESSED
            || $journal[LedgerConstants::TRANSACTOR_EVENT] === Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_REVERSED))
        {
            $this->handleOndemandSettlementEventsOnAcknowledgment($response, $journal, $accountAlreadyExistsForCapitalInNewLedger);
            return;
        }

        if(isset($response[LedgerConstants::JOURNALS]) and is_array($response[LedgerConstants::JOURNALS]))
        {
            $isBulkJournal = true;
        }

        if($isBulkJournal === true)
        {
            $bulkJournals = $response[LedgerConstants::JOURNALS];

            $singleJournal = $bulkJournals[0];

            $transactorId = $singleJournal[LedgerConstants::TRANSACTOR_ID];

            $transactorEvent = $singleJournal[LedgerConstants::TRANSACTOR_EVENT];

            $this->handleBulkJournalFlow($bulkJournals, $transactorId, $transactorEvent);

            return;
        }

        // Assigning value again as request object is not populated in the kafka response for success cases
        $transactorId = $journal[LedgerConstants::TRANSACTOR_ID];

        $transactorEvent = $journal[LedgerConstants::TRANSACTOR_EVENT];

        // We will not create any transaction in case of amount credits expiry
        if($transactorEvent === LedgerConstants::AMOUNT_CREDITS_EXPIRY_EVENT)
        {
            $this->trace->info(TraceCode::AMOUNT_CREDITS_EXPIRY_JOURNAL_CREATION_SUCCESS, [
                LedgerConstants::DATA   => $journal
            ]);

            $this->softDelete($transactorId, $transactorEvent);
            return;
        }

        try
        {
            $this->handleTransactionCreationOnAcknowledgement($journal, $transactorId, $transactorEvent, false);
        }
        catch (Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::PG_LEDGER_ACK_WORKER_FAILURE,
            );

            $this->trace->count(Metric::PG_LEDGER_ACK_WORKER_FAILURE,
                [
                    LedgerConstants::TRANSACTOR_EVENT       => $transactorEvent,
                    LedgerConstants::TRANSACTOR_ID          => $transactorId,
                    Constants::SOURCE                       => Constants::ACK_WORKER
                ]);
        }
    }

    private function handleOndemandSettlementEventsOnAcknowledgment($response, $journal, bool $accountAlreadyExistsForCapitalInNewLedger) {
        $transactorIdVal = $response[LedgerConstants::TRANSACTOR_ID];
        $event = $response[LedgerConstants::TRANSACTOR_EVENT];
        try
        {
            $entityId = $this->determineEntityIDFromTransactorID($transactorIdVal);
            $txn = (new OndemandCore)->handleLedgerEventsOnAcknowledgment($journal, $transactorIdVal, $event, $entityId, $accountAlreadyExistsForCapitalInNewLedger);
            if($txn === null)
            {
                $this->trace->info(TraceCode::CAPITAL_PG_LEDGER_TRANSACTION_NOT_CREATED,
                    [
                        LedgerConstants::TRANSACTOR_EVENT       => $event,
                        LedgerConstants::TRANSACTOR_ID          => $transactorIdVal,
                        Constants::SOURCE                       => Constants::ACK_WORKER
                    ]
                );
            }
            else
            {
                $txnId = $txn->getId();

                $this->trace->info(TraceCode::CAPITAL_PG_LEDGER_CREATE_TRANSACTION_SUCCESS,
                    [
                        LedgerConstants::API_TRANSACTION_ID     => $txnId,
                        LedgerConstants::TRANSACTOR_EVENT       => $event,
                        LedgerConstants::TRANSACTOR_ID          => $transactorIdVal,
                        Constants::SOURCE                       => Constants::ACK_WORKER
                    ]
                );

                $this->trace->count(Metric::PG_LEDGER_CREATE_TRANSACTION_SUCCESS, [
                    LedgerConstants::TRANSACTOR_EVENT   => $event,
                    Constants::SOURCE                   => Constants::ACK_WORKER
                ]);
            }
            $this->softDelete($response[LedgerConstants::TRANSACTOR_ID], $response[LedgerConstants::TRANSACTOR_EVENT]);
        }
        catch (Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::PG_LEDGER_ACK_WORKER_FAILURE,
            );

            $this->trace->count(Metric::PG_LEDGER_ACK_WORKER_FAILURE,
                [
                    LedgerConstants::TRANSACTOR_EVENT       => $event,
                    LedgerConstants::TRANSACTOR_ID          => $transactorIdVal,
                    Constants::SOURCE                       => Constants::ACK_WORKER
                ]);
        }
    }

    private function handleTransactionCreationOnAcknowledgement($journal, $transactorId, $transactorEvent, $isBulkJournal)
    {
        $this->emitMetric($transactorId, $transactorEvent);

        $txn = $this->createTransactionFromJournal($journal, Constants::ACK_WORKER, $isBulkJournal);

        if($txn ===  null)
        {
            $this->trace->info(TraceCode::PG_LEDGER_TRANSACTION_NOT_CREATED,
                [
                    LedgerConstants::TRANSACTOR_EVENT       => $transactorEvent,
                    LedgerConstants::TRANSACTOR_ID          => $transactorId,
                    Constants::SOURCE                       => Constants::ACK_WORKER
                ]
            );
        }
        else
        {
            $txnId = $txn->getId();

            $this->trace->info(TraceCode::PG_LEDGER_CREATE_TRANSACTION_SUCCESS,
                [
                    LedgerConstants::API_TRANSACTION_ID     => $txnId,
                    LedgerConstants::TRANSACTOR_EVENT       => $transactorEvent,
                    LedgerConstants::TRANSACTOR_ID          => $transactorId,
                    Constants::SOURCE                       => Constants::ACK_WORKER
                ]
            );

            $this->trace->count(Metric::PG_LEDGER_CREATE_TRANSACTION_SUCCESS, [
                LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                Constants::SOURCE                   => Constants::ACK_WORKER
            ]);
        }

        $this->softDelete($transactorId, $transactorEvent);
    }

    private function handleBulkJournalFlow($bulkJournals, $transactorId, $transactorEvent)
    {
        try
        {
            $this->handleTransactionCreationOnAcknowledgement($bulkJournals, $transactorId, $transactorEvent, true);
        }
        catch (Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::PG_LEDGER_ACK_WORKER_FAILURE,
            );

            $this->trace->count(Metric::PG_LEDGER_ACK_WORKER_FAILURE,
                [
                    LedgerConstants::TRANSACTOR_EVENT       => $transactorEvent,
                    LedgerConstants::TRANSACTOR_ID          => $transactorId,
                    Constants::SOURCE                       => Constants::ACK_WORKER
                ]);
        }
    }

    private function emitMetric(string $transactorId, string $transactorEvent)
    {
        try
        {
            $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

            $outboxEntries = $this->repo->ledger_outbox->fetchOutboxEntriesByPayloadName($payloadName);

            if(count($outboxEntries) === 0)
            {
                return;
            }

            $entry = $outboxEntries[0];

            // Metric to calculate latency from Pg outbox entry creation to pg ack received
            $createdAtInMs = (int)($entry[Entity::CREATED_AT]*1000);

            $totalDuration = millitime() - $createdAtInMs;

            $this->trace->histogram(
                Metric::PG_LEDGER_KAFKA_ACKNOWLEDGMENT_RECEIVED_FROM_PG,
                $totalDuration,
            );

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::PG_LEDGER_METRIC_PUSH_FAILURE,
            );
        }
    }

    // We determine if reversal ledger entries were created with feeOnlyReversal
    // If the amount debited from merchant balance or refund credits is zero.
    // As only fee and commission are deducted.
    private function determineIfFeeOnlyReversalEvent(array $ledgerEntries): bool
    {
        foreach ($ledgerEntries as $ledgerEntry)
        {
            $accountEntities = $ledgerEntry[LedgerConstants::ACCOUNT_ENTITIES];

            $fundAccountTypeArr = $accountEntities[LedgerConstants::FUND_ACCOUNT_TYPE];

            $fundAccountType = (count($fundAccountTypeArr) > 0) ? $fundAccountTypeArr[0] : "";

            if($fundAccountType === LedgerConstants::CUSTOMER_REFUND)
            {
                $ledgerEntryAmount = (float) $ledgerEntry[LedgerConstants::AMOUNT];
                if($ledgerEntryAmount === 0.0)
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @throws BadRequestException
     */
    private function determineEntityIDFromTransactorID(string $transactorId): string
    {
        $transactorIdArr = $this->getTransactorIDArray($transactorId);
        return $transactorIdArr[1];
    }

    private function getTransactorIDArray(string $transactorId): array
    {
        $transactorIdArr = explode('_', $transactorId);

        if(count($transactorIdArr) != 2)
        {
            $this->trace->debug(
                TraceCode::INVALID_TRANSACTOR_ID,
                [
                    LedgerConstants::MESSAGE        => "provide a valid public ID for transactor",
                    LedgerConstants::TRANSACTOR_ID  => $transactorId
                ]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_TRANSACTOR_ID);
        }

        return $transactorIdArr;
    }

    private function determineTransactionType(string $transactorId)
    {
        $transactorIdArr = $this->getTransactorIDArray($transactorId);

        $publicIdPrefix = $transactorIdArr[0];

        $res = [
            LedgerConstants::TRANSACTOR_ID => $transactorId,
            LedgerConstants::ID            => $transactorIdArr[1]
        ];

        switch ($publicIdPrefix)
        {
            case "pay":
                $res[Constants::TYPE] = Constants::PAYMENT;
                return $res;
            case "rfnd":
                $res[Constants::TYPE] = Constants::REFUND;
                return $res;
            case "rvrsl":
                $res[Constants::TYPE] = Constants::REVERSAL;
                return $res;
            case "credits":
                $res[Constants::TYPE] = Constants::CREDIT_LOADING;
                return $res;
            case "adj":
                $res[Constants::TYPE] = Constants::RESERVE_BALANCE_LOADING;
                return $res;
            case "trf":
                $res[Constants::TYPE] = Constants::TRANSFER;
                return $res;
            default:
                $res[Constants::TYPE] = "";
                return $res;
        }
    }

    private function softDelete(string $transactorId, string $transactorEvent)
    {
        try
        {
            $this->repo->transaction(function () use ($transactorId, $transactorEvent)
            {
                $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

                $outboxEntries = $this->repo->ledger_outbox->fetchOutboxEntriesByPayloadName($payloadName);

                $this->trace->info(
                    TraceCode::PG_LEDGER_OUTBOX_FETCH,
                    [
                        Constants::OUBTOX_ENTRIES_COUNT     => count($outboxEntries),
                        Constants::PAYLOAD_NAME             => $payloadName,
                        Constants::SOURCE                   => Constants::ACK_WORKER
                    ]
                );

                foreach ($outboxEntries as $entry)
                {
                    $update = [
                        Entity::IS_DELETED => true,
                        Entity::DELETED_AT => Carbon::now()->getTimestamp(),
                    ];

                    $this->updateOutboxEntry($entry, $update);
                }

                $this->trace->count(Metric::PG_LEDGER_OUTBOX_SOFT_DELETE_SUCCESS, [
                    LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                    Constants::SOURCE                   => Constants::ACK_WORKER
                ]);

                $this->trace->info(
                    TraceCode::PG_LEDGER_OUTBOX_SOFT_DELETE_SUCCESS,
                    [
                        LedgerConstants::TRANSACTOR_ID      => $transactorId,
                        LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                        Constants::SOURCE                   => Constants::ACK_WORKER
                    ]
                );
            });
        }
        catch (\Throwable $ex)
        {
            $this->trace->count(Metric::PG_LEDGER_OUTBOX_SOFT_DELETE_FAILURE, [
                LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                Constants::SOURCE   => Constants::ACK_WORKER
            ]);

            $this->trace->traceException(
                $ex,
                500,
                TraceCode::PG_LEDGER_OUTBOX_SOFT_DELETE_FAILURE,
                [
                    LedgerConstants::TRANSACTOR_ID      => $transactorId,
                    LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                    Constants::SOURCE                   => Constants::ACK_WORKER
                ]
            );

            throw $ex;
        }
    }

    // handles errors in journal creation in pg_legder ack worker.
    // returns journal payload if error is BAD_REQUEST_RECORD_ALREADY_EXIST and txn is missing, else null
    private function handleLedgerJournalCreateWorkerFailures(string $transactorId, string $transactorEvent, array $errorResponse)
    {
        $errorMessage = $errorResponse[Constants::MSG];

        if($errorMessage === "")
        {
            return null;
        }

        $this->ledgerService = $this->app['ledger'];

        // Non-Recoverable errors cannot be retried, hence soft deleted from the outbox table
        foreach (Constants::NON_RETRYABLE_ERROR_CODES as $nonRetryableError)
        {
            if (str_contains($errorMessage, $nonRetryableError) === true)
            {
                if ($nonRetryableError === Constants::BAD_REQUEST_RECORD_ALREADY_EXIST)
                {

                    // if the error is record_already_exists and we do not have to create a txn then return from here.
                    if(in_array($transactorEvent, Constants::NON_TRANSACTION_EVENTS))
                    {
                        $this->trace->debug(TraceCode::NON_RECOVERABLE_ERROR_ACK_WORKER, [
                            constants::ERROR_TYPE               => constants::NON_RECOVERABLE_ERROR,
                            constants::ERROR_MESSAGE            => $errorMessage,
                            LedgerConstants::TRANSACTOR_ID      => $transactorId,
                            LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                            constants::SOURCE                   => constants::ACK_WORKER,
                        ]);

                        $this->trace->count(Metric::LEDGER_REVERSE_SHADOW_JOURNAL_CREATE_FAILURE, [
                            constants::ERROR_TYPE               => constants::NON_RECOVERABLE_ERROR,
                            LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                            constants::SOURCE                   => constants::ACK_WORKER,

                        ]);

                        // Soft deleting this record from outbox as the error is not recoverable and will
                        // fail when retried via cron as well
                        $this->softDelete($transactorId, $transactorEvent);

                        return null;
                    }

                    // check if txn exists already for the journal
                    $journal = $this->getJournalByTransactorInfo($transactorId, $transactorEvent, $this->ledgerService);

                    if($journal === null)
                    {
                        // if journal does not exist, return so that cron retries the entry
                        return null;
                    }

                    $existingTxn = $this->repo->transaction->find($journal['id']);

                    if (($existingTxn === null) and (!in_array($transactorEvent, Constants::NON_TRANSACTION_EVENTS)) )
                    {
                        $this->trace->debug(TraceCode::PG_LEDGER_TRANSACTION_NOT_FOUND, [
                            constants::ERROR_TYPE               => constants::RECOVERABLE_ERROR,
                            constants::ERROR_MESSAGE            => $errorMessage,
                            LedgerConstants::TRANSACTOR_ID      => $transactorId,
                            LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                            Constants::SOURCE                   => Constants::ACK_WORKER,
                        ]);

                        // if transaction does not exist, return journal so that worker creates txn.
                        return $journal;
                    }
                }

                if ($transactorEvent === Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_REVERSED ||
                    $transactorEvent === Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_PROCESSED)
                {
                    $this->handleOndemandSettlementEventsOnFailure($transactorEvent, $transactorId);
                }

                $this->trace->debug(TraceCode::NON_RECOVERABLE_ERROR_ACK_WORKER, [
                    constants::ERROR_TYPE               => constants::NON_RECOVERABLE_ERROR,
                    constants::ERROR_MESSAGE            => $errorMessage,
                    LedgerConstants::TRANSACTOR_ID      => $transactorId,
                    LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                    constants::SOURCE                   => constants::ACK_WORKER,
                ]);

                $this->trace->count(Metric::LEDGER_REVERSE_SHADOW_JOURNAL_CREATE_FAILURE, [
                    constants::ERROR_TYPE               => constants::NON_RECOVERABLE_ERROR,
                    LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                    constants::SOURCE                   => constants::ACK_WORKER,

                ]);

                // Soft deleting this record from outbox as the error is not recoverable and will
                // fail when retried via cron as well
                $this->softDelete($transactorId, $transactorEvent);

                return null;
            }
        }

        // Recoverable errors will be retried via cron, hence they won't be soft deleted
        $this->trace->count(Metric::LEDGER_REVERSE_SHADOW_JOURNAL_CREATE_FAILURE, [
            constants::ERROR_TYPE               => constants::RECOVERABLE_ERROR,
            constants::SOURCE                   => constants::ACK_WORKER,
            LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent
        ]);

        $this->trace->debug(TraceCode::RECOVERABLE_ERROR_ACK_WORKER, [
            constants::ERROR_TYPE               => constants::RECOVERABLE_ERROR,
            constants::ERROR_MESSAGE            => $errorMessage,
            LedgerConstants::TRANSACTOR_ID      => $transactorId,
            LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
            constants::SOURCE                   => constants::ACK_WORKER,
        ]);

        // Emitting a separate metric for ledger account not found issue.
        // The metric will trigger an alert to notify users for account creation
        if (str_contains($errorMessage, constants::ACCOUNT_DISCOVERY_ACCOUNT_NOT_FOUND_FAILURE))
        {
            $this->trace->debug(TraceCode::LEDGER_ACCOUNT_NOT_FOUND, [
                constants::ERROR_TYPE               => constants::RECOVERABLE_ERROR,
                constants::ERROR_MESSAGE            => $errorMessage,
                LedgerConstants::TRANSACTOR_ID      => $transactorId,
                LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                constants::SOURCE                   => constants::ACK_WORKER,
            ]);
            // todo: add account details later

            $this->trace->count(Metric::LEDGER_ACCOUNT_NOT_FOUND, [
                constants::ERROR_TYPE               => constants::RECOVERABLE_ERROR,
                constants::SOURCE                   => constants::ACK_WORKER,
                LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent
            ]);
        }

        return null;
    }

    private function handleOndemandSettlementEventsOnFailure(string $event, string $transactorId)
    {
        try {
            $entityId = $this->determineEntityIDFromTransactorID($transactorId);

            (new OndemandCore)->handleLedgerEventsOnFailure($event, $entityId);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::ONDEMAND_LEDGER_FAILED_EVENT_HANDLING_FAILURE);
        }
    }

    public function createTransactionFromJournal(array $journal, $source, $isBulkJournal = false)
    {
        $transactorPublicId = "";
        $transactionType = "";
        $merchantId = "";
        $transactorEvent = "";
        $ledgerEntries = [];
        $journalId = "";

        if($isBulkJournal === true)
        {
            $singleJournal = $journal[0];

            $transactorPublicId = $singleJournal[LedgerConstants::TRANSACTOR_ID];

            $journalId = $singleJournal['id'];

            $ledgerEntries = $singleJournal["ledger_entry"];

            $transactorEvent = $singleJournal[LedgerConstants::TRANSACTOR_EVENT];
        }
        else
        {
            $transactorPublicId = $journal[LedgerConstants::TRANSACTOR_ID];

            $journalId = $journal['id'];

            $ledgerEntries = $journal["ledger_entry"];

            $transactorEvent = $journal[LedgerConstants::TRANSACTOR_EVENT];
        }

        $merchantId = (count($ledgerEntries) > 0) ? $ledgerEntries[0]["merchant_id"] : "";

        //TODO: This needs to be fixed
        $this->merchant = $this->repo->merchant->findOrFail($merchantId);

        $transactorInfo = $this->determineTransactionType($transactorPublicId);

        $transactionType = $transactorInfo[Constants::TYPE];

        $txn = null;

        if($transactionType === Constants::REVERSAL)
        {
            $reversal = $this->repo
                ->reversal
                ->findByPublicIdAndMerchant($transactorPublicId, $this->merchant, []);

            $refundId = $reversal->toArray()['entity_id'];

            $txn = $this->repo->transaction->findByEntityId($refundId, $this->merchant);

            if($txn === null)
            {
                // Case 1: if request comes via worker and refund transaction is null, throw an exception and do not soft-delete the outbox entry.
                // This is because the execution can reach this code block before the refund transaction is created (edge case)
                // Reversal txn creation in this case should be retried from cron.
                if($source === Constants::ACK_WORKER)
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_REFUND_REVERSAL_NOT_APPLICABLE,
                        null,
                        [
                            LedgerConstants::REFUND_ID => $refundId,
                        ],
                    );
                }

                // Case 2: if request comes via cron   and refund transaction is null, return null and soft delete outbox entry.
                // It is a reversal for virtual refund and txn should not be created for it.
                // For refunds that are soft deleted on scrooge, we create a reversal entity if the refund has journal entries created.
                // We do not want to create transaction for such refunds. If the refund does not have transaction by this point of time,
                // We go ahead and mark the outbox entry as soft deleted
                if($source === Constants::CRON)
                {
                    return null;
                }

            }

            $txn = (new Reversal\Core)->createReversalTransaction($reversal, $journalId);

        }
        else if($transactionType === Constants::PAYMENT)
        {
            if($transactorEvent === LedgerConstants::GATEWAY_CAPTURED)
            {
                $payment = $this->repo
                    ->payment
                    ->findByPublicIdAndMerchant($transactorPublicId, $this->merchant, []);

                    $txn = $this->createTransactionFromAuthorisedPaymentInReverseShadow($payment, $transactorPublicId);
            }

            if($transactorEvent === LedgerConstants::MERCHANT_CAPTURED)
            {
                $payment = $this->repo
                    ->payment
                    ->findByPublicIdAndMerchant($transactorPublicId, $this->merchant, []);

                $txn = $this->createTransactionFromCapturedPaymentInReverseShadow($payment, $journalId, $transactorEvent);
            }
        }
        else if($transactionType === Constants::CREDIT_LOADING)
        {
            $this->trace->info(TraceCode::PG_LEDGER_ACK_WORKER_CREDIT_LOADING_EVENT, [
                LedgerConstants::JOURNALS   => $journal,
                LedgerConstants::SOURCE     => $source
            ]);

            return null;
        }
        else if($transactionType === Constants::RESERVE_BALANCE_LOADING)
        {
            [$creditJournalId, $debitJournalId] = $this->determineJournalIdForAPITransaction($journal, "merchant_balance", "merchant_reserve_balance" );

            if((empty($creditJournalId)) or
                (empty($debitJournalId)))
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_EXPECTED_FUND_ACCOUNT_TYPE_NOT_PRESENT,
                    null,
                    [
                        LedgerConstants::ADJUSTMENT_ID      => $transactorPublicId,
                    ]);
            }

            $this->trace->info(TraceCode::PG_LEDGER_ACK_WORKER_RESERVE_BALANCE_LOADING_EVENT, [
                LedgerConstants::JOURNALS   => $journal,
                LedgerConstants::SOURCE     => $source
            ]);

            try
            {
                $adjustment = $this->repo->adjustment->findByPublicId($transactorPublicId);
            }
            catch (\Exception $e)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID,
                    null,
                    [
                        LedgerConstants::ADJUSTMENT_ID      => $transactorPublicId,
                    ]);
            }

            $txn = $this->repo->transaction(function() use ($adjustment, $creditJournalId)
            {
                $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($adjustment);

                if (isset($txn) === true)
                {
                    return $txn;
                }

                [$balance, $sendReserveBalanceMail] = (new Balance\Core())->createOrFetchReserveBalance($adjustment->merchant,
                    Type::RESERVE_PRIMARY, $this->mode);

                if ($sendReserveBalanceMail === true)
                {
                    (new Balance\NegativeReserveBalanceMailers())->sendReserveBalanceActivatedMail($adjustment->merchant, $balance);
                }

                $adjustment->balance()->associate($balance);

                $txn = (new Transaction\Core)->createFromAdjustment($adjustment, $creditJournalId);

                $adjustment->setStatus(Status::PROCESSED);

                $this->repo->saveOrFail($txn);

                $this->repo->saveOrFail($adjustment);

                return $txn;
            });

            (new Transaction\Core)->dispatchEventForTransactionCreated($txn);

            $merchantCore = new Merchant\Core();

            $input = [
                "amount" => $adjustment->getAmount(),
                "type" => "reserve_primary",
                "currency" => "INR",
                "description" => $adjustment->getDescription()
            ];

            $merchantCore->sendFundAdditionSuccessEvent($input, $merchantId, $adjustment, EventCode::RESERVE_BALANCE_ADDITION_SUCCESS);

            $merchantCore->sendAlertIfReserveBalanceAdditionIsSuccessful($merchantId, $adjustment->getAmount(), Type::RESERVE_BALANCE);

            $this->trace->info(TraceCode::ADJUSTMENT_TRANSACTION_CREATED,
                [
                    LedgerConstants::ADJUSTMENT_ID      => $transactorPublicId,
                    LedgerConstants::JOURNAL_ID         => $journalId,
                    LedgerConstants::API_TRANSACTION_ID => $txn->getId(),
                ]);

            return $txn;
        }
        else if($transactionType === Constants::TRANSFER)
        {
            [$creditJournalId, $debitJournalId] = $this->determineJournalIdForAPITransaction($journal, "merchant_balance", "merchant_balance" );

            if((empty($creditJournalId)) or
                (empty($debitJournalId)))
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_EXPECTED_FUND_ACCOUNT_TYPE_NOT_PRESENT,
                    null,
                    [
                        LedgerConstants::TRANSFER_ID      => $transactorPublicId,
                    ]);
            }

            $this->trace->info(TraceCode::PG_LEDGER_TRANSFER_PROCESSED_EVENT, [
                LedgerConstants::JOURNALS   => $journal,
                LedgerConstants::SOURCE     => $source
            ]);

            $mutexResource = null;

            try
            {
                $transfer = $this->repo->transfer->findByPublicId($transactorPublicId);

                if ($transfer->getSourceType() === E::PAYMENT)
                {
                    $sourcePayment = $transfer->source;

                    $transferProcessor = new PaymentTransfer($sourcePayment);

                    $mutexResource = Transfer\Core::getTransferProcessingMutexResource(Transfer\Constant::PAYMENT);
                }
                else if ($transfer->getSourceType() === E::ORDER)
                {
                    $sourceOrderId = $transfer->getSourceId();

                    $apiPayments = $this->repo->payment->fetchPaymentsForOrderId($sourceOrderId, $transfer->getMerchantId());

                    $rearchPayments = $this->app['pg_router']->fetchOrderPayments($sourceOrderId, $transfer->getMerchantId());

                    $allPayments = $apiPayments->merge($rearchPayments);

                    $sourcePayment = null;

                    foreach ($allPayments as $singlePayment)
                    {
                        if ($singlePayment->getStatus() === Payment\Status::CAPTURED)
                        {
                            $sourcePayment = $singlePayment;

                            break;
                        }
                    }

                    if ($sourcePayment === null)
                    {
                        return null;
                    }

                    // fetching payment again to get from sources configured for archived entity
                    // As of now, archived payment fetch with findOrFail happens on fallback replica
                    // This will also prevent columns like _record_source from warm storage to be present in entity attributes
                    $sourcePayment = $this->repo->payment->findOrFail($sourcePayment->getId());

                    $transferProcessor = new OrderTransfer($sourcePayment);

                    $mutexResource = Transfer\Core::getTransferProcessingMutexResource(Transfer\Constant::ORDER);
                }
            }
            catch (\Exception $e)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID,
                    null,
                    [
                        LedgerConstants::TRANSFER_ID      => $transactorPublicId,
                    ]);
            }

            $transferCore = new Transfer\Core();

            $transferMetric =  new Transfer\Metric();

            $mutexConfig = Transfer\AbstractTransfer::fetchTransferProcessMutexConfig();

            $transferProcessStartTime = microtime(true);

            try
            {
                $this->mutex->acquireAndRelease(
                    $mutexResource . $sourcePayment->getPublicId(),
                    function () use ($transferCore, $transfer, $sourcePayment, $transferProcessor, $creditJournalId, $debitJournalId, $transferMetric, $source)
                    {
                        $this->repo->transaction(function () use ($transferCore, $transfer, $sourcePayment, $transferProcessor, $creditJournalId, $debitJournalId, $transferMetric, $source)
                        {
                            if($transfer->getStatus() === Transfer\Status::PENDING)
                            {
                                // set transfer as processed
                                $transfer->setProcessed();

                                $transfer->setErrorCode(null);

                                $transferProcessor->setSettlementStatus($transfer);

                                $totalTransferAmount = $transfer->getAmount();

                                $transferCore->updatePaymentAmountTransferred($sourcePayment, $totalTransferAmount);

                                $this->repo->saveOrFail($transfer);

                                $transferMetric->pushTransferProcessSuccessMetrics();

                                $transferProcessor->fireTransferProcessedWebhookIfApplicable($transfer);

                                // in txn creation - check if transfer has txn created, duplicate txn check

                                $input = [
                                    LedgerConstants::DEBIT_TRANSACTION_ID  => $debitJournalId,
                                    LedgerConstants::CREDIT_TRANSACTION_ID => $creditJournalId,
                                    LedgerConstants::TRANSFER_ID           => $transfer->getPublicId(),
                                    LedgerConstants::SOURCE                => $source,
                                ];

                                // dispatch to queue again for txn creation
                                $transferCore->dispatchForTransferProcessing($transfer->getSourceType(), $sourcePayment, 30, true, $input);

                                $this->trace->info(
                                    TraceCode::TRANSFER_PROCCESSED_SUCCESSFULLY_IN_REVERSE_SHADOW,
                                    [
                                        LedgerConstants::TRANSFER_ID  => $transfer->getPublicId(),
                                        'transfer_input_to_queue'     => $input
                                    ]);
    
                            // transfer transactions created via queue in async
                            }
                            else
                            {
                                $this->trace->info(
                                    TraceCode::TRANSFER_ALREADY_PROCCESSED,
                                    [
                                       "transfer" => $transfer->toArray(),
                                    ]);
                            }

                            return null;
                        });
                    },
                    $mutexConfig[Transfer\Constant::TRANSFER_PROCESS_MUTEX_LOCK_TIMEOUT_SEC_KEY],
                    ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_PROCESS_IN_PROGRESS,
                    $mutexConfig[Transfer\Constant::TRANSFER_PROCESS_MUTEX_NUM_RETRIES_KEY],
                    $mutexConfig[Transfer\Constant::TRANSFER_PROCESS_MUTEX_MIN_RETRY_DELAY_MS_KEY],
                    $mutexConfig[Transfer\Constant::TRANSFER_PROCESS_MUTEX_MAX_RETRY_DELAY_MS_KEY], true);
            }
            catch (\Exception $ex)
            {
                $transferMetric->pushTransferProcessFailedMetrics($ex);

                throw  $ex;
            }
            finally
            {
                //Note: for reverse shadow merchants, this would done from ack worker
                $transferProcessEndTime = microtime(true);

                $transferMetric->pushTransferProcessingTimeInWorkerMetrics(
                    $transfer->getSourceType(),
                    ($transferProcessEndTime - $transferProcessStartTime)
                );

                $transferCore->trackTransferProcessingTime($transfer, $sourcePayment);
            }

        }

        //Note: Transaction is not created for credit loading event.

        return $txn;
    }

    private function createTransactionFromCapturedPaymentInReverseShadow($payment, $journalId, $transactorEvent)
    {
            $resource = $this->getTransactionMutexresource($payment);

            $txn = $this->mutex->acquireAndRelease(
                $resource,
                function () use ($payment, $journalId, $transactorEvent)
                {
                    return $this->repo->transaction(function() use ($payment, $journalId, $transactorEvent)
                     {
                         $paymentProcessor = new Payment\Processor\Processor($this->merchant);

                         $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($payment);

                        if ((isset($txn) === true) and
                            ($txn->isBalanceUpdated() === true))
                        {
                            return $txn;
                        }

                        if ((isset($txn) === true) and
                            ($transactorEvent === LedgerConstants::MERCHANT_CAPTURED) and
                            ($txn->getId() !== $journalId))
                        {
                            throw new BadRequestException(ErrorCode::BAD_REQUEST_API_TRANSACTION_JOURNAL_ID_MISMATCH);
                        }

                        if ((isset($txn) === true) and
                            ($transactorEvent === LedgerConstants::GATEWAY_CAPTURED))
                        {
                            $apiTransactionId = $this->getAPITransactionId($payment->getPublicId(), $payment);
                            if($txn->getId() !== $apiTransactionId)
                            {
                                throw new BadRequestException(ErrorCode::BAD_REQUEST_API_TRANSACTION_JOURNAL_ID_MISMATCH);
                            }
                        }

                        list($txn, $merchantBalance) = $paymentProcessor->createTransactionFromCapturedPayment($payment, $journalId);

                        $paymentProcessor->processTransferIfApplicable($payment);

                        return $txn;
                    });
                },
                self::PAYMENT_TRANSACTION_CREATION_MUTEX_TTL,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
                self::PAYMENT_TRANSACTION_CREATION_MUTEX_RETRIES,
                self::PAYMENT_TRANSACTION_CREATION_MUTEX_MIN_RETRY_DELAY,
                self::PAYMENT_TRANSACTION_CREATION_MUTEX_MAX_RETRY_DELAY
            );

            return $txn;
    }

    private function createTransactionFromAuthorisedPaymentInReverseShadow($payment, $transactorPublicId)
    {
        $resource = $this->getTransactionMutexresource($payment);

        $txn = $this->mutex->acquireAndRelease(
            $resource,
            function () use ($payment, $transactorPublicId)
            {
                return $this->repo->transaction(function() use ($payment, $transactorPublicId)
                {
                    $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($payment);

                    if (isset($txn) === true) {
                        return $txn;
                    }

                    $apiTransactionId = $this->getAPITransactionId($transactorPublicId, $payment);

                    list($txn, $feeSplit) = (new Transaction\Core())->createFromPaymentAuthorizedInReverseShadow($payment, $apiTransactionId);

                    $this->repo->saveOrFail($txn);
                    // This is required to save the association of the transaction with the payment.
                    $this->repo->saveOrFail($payment);

                    return $txn;
                });
            },
            self::PAYMENT_TRANSACTION_CREATION_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            self::PAYMENT_TRANSACTION_CREATION_MUTEX_RETRIES,
            self::PAYMENT_TRANSACTION_CREATION_MUTEX_MIN_RETRY_DELAY,
            self::PAYMENT_TRANSACTION_CREATION_MUTEX_MAX_RETRY_DELAY
        );

        return $txn;
    }


    //pg-ledger outbox cron retries journal and txn creation for non-deleted outbox entries in reverse-shadow mode
    public function retryFailedReverseShadowTransactions($limit) : array
    {
        $ledgerService = $this->app['ledger'];

        $successful = 0;

        $successfulIds = [];

        $failed = 0;

        $failedIds = [];

        $now = time();

        $startTimestamp = $now - Constants::OUTBOX_RETRY_DEFAULT_START_TIME;
        $endTimestamp = $now - Constants::OUTBOX_RETRY_DEFAULT_END_TIME;

        $entries = $this->repo->ledger_outbox->fetchOldOutboxEntriesForRetry($limit, $startTimestamp, $endTimestamp);

        $this->trace->info(TraceCode::PG_LEDGER_OUTBOX_FETCH,
            [
                Constants::OUBTOX_ENTRIES_COUNT     => count($entries),
                Constants::SOURCE                   => Constants::CRON
            ]
        );

        foreach ($entries as $entry)
        {
            $entry->reload();

            if (($entry->isDeleted() === false) && ($entry[Entity::RETRY_COUNT] < ReverseShadow\Constants::MAX_RETRY_COUNT_CRON))
            {
                $retries = $entry[Entity::RETRY_COUNT] + 1;

                $payload = $entry[Entity::PAYLOAD_SERIALIZED];

                //decode base_64 payload
                $payload = base64_decode($payload);

                $payload = json_decode($payload, true);

                $transactorId = $payload[LedgerConstants::TRANSACTOR_ID];

                $transactorEvent = $payload[LedgerConstants::TRANSACTOR_EVENT];

                $idempotencyKey =$payload[LedgerConstants::IDEMPOTENCY_KEY];

                try
                {
                    $requestHeaders = $this->getJournalRequestHeadersSync($idempotencyKey);

                    $this->trace->info(TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST,
                        [
                            LedgerConstants::TRANSACTOR_ID    => $transactorId,
                            LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                            LedgerConstants::IDEMPOTENCY_KEY  => $idempotencyKey,
                            Constants::SOURCE                 => Constants::CRON

                        ]
                    );

                    $isBulkJournal = false;
                    if(in_array($transactorEvent, Constants::BULK_JOURNAL_EVENTS))
                    {
                        $isBulkJournal = true;
                    }
                    else
                    {
                        $merchantId = $payload[LedgerConstants::MERCHANT_ID];

                        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                        if ($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
                        {
                            $this->updateRetryCountAndSoftDelete($entry, $retries);
                            $successful++;
                            array_push($successfulIds, $transactorId);
                            if($transactorEvent === Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_PROCESSED ||
                                $transactorEvent === Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_REVERSED)
                            {
                                $this->handleOndemandSettlementEventsOnFailure($transactorEvent, $transactorId);
                            }
                            continue;
                        }
                    }

                    $response = ($isBulkJournal === false) ? $ledgerService->createJournal($payload, $requestHeaders, true) : $ledgerService->createBulkJournal($payload, $requestHeaders, true);

                    $journal = $response[LedgerService::RESPONSE_BODY];

                    $bulkJournals = [];

                    $responseBody = $response[LedgerService::RESPONSE_BODY];

                    // Handling use case for bulk journals
                    if($isBulkJournal === true)
                    {
                        $bulkJournals = $responseBody[LedgerConstants::JOURNALS];
                    }

                    $this->trace->info(TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_RESPONSE,
                        [
                            LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                            LedgerConstants::JOURNALS       => $journal,
                            Constants::SOURCE                 => Constants::CRON
                        ]
                    );

                    $this->trace->count(Metric::PG_LEDGER_CREATE_JOURNAL_ENTRY_SUCCESS, [
                        LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                        Constants::SOURCE                 => Constants::CRON
                    ]);


                    // We will not create any transaction in case of amount credits expiry
                    if($transactorEvent === LedgerConstants::AMOUNT_CREDITS_EXPIRY_EVENT)
                    {
                        $this->trace->info(TraceCode::AMOUNT_CREDITS_EXPIRY_JOURNAL_CREATION_SUCCESS, [
                            LedgerConstants::DATA   => $journal
                        ]);

                        $isDeleted = $this->updateRetryCountAndSoftDelete($entry, $retries);

                        if ($isDeleted === true)
                        {
                            $successful++;
                            array_push($successfulIds, $transactorId);
                        }

                        continue;
                    }

                    try
                    {
                        $txn = null;

                        if($transactorEvent === Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_PROCESSED || $transactorEvent === Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_REVERSED)
                        {
                            $entityId = $this->determineEntityIDFromTransactorID($transactorId);
                            $txn = (new OndemandCore)->handleLedgerEventsOnAcknowledgment($journal, $transactorId, $transactorEvent, $entityId, false);
                        }
                        else if($isBulkJournal === true)
                        {
                            $txn = $this->createTransactionFromJournal($bulkJournals, Constants::CRON, true);
                        }
                        else
                        {
                            $txn = $this->createTransactionFromJournal($journal, Constants::CRON, false);
                        }

                        if($txn ===  null)
                        {
                            $this->trace->info(TraceCode::PG_LEDGER_TRANSACTION_NOT_CREATED,
                                [
                                    LedgerConstants::TRANSACTOR_EVENT       => $transactorEvent,
                                    LedgerConstants::TRANSACTOR_ID          => $transactorId,
                                    LedgerConstants::JOURNAL_ID             => $journal[LedgerConstants::ID],
                                    Constants::SOURCE                       => Constants::CRON
                                ]
                            );
                        }
                        else
                        {
                            $txnId = $txn->getId();

                            $this->trace->info(TraceCode::PG_LEDGER_CREATE_TRANSACTION_SUCCESS,
                                [
                                    LedgerConstants::API_TRANSACTION_ID   => $txnId,
                                    LedgerConstants::JOURNAL_ID           => $journal[LedgerConstants::ID],
                                    LedgerConstants::TRANSACTOR_EVENT     => $transactorEvent,
                                    LedgerConstants::TRANSACTOR_ID        => $transactorId,
                                    Constants::SOURCE                     => Constants::CRON
                                ]
                            );

                            $this->trace->count(Metric::PG_LEDGER_CREATE_TRANSACTION_SUCCESS, [
                                LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                                Constants::SOURCE                   => Constants::CRON
                            ]);
                        }
                    }
                    catch (\Throwable $e)
                    {
                        // catches all txn failure exceptions
                        $this->trace->traceException(
                            $e,
                            Trace::CRITICAL,
                            TraceCode::PG_LEDGER_OUTBOX_CRON_RETRY_FAILURE,
                            [
                                Constants::SOURCE => Constants::TRANSACTION_CREATE,
                                LedgerReverseShadowConstants::RETRY_COUNT => $retries,
                            ]
                        );

                        $this->trace->count(Metric::PG_LEDGER_CREATE_TRANSACTION_FAILURE, [
                                LedgerConstants::TRANSACTOR_EVENT       => $transactorEvent,
                                Constants::SOURCE                       => Constants::CRON
                        ]);

                        $this->trace->count(Metric::PG_LEDGER_OUTBOX_CRON_RETRY_FAILURE, [
                            LedgerReverseShadowConstants::RETRY_COUNT => $retries
                        ]);

                        $this->updateRetryCount($entry, $retries, $transactorEvent);

                        $failed++;
                        array_push($failedIds, $transactorId);
                        continue;
                    }

                    $isDeleted = $this->updateRetryCountAndSoftDelete($entry, $retries);

                    if ($isDeleted === true)
                    {
                        $successful++;
                        array_push($successfulIds, $transactorId);
                    }
                    else
                    {
                        $failed++;
                        array_push($failedIds, $transactorId);
                    }
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::CRITICAL,
                        TraceCode::PG_LEDGER_OUTBOX_CRON_RETRY_FAILURE,
                        [
                            Constants::SOURCE => Constants::JOURNAL_CREATE,
                            LedgerReverseShadowConstants::RETRY_COUNT => $retries,
                        ]
                    );

                    $this->trace->count(Metric::PG_LEDGER_OUTBOX_CRON_RETRY_FAILURE, [
                        LedgerReverseShadowConstants::RETRY_COUNT => $retries
                    ]);

                    $canRetry = $this->handleSyncLedgerJournalCreateFailures($payload,  $e->getError()->toPublicArray(), Constants::CRON);

                    if($canRetry === false)
                    {
                        if($transactorEvent === Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_PROCESSED ||
                            $transactorEvent === Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_REVERSED)
                        {
                            $this->handleOndemandSettlementEventsOnFailure($transactorEvent, $transactorId);
                        }
                        else if ($transactorEvent === LedgerConstants::TRANSFER)
                        {
                            $this->failTransferWithErrorCodeAndMessage($entry);
                        }

                        $this->updateRetryCountAndSoftDelete($entry, $retries);
                    }
                    else if ($retries === LedgerReverseShadowConstants::MAX_RETRY_COUNT_CRON)
                    {
                        $this->trace->count(Metric::PG_LEDGER_OUTBOX_CRON_RETRIES_EXHAUSTED, [
                            LedgerReverseShadowConstants::RETRY_COUNT => $retries
                        ]);
                        if($transactorEvent === Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_PROCESSED ||
                            $transactorEvent === Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_REVERSED)
                        {
                            $this->handleOndemandSettlementEventsOnFailure($transactorEvent, $transactorId);
                        }
                        else if (($transactorEvent === LedgerConstants::TRANSFER) and
                                 (str_contains($e->getMessage(), Constants::INSUFFICIENT_BALANCE_FAILURE)))
                        {
                            $this->failTransferWithErrorCodeAndMessage(
                                $entry, ErrorCode::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE,
                                PublicErrorDescription::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE);
                        }

                        $this->updateRetryCountAndSoftDelete($entry, $retries);
                    }
                    else
                    {
                        $this->updateRetryCount($entry, $retries, $transactorEvent);
                    }

                    $failed++;
                    array_push($failedIds, $transactorId);
                }
            }
        }

        return [
            'successful entries count' => $successful,
            'successful Ids' => $successfulIds,
            'failed entries count' =>  $failed,
            'failed Ids' =>  $failedIds,
        ];
    }

    protected function updateRetryCount(Entity $entry, $retries, $transactorEvent)
    {
        try
        {
            $this->repo->transaction(function () use ($entry, $retries, $transactorEvent)
            {
                $update = [
                    Entity::RETRY_COUNT => $retries,
                ];

                $this->updateOutboxEntry($entry, $update);

                $this->trace->count(Metric::PG_LEDGER_OUTBOX_UPDATE_RETRY_COUNT_SUCCESS, [
                    LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                    Constants::SOURCE                 => Constants::CRON,
                ]);

                $this->trace->info(
                    TraceCode::PG_LEDGER_OUTBOX_UPDATE_RETRY_COUNT_SUCCESS,
                    [
                        Entity::PAYLOAD_NAME => $entry->getPayloadName(),
                        Constants::SOURCE => Constants::CRON,
                    ]
                );
            });
        }
        catch (\Throwable $ex)
        {
            $this->trace->count(Metric::PG_LEDGER_OUTBOX_UPDATE_RETRY_COUNT_FAILURE, [
                LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                Constants::SOURCE                 => Constants::CRON,
            ]);

            $this->trace->traceException(
                $ex,
                500,
                TraceCode::PG_LEDGER_OUTBOX_UPDATE_RETRY_COUNT_FAILURE,
                [
                    LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                    Constants::SOURCE                 => Constants::CRON,
                ]
            );

            throw $ex;
        }

    }

    protected function updateOutboxEntry(Entity $entry, array $update)
    {
        $this->repo->ledger_outbox->lockForUpdateAndReload($entry);

        $entry->edit($update);

        $this->repo->ledger_outbox->saveOrFail($entry);
    }

    private function updateRetryCountAndSoftDelete($entry, int $retryCount = null): bool
    {
        try
        {
            $retryCount = isset($retryCount) === true ? $retryCount : 0;

            $this->repo->transaction(function () use ($entry, $retryCount)
            {

                $update = [
                    Entity::IS_DELETED  => true,
                    Entity::DELETED_AT  => Carbon::now()->getTimestamp(),
                    Entity::RETRY_COUNT => $retryCount,
                ];

                $this->updateOutboxEntry($entry, $update);

                $this->trace->count(Metric::PG_LEDGER_OUTBOX_SOFT_DELETE_SUCCESS, [
                    Constants::SOURCE     => Constants::CRON,
                ]);

                $this->trace->info(
                    TraceCode::PG_LEDGER_OUTBOX_SOFT_DELETE_SUCCESS,
                    [
                        Entity::PAYLOAD_NAME => $entry->getPayloadName(),
                        Constants::SOURCE => Constants::CRON,
                    ]
                );
            });

            return true;
        }
        catch (\Throwable $ex)
        {
            $this->trace->count(Metric::PG_LEDGER_OUTBOX_SOFT_DELETE_FAILURE, [
                Constants::SOURCE => Constants::CRON,
            ]);

            $this->trace->traceException(
                $ex,
                500,
                TraceCode::PG_LEDGER_OUTBOX_SOFT_DELETE_FAILURE,
                [
                    Entity::PAYLOAD_NAME => $entry->getPayloadName(),
                    Constants::SOURCE => Constants::CRON,
                ]
            );

            return false;
        }
    }

    /**
     * Creates partitions till T+6 date
     * Drops the oldest partition with a validation that it should be older than T-7.
     *
     * @return bool[]
     */
    public function createLedgerOutboxPartition(): array
    {
        try
        {
            $this->repo->ledger_outbox->managePartitions();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::TABLE_PARTITION_ERROR);

            return ['success' => false];
        }

        return ['success' => true];
    }

    private function failTransferWithErrorCodeAndMessage($entry, $errorCode=ErrorCode::BAD_REQUEST_ERROR,
                                                         $errorMessage=PublicErrorDescription::BAD_REQUEST_ERROR)
    {
        $payloadName = $entry['payload_name'];

        $transferIdPublic = str_replace('-' . LedgerConstants::TRANSFER, "", $payloadName);

        $transferId = Transfer\Entity::verifyIdAndSilentlyStripSign($transferIdPublic);

        $transfer = $this->repo->transfer->findOrFail($transferId);

        $transfer->setFailed();

        $transfer->setErrorCode($errorCode);

        $transfer->setMessage($errorMessage);

        $source = $transfer->getSourceType();

        if ($source === Transfer\Constant::PAYMENT)
        {
            $transfer->setAttempts(Transfer\Constant::MAX_ALLOWED_PAYMENT_TRANSFER_PROCESS_ATTEMPTS);
        }
        else if ($source === Transfer\Constant::ORDER)
        {
            $transfer->setAttempts(Transfer\Constant::MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS);
        }

        $this->repo->saveOrFail($transfer);

        $this->trace->info(
            TraceCode::TRANSFER_FAILED_DUE_TO_INSUFFICIENT_BALANCE,
            [
                'transfer_id'         => $transfer->getId(),
            ]);

        (new Transfer\Core())->eventTransferFailed($transfer);
    }
}
