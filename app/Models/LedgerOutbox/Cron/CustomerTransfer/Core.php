<?php

namespace RZP\Models\LedgerOutbox\Cron\CustomerTransfer;

use App;
use RZP\Constants\Metric;
use RZP\Models\Base;
use RZP\Models\Ledger\ReverseShadow\Constants as LedgerReverseShadowConstants;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\LedgerOutbox\Constants as Constants;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\LedgerOutbox\Entity as Entity;
use RZP\Trace\TraceCode;
use RZP\Models\LedgerOutbox\Core as LedgerOutboxCore;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\Transfer;

class Core extends Base\Core{
    use ReverseShadowTrait;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->trace = $this->app['trace'];
    }

    public function retryFailedReverseShadowCustomerTransfer($limit,$maxRetryCount): array
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

        $entries = $this->repo->ledger_outbox->fetchOldOutboxEntriesForRetryByEntityType($limit, $startTimestamp, $endTimestamp, Constants::CUSTOMER_TRANSFER ,  $maxRetryCount);

        $this->trace->info(TraceCode::PG_LEDGER_OUTBOX_FETCH,
             [
                   Constants::OUBTOX_ENTRIES_COUNT     => count($entries),
                   Constants::SOURCE                   => Constants::CRON,
                   Constants::CRON_TYPE                => Constants:: CUSTOMER_TRANSFER
             ]
        );

        foreach ($entries as $entry)
        {
            $entry->reload();

            if (($entry->isDeleted() === false) && ($entry[Entity::RETRY_COUNT] < $maxRetryCount))
            {
                $retries = $entry[Entity::RETRY_COUNT] + 1;

                $payload = $entry[Entity::PAYLOAD_SERIALIZED];

                //decode base_64 payload
                $payload = base64_decode($payload);

                $payload = json_decode($payload, true);

                 $transactorId = $payload[LedgerConstants::TRANSACTOR_ID];

                 $transactorEvent = $payload[LedgerConstants::TRANSACTOR_EVENT];

                 if ($transactorEvent !== LedgerConstants::CUSTOMER_WALLET_LOADING)
                 {
                     $this->trace->info(TraceCode::PG_LEDGER_OUTBOX_FETCH_INVALID,
                         [
                               LedgerConstants::TRANSACTOR_ID    => $transactorId,
                               LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                                Constants::SOURCE                 => Constants::CRON,
                                Constants::CRON_TYPE              => Constants::CUSTOMER_TRANSFER
                         ]
                     );

                     $failed++;

                     array_push($failedIds, $transactorId);

                      continue;
                }

                $this->trace->info(TraceCode::PG_LEDGER_OUTBOX_CRON_RETRY_TRACE,
                    [
                        LedgerConstants::TRANSACTOR_ID      => $transactorId,
                        LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                        Entity::RETRY_COUNT                 => $entry[Entity::RETRY_COUNT],
                        Entity::MAX_RETRY_COUNT             => $maxRetryCount,
                        Constants::SOURCE                   => Constants::CRON,
                        Constants::CRON_TYPE                => Constants::CUSTOMER_TRANSFER
                    ]
                );

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
                            Constants::CRON_TYPE              => Constants::CUSTOMER_TRANSFER
                        ]
                    );
                    $response = $ledgerService->createJournal($payload, $requestHeaders, true);

                    $journal = $response[LedgerService::RESPONSE_BODY];

                    $this->trace->info(TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_RESPONSE,
                        [
                            LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                            LedgerConstants::JOURNALS       => $journal,
                            Constants::SOURCE               => Constants::CRON,
                            Constants::CRON_TYPE            => Constants::CUSTOMER_TRANSFER
                        ]
                    );

                    $this->trace->count(Metric::PG_LEDGER_CREATE_JOURNAL_ENTRY_SUCCESS, [
                        LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                        Constants::SOURCE                 => Constants::CRON,
                        Constants::CRON_TYPE              => Constants::CUSTOMER_TRANSFER
                    ]);

                    $ledgerOutboxCore->postProcessingJournalResponse($journal);

                    try
                    {
                        $txn = null;
                        $txn = $ledgerOutboxCore->createTransactionFromJournal($journal, Constants::CRON, false);

                        if($txn === null)
                        {
                            $this->trace->info(TraceCode::PG_LEDGER_TRANSACTION_NOT_CREATED,
                                [
                                    LedgerConstants::TRANSACTOR_EVENT       => $transactorEvent,
                                    LedgerConstants::TRANSACTOR_ID          => $transactorId,
                                    LedgerConstants::JOURNAL_ID             => $journal[LedgerConstants::ID],
                                    Constants::SOURCE                       => Constants::CRON,
                                    Constants::CRON_TYPE                    => Constants::CUSTOMER_TRANSFER
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
                                    Constants::CRON_TYPE                  => Constants::CUSTOMER_TRANSFER
                                ]
                            );

                            $this->trace->count(Metric::PG_LEDGER_CREATE_TRANSACTION_SUCCESS, [
                                LedgerConstants::TRANSACTOR_EVENT   => $transactorEvent,
                                Constants::SOURCE                   => Constants::CRON,
                                Constants::CRON_TYPE                => Constants::CUSTOMER_TRANSFER
                            ]);
                        }
                    }
                    catch (\Throwable $e)
                    {
                        // catches all txn failure exceptions
                        $this->trace->error(TraceCode::PG_LEDGER_OUTBOX_CRON_RETRY_FAILURE, [
                            Constants::ERROR_MESSAGE                    => $e->getMessage(),
                            Constants::SOURCE                           => Constants::TRANSACTION_CREATE,
                            Constants::CRON_TYPE                        => Constants::CUSTOMER_TRANSFER,
                            LedgerReverseShadowConstants::RETRY_COUNT   => $retries,
                        ]);

                        $this->trace->count(Metric::PG_LEDGER_CREATE_TRANSACTION_FAILURE, [
                            LedgerConstants::TRANSACTOR_EVENT       => $transactorEvent,
                            Constants::SOURCE                       => Constants::CRON,
                            Constants::CRON_TYPE                    => Constants::CUSTOMER_TRANSFER,
                        ]);

                        $this->trace->count(Metric::PG_LEDGER_OUTBOX_CRON_RETRY_FAILURE, [
                            LedgerReverseShadowConstants::RETRY_COUNT => $retries,
                            Constants::CRON_TYPE                      => Constants::CUSTOMER_TRANSFER,
                        ]);

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
                    $this->trace->error(TraceCode::PG_LEDGER_OUTBOX_CRON_RETRY_FAILURE, [
                        Constants::ERROR_MESSAGE                    => $e->getMessage(),
                        Constants::SOURCE                           => Constants::JOURNAL_CREATE,
                        LedgerReverseShadowConstants::RETRY_COUNT   => $retries,
                        Constants::CRON_TYPE                        => Constants::CUSTOMER_TRANSFER,
                    ]);

                    $this->trace->count(Metric::PG_LEDGER_OUTBOX_CRON_RETRY_FAILURE, [
                        LedgerReverseShadowConstants::RETRY_COUNT => $retries,
                        Constants::CRON_TYPE                      => Constants::CUSTOMER_TRANSFER,
                    ]);

                    $canRetry = $ledgerOutboxCore->handleSyncLedgerJournalCreateFailures($payload,  $e->getError()->toPublicArray(), Constants::CRON);

                    if($canRetry === false)
                    {
                        // TODO: alert metric
                    } else {
                        $ledgerOutboxCore->updateRetryCount($entry, $retries, $transactorEvent);
                    }

                    $failed++;

                    array_push($failedIds, $transactorId);
                }
            }
        }

        return [
            LedgerConstants::SUCCESSFUL_ENTRIES_COUNT => $successful,
            LedgerConstants::SUCCESSFUL_IDS           => $successfulIds,
            LedgerConstants::FAILED_ENTRIES_COUNT     =>  $failed,
            LedgerConstants::FAILED_IDS               =>  $failedIds,
        ];

    }
}
