<?php

namespace RZP\Models\LedgerOutbox\Cron\Transfer;

use App;
use Exception;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as E;
use RZP\Constants\Metric;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer;
use RZP\Models\Transaction;
use RZP\Trace\Tracer;
use RZP\Error\PublicErrorDescription;
use RZP\Services\Ledger as LedgerService;
use RZP\Exception\BadRequestException;
use RZP\Models\LedgerOutbox\Entity as Entity;
use RZP\Models\LedgerOutbox\Constants as Constants;
use RZP\Models\LedgerOutbox\Core as LedgerOutboxCore;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\Payment\Processor\Capture as CaptureTrait;
use RZP\Models\Ledger\ReverseShadow\Constants as LedgerReverseShadowConstants;

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


    //pg-ledger outbox cron retries journal and txn creation for non-deleted outbox entries in reverse-shadow mode
    public function retryFailedReverseShadowTransferTransactions($limit) : array
    {
        $ledgerService = $this->app['ledger'];

        $ledgerOutboxCore = (new LedgerOutboxCore());

        $successful = 0;

        $successfulIds = [];

        $failed = 0;

        $failedIds = [];

        $now = time();

        $startTimestamp = $now - Constants::OUTBOX_RETRY_DEFAULT_START_TIME;
        $endTimestamp = $now - Constants::OUTBOX_RETRY_DEFAULT_END_TIME;

        $entries = $this->repo->ledger_outbox->fetchOldOutboxEntriesForRetryByEntityType($limit, $startTimestamp, $endTimestamp, Constants::TRANSFER,  LedgerReverseShadowConstants::MAX_RETRY_COUNT_TRANSFER_CRON);

        $this->trace->info(TraceCode::PG_LEDGER_OUTBOX_FETCH,
            [
                Constants::OUBTOX_ENTRIES_COUNT     => count($entries),
                Constants::SOURCE                   => Constants::CRON,
                Constants::CRON_TYPE                => Constants::TRANSFER
            ]
        );

        foreach ($entries as $entry)
        {
            $entry->reload();

            if (($entry->isDeleted() === false) && ($entry[Entity::RETRY_COUNT] < LedgerReverseShadowConstants::MAX_RETRY_COUNT_TRANSFER_CRON))
            {
                $retries = $entry[Entity::RETRY_COUNT] + 1;

                $payload = $entry[Entity::PAYLOAD_SERIALIZED];

                //decode base_64 payload
                $payload = base64_decode($payload);

                $payload = json_decode($payload, true);

                $transactorId = $payload[LedgerConstants::TRANSACTOR_ID];

                $transactorEvent = $payload[LedgerConstants::TRANSACTOR_EVENT];

                if ($transactorEvent !== LedgerConstants::TRANSFER)
                {
                    $this->trace->info(TraceCode::PG_LEDGER_OUTBOX_FETCH_INVALID,
                        [
                            LedgerConstants::TRANSACTOR_ID    => $transactorId,
                            LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                            Constants::SOURCE                 => Constants::CRON,
                            Constants::CRON_TYPE              => Constants::TRANSFER
                        ]
                    );

                    $failed++;

                    array_push($failedIds, $transactorId);

                    continue;
                }

                $idempotencyKey =$payload[LedgerConstants::IDEMPOTENCY_KEY];

                try
                {
                    $requestHeaders = $this->getJournalRequestHeadersSync($idempotencyKey);

                    $this->trace->info(TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST,
                        [
                            LedgerConstants::TRANSACTOR_ID    => $transactorId,
                            LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                            LedgerConstants::IDEMPOTENCY_KEY  => $idempotencyKey,
                            Constants::SOURCE                 => Constants::CRON,
                            Constants::CRON_TYPE              => Constants::TRANSFER
                        ]
                    );

//                  Note: All transfer_processed journals are bulk journals
                    $response = $ledgerService->createBulkJournal($payload, $requestHeaders, true);

                    $journal = $response[LedgerService::RESPONSE_BODY];

                    $responseBody = $response[LedgerService::RESPONSE_BODY];

                    $bulkJournals = $responseBody[LedgerConstants::JOURNALS];

                    $this->trace->info(TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_RESPONSE,
                        [
                            LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                            LedgerConstants::JOURNALS       => $journal,
                            Constants::SOURCE               => Constants::CRON,
                            Constants::CRON_TYPE            => Constants::TRANSFER
                        ]
                    );

                    $this->trace->count(Metric::PG_LEDGER_CREATE_JOURNAL_ENTRY_SUCCESS, [
                        LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                        Constants::SOURCE                 => Constants::CRON,
                        Constants::CRON_TYPE              => Constants::TRANSFER
                    ]);

                    try
                    {
                        $txn = null;

                        $txn = $ledgerOutboxCore->createTransactionFromJournal($bulkJournals, Constants::CRON, true);

                        if($txn === null)
                        {
                            $this->trace->info(TraceCode::PG_LEDGER_TRANSACTION_NOT_CREATED,
                                [
                                    LedgerConstants::TRANSACTOR_EVENT       => $transactorEvent,
                                    LedgerConstants::TRANSACTOR_ID          => $transactorId,
                                    LedgerConstants::JOURNAL_ID             => $journal[LedgerConstants::ID],
                                    Constants::SOURCE                       => Constants::CRON,
                                    Constants::CRON_TYPE                    => Constants::TRANSFER
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
                                    Constants::SOURCE                     => Constants::CRON,
                                    Constants::CRON_TYPE                  => Constants::TRANSFER
                                ]
                            );

                            $this->trace->count(Metric::PG_LEDGER_CREATE_TRANSACTION_SUCCESS, [
                                LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                                Constants::SOURCE                   => Constants::CRON,
                                Constants::CRON_TYPE                => Constants::TRANSFER
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
                                Constants::SOURCE                           => Constants::TRANSACTION_CREATE,
                                Constants::CRON_TYPE                        => Constants::TRANSFER,
                                LedgerReverseShadowConstants::RETRY_COUNT   => $retries,
                            ]
                        );

                        $this->trace->count(Metric::PG_LEDGER_CREATE_TRANSACTION_FAILURE, [
                                LedgerConstants::TRANSACTOR_EVENT       => $transactorEvent,
                                Constants::SOURCE                       => Constants::CRON,
                                Constants::CRON_TYPE                    => Constants::TRANSFER,
                        ]);

                        $this->trace->count(Metric::PG_LEDGER_OUTBOX_CRON_RETRY_FAILURE, [
                            LedgerReverseShadowConstants::RETRY_COUNT => $retries,
                            Constants::CRON_TYPE                      => Constants::TRANSFER,
                        ]);

                        // For transfer transactor IDs, skip update of retry count. This will allow
                        // retry of transfer processing infinitely (status update and dispatch to queue
                        // for txn creation) in case of failures.
                        (new Transfer\Metric())->pushLedgerOutboxRetryCronFailureMetrics($e);

                        $failed++;
                        array_push($failedIds, $transactorId);
                        continue;
                    }

                    $isDeleted = $ledgerOutboxCore->updateRetryCountAndSoftDelete($entry, $retries);

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
                            Constants::SOURCE                           => Constants::JOURNAL_CREATE,
                            LedgerReverseShadowConstants::RETRY_COUNT   => $retries,
                            Constants::CRON_TYPE                        => Constants::TRANSFER,
                        ]
                    );

                    $this->trace->count(Metric::PG_LEDGER_OUTBOX_CRON_RETRY_FAILURE, [
                        LedgerReverseShadowConstants::RETRY_COUNT => $retries,
                        Constants::CRON_TYPE                      => Constants::TRANSFER,
                    ]);

                    $canRetry = $ledgerOutboxCore->handleSyncLedgerJournalCreateFailures($payload,  $e->getError()->toPublicArray(), Constants::CRON);

                    if($canRetry === false)
                    {
                        $ledgerOutboxCore->failTransferWithErrorCodeAndMessage($entry);

                        $ledgerOutboxCore->updateRetryCountAndSoftDelete($entry, $retries);
                    }
                    else if ($retries === LedgerReverseShadowConstants::MAX_RETRY_COUNT_TRANSFER_CRON)
                    {
                        $this->trace->count(Metric::PG_LEDGER_OUTBOX_CRON_RETRIES_EXHAUSTED, [
                            LedgerReverseShadowConstants::RETRY_COUNT => $retries,
                            Constants::CRON_TYPE                      => Constants::TRANSFER,
                        ]);

                        if (str_contains($e->getMessage(), Constants::INSUFFICIENT_BALANCE_FAILURE))
                        {
                            $ledgerOutboxCore->failTransferWithErrorCodeAndMessage(
                                $entry, ErrorCode::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE,
                                PublicErrorDescription::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE);
                        }

                        $ledgerOutboxCore->updateRetryCountAndSoftDelete($entry, $retries);
                    }
                    else
                    {
                        $ledgerOutboxCore->updateRetryCount($entry, $retries, $transactorEvent);
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


}
