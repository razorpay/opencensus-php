<?php

namespace RZP\Models\LedgerOutbox;

use App;
use Carbon\Carbon;
use Exception;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Ledger\ReverseShadow;
use RZP\Models\Ledger\ReverseShadow\Constants as LedgerReverseShadowConstants;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Services\Ledger as LedgerService;
use RZP\Trace\TraceCode;
use RZP\Models\Reversal;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Transaction;
use RZP\Models\Payment\Processor\Capture as CaptureTrait;
use RZP\Models\Ledger\Constants as LedgerConstants;

class Core extends Base\Core
{
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
            }
        }

        $journal = $payload[Constants::RESPONSE];

        if(isset($response[LedgerConstants::JOURNALS]) and is_array($response[LedgerConstants::JOURNALS]))
        {
            $isBulkJournal = true;
        }

        if($isBulkJournal)
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

    private function determineTransactionType(string $transactorId)
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

    public function createTransactionFromJournal(array $journal, $source, $isBulkJournal = false)
    {
        $transactorPublicId = "";
        $transactionType = "";
        $merchantId = "";
        $transactorEvent = "";
        $ledgerEntries = [];
        $journalId = "";

        if($isBulkJournal)
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

                $txn = $this->repo->transaction(function() use ($payment, $transactorPublicId)
                {
                    $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($payment);

                    if (isset($txn) === true)
                    {
                        return $txn;
                    }

                    $apiTransactionId = $this->getAPITransactionId($transactorPublicId);

                    $resource = $this->getTransactionMutexresource($payment);

                    list($txn, $feeSplit) = $this->mutex->acquireAndRelease(
                        $resource,
                        function () use ($payment, $apiTransactionId)
                        {
                            list($txn, $feeSplit) = (new Transaction\Core())->createFromPaymentAuthorized($payment, $apiTransactionId);

                            $this->repo->saveOrFail($txn);
                            // This is required to save the association of the transaction with the payment.
                            $this->repo->saveOrFail($payment);
                        });

                    return $txn;
                });
            }

            if($transactorEvent === LedgerConstants::MERCHANT_CAPTURED)
            {
                $payment = $this->repo
                    ->payment
                    ->findByPublicIdAndMerchant($transactorPublicId, $this->merchant, []);

                $txn = $this->repo->transaction(function() use ($payment, $journalId)
                {
                    $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($payment);

                    if ((isset($txn) === true) and
                        ($txn->isBalanceUpdated() === true))
                    {
                        return $txn;
                    }

                    if ((isset($txn) === true) and
                        ($txn->getId() !== $journalId))
                    {
                        throw new BadRequestException(ErrorCode::BAD_REQUEST_API_TRANSACTION_JOURNAL_ID_MISMATCH);
                    }

                    $paymentProcessor = new Payment\Processor\Processor($this->merchant);

                    $resource = $this->getTransactionMutexresource($payment);

                    list($txn, $merchantBalance) = $this->mutex->acquireAndRelease(
                        $resource,
                        function () use ($payment, $journalId, $paymentProcessor)
                        {
                            return $paymentProcessor->createTransactionFromCapturedPayment($payment, $journalId);
                        });

                    $paymentProcessor->processTransferIfApplicable($payment);

                    return $txn;
                });
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

        //Note: Transaction is not created for gateway_captured event and credit loading event.

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

                    try
                    {
                        $txn = null;

                        if($isBulkJournal === true)
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
                    catch (Exception $e)
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
                            [
                                LedgerConstants::TRANSACTOR_EVENT       => $transactorEvent,
                                Constants::SOURCE                       => Constants::CRON
                            ]
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
                catch (Exception $e)
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
                        $this->updateRetryCountAndSoftDelete($entry, $retries);
                    }
                    else if ($retries === LedgerReverseShadowConstants::MAX_RETRY_COUNT_CRON)
                    {
                        $this->trace->count(Metric::PG_LEDGER_OUTBOX_CRON_RETRIES_EXHAUSTED, [
                            LedgerReverseShadowConstants::RETRY_COUNT => $retries
                        ]);
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
}
