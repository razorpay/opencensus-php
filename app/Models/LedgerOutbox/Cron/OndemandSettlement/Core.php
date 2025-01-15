<?php

namespace RZP\Models\LedgerOutbox\Cron\OndemandSettlement;

use App;
use Exception;
use Carbon\Carbon;
use RZP\Models\Feature;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as E;
use RZP\Constants\Metric;
use RZP\Models\Base;
use RZP\Models\Settlement\Ondemand\Core as OndemandCore;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;
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
    public function retryFailedReverseShadowSettlementOndemandTransactions($limit) : array
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

        $entries = $this->repo->ledger_outbox->fetchOldOutboxEntriesForRetryByEntityType($limit, $startTimestamp, $endTimestamp, Constants::ONDEMAND_SETTLEMENT,  LedgerReverseShadowConstants::MAX_RETRY_COUNT_ONDEMAND_SETTLEMENT_CRON);

        $this->trace->info(TraceCode::PG_LEDGER_OUTBOX_FETCH,
            [
                Constants::OUBTOX_ENTRIES_COUNT     => count($entries),
                Constants::SOURCE                   => Constants::CRON,
                Constants::CRON_TYPE                => Constants::ONDEMAND_SETTLEMENT
            ]
        );

        foreach ($entries as $entry)
        {
            $entry->reload();



            if (($entry->isDeleted() === false) && ($entry[Entity::RETRY_COUNT] < LedgerReverseShadowConstants::MAX_RETRY_COUNT_ONDEMAND_SETTLEMENT_CRON))
            {
                $retries = $entry[Entity::RETRY_COUNT] + 1;

                $payload = $entry[Entity::PAYLOAD_SERIALIZED];

                //decode base_64 payload
                $payload = base64_decode($payload);

                $payload = json_decode($payload, true);

                $transactorId = $payload[LedgerConstants::TRANSACTOR_ID];

                $transactorEvent = $payload[LedgerConstants::TRANSACTOR_EVENT];

                if( !in_array($transactorEvent, Constants::SETLLEMENT_ONDEMAND_EVENTS, true))
                {
                    $this->trace->info(TraceCode::PG_LEDGER_OUTBOX_FETCH_INVALID,
                        [
                            LedgerConstants::TRANSACTOR_ID    => $transactorId,
                            LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                            Constants::SOURCE                 => Constants::CRON,
                            Constants::CRON_TYPE              => Constants::ONDEMAND_SETTLEMENT
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
                            Constants::CRON_TYPE              => Constants::ONDEMAND_SETTLEMENT
                        ]
                    );

                    $merchantId = $payload[LedgerConstants::MERCHANT_ID];

                    $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                    if ($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
                    {
                        $ledgerOutboxCore->updateRetryCountAndSoftDelete($entry, $retries);

                        $successful++;

                        array_push($successfulIds, $transactorId);

                         if( in_array($transactorEvent, Constants::SETLLEMENT_ONDEMAND_EVENTS, true))
                        {
                            $ledgerOutboxCore->handleOndemandSettlementEventsOnFailure($transactorEvent, $transactorId);
                        }
                        continue;
                    }

//                  Note: All ods journals are single journals
                    $response = $ledgerService->createJournal($payload, $requestHeaders, true);

                    $journal = $response[LedgerService::RESPONSE_BODY];

                    $this->trace->info(TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_RESPONSE,
                        [
                            LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                            LedgerConstants::JOURNALS       => $journal,
                            Constants::SOURCE               => Constants::CRON,
                            Constants::CRON_TYPE            => Constants::ONDEMAND_SETTLEMENT
                        ]
                    );

                    $this->trace->count(Metric::PG_LEDGER_CREATE_JOURNAL_ENTRY_SUCCESS, [
                        LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                        Constants::SOURCE                 => Constants::CRON,
                        Constants::CRON_TYPE              => Constants::ONDEMAND_SETTLEMENT
                    ]);

                    try
                    {
                        $entityId = $ledgerOutboxCore->determineEntityIDFromTransactorID($transactorId);

                        (new OndemandCore)->handleLedgerEventsOnAcknowledgment($journal, $transactorId, $transactorEvent, $entityId, false);

                        $this->trace->info(TraceCode::ONDEMAND_SETTLEMENT_LEDGER_ACKNOWLEDGEMENT_SUCCESS,
                            [
                                LedgerConstants::JOURNAL_ID           => $journal[LedgerConstants::ID],
                                LedgerConstants::TRANSACTOR_EVENT     => $transactorEvent,
                                LedgerConstants::TRANSACTOR_ID        => $transactorId,
                                Constants::SOURCE                     => Constants::CRON,
                                Constants::CRON_TYPE                  => Constants::ONDEMAND_SETTLEMENT
                            ]
                        );
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
                                Constants::CRON_TYPE                        => Constants::ONDEMAND_SETTLEMENT,
                                LedgerReverseShadowConstants::RETRY_COUNT   => $retries,
                            ]
                        );

                        $this->trace->count(Metric::PG_LEDGER_OUTBOX_CRON_RETRY_FAILURE, [
                            LedgerReverseShadowConstants::RETRY_COUNT => $retries,
                            Constants::CRON_TYPE                      => Constants::ONDEMAND_SETTLEMENT,
                        ]);

                        $ledgerOutboxCore->updateRetryCount($entry, $retries, $transactorEvent);

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
                            Constants::CRON_TYPE                        => Constants::ONDEMAND_SETTLEMENT,
                        ]
                    );

                    $this->trace->count(Metric::PG_LEDGER_OUTBOX_CRON_RETRY_FAILURE, [
                        LedgerReverseShadowConstants::RETRY_COUNT => $retries,
                        Constants::CRON_TYPE                      => Constants::ONDEMAND_SETTLEMENT,
                    ]);

                    $canRetry = $ledgerOutboxCore->handleSyncLedgerJournalCreateFailures($payload,  $e->getError()->toPublicArray(), Constants::CRON);

                    if($canRetry === false)
                    {
                        $ledgerOutboxCore->handleOndemandSettlementEventsOnFailure($transactorEvent, $transactorId);

                        $ledgerOutboxCore->updateRetryCountAndSoftDelete($entry, $retries);
                    }
                    else if ($retries === LedgerReverseShadowConstants::MAX_RETRY_COUNT_ONDEMAND_SETTLEMENT_CRON)
                    {
                        $this->trace->count(Metric::PG_LEDGER_OUTBOX_CRON_RETRIES_EXHAUSTED, [
                            LedgerReverseShadowConstants::RETRY_COUNT => $retries,
                            Constants::CRON_TYPE                      => Constants::ONDEMAND_SETTLEMENT,
                        ]);

                        $ledgerOutboxCore->handleOndemandSettlementEventsOnFailure($transactorEvent, $transactorId);

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
