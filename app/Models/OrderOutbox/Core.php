<?php

namespace RZP\Models\OrderOutbox;

use App;
use Exception;
use Carbon\Carbon;
use RZP\Constants\Metric;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base;
use RZP\Trace\TraceCode;


class Core extends Base\Core
{
    //order outbox cron retries order update request for non-deleted outbox entries
    public function retryOrderUpdate($input) : array
    {
        $successful = 0;

        $successfulIds = [];

        $failed = 0;

        $failedIds = [];

        $now = time();

        $limit = $input['limit'] ?? Constants::DEFAULT_LIMIT;

        $startTimeOffset = $input['start_time_offset'] ?? Constants::OUTBOX_RETRY_DEFAULT_START_TIME;

        $endTimeOffset = $input['end_time_offset'] ?? Constants::OUTBOX_RETRY_DEFAULT_END_TIME;

        $startTimestamp = $now - $startTimeOffset;

        $endTimestamp = $now - $endTimeOffset;

        $entries = $this->repo->order_outbox->fetchOldOutboxEntriesForRetry($limit, $startTimestamp, $endTimestamp);

        $this->trace->info(TraceCode::ORDER_OUTBOX_FETCH,
            [
                Constants::OUBTOX_ENTRIES_COUNT     => count($entries),
                'start_time'                        => $startTimestamp,
                'end_time'                          => $endTimestamp,
                'limit'                             => $limit,
            ]
        );

        foreach ($entries as $entry)
        {
            $entry->reload();

            if (($entry->isDeleted() === false) && ($entry[Entity::RETRY_COUNT] < Constants::MAX_RETRY_COUNT))
            {
                $retries = $entry[Entity::RETRY_COUNT] + 1;

                $payload = $entry[Entity::PAYLOAD];

                $payload = json_decode($payload, true);

                $orderId = $entry[Entity::ORDER_ID];

                $merchantId = $entry[Entity::MERCHANT_ID];

                try
                {
                    $mutex =  App::getFacadeRoot()['api.mutex'];

                    $mutex->acquireAndRelease(
                        $orderId . Constants::ORDER_UPDATE_MUTEX,
                        function() use($payload, $orderId, $merchantId)
                        {
                            $this->app['pg_router']->updateInternalOrder($payload, $orderId, $merchantId, true);

                        }
                    );

                    $successful++;
                    array_push($successfulIds, $entry->getOrderId());

                    $isDeleted = $this->updateRetryCountAndSoftDelete($entry, $retries);

                    //For deleting the older entry with the same order id
                    $this->softDelete($entry);

                }
                catch (Exception $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::CRITICAL,
                        TraceCode::ORDER_OUTBOX_CRON_RETRY_FAILURE,
                        [
                            Entity::ORDER_ID        => $entry->getOrderId(),
                            Entity::RETRY_COUNT     => $retries,
                        ]
                    );

                    $this->trace->count(Metric::ORDER_OUTBOX_CRON_RETRY_FAILURE, [
                        Entity::ORDER_ID        => $entry->getOrderId(),
                        Entity::RETRY_COUNT     => $retries
                    ]);

                    $this->updateRetryCount($entry, $retries);

                    $failed++;
                    array_push($failedIds, $entry->getOrderId());
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

    public function softDelete(Entity $orderOutbox)
    {
        try
        {
            $this->repo->transaction(function () use ($orderOutbox)
            {
                $orderId = $orderOutbox->getOrderId();

                $outboxEntries = $this->repo->order_outbox->fetchByOrderIdAndCreatedAt(
                    $orderId, $orderOutbox->getCreatedAt());

                $this->trace->info(
                    TraceCode::ORDER_OUTBOX_FETCH,
                    [
                        Constants::OUBTOX_ENTRIES_COUNT     => count($outboxEntries),
                        Constants::ORDER_OUTBOX             => $orderOutbox
                    ]
                );

                foreach ($outboxEntries as $entry)
                {
                    $update = [
                        Entity::IS_DELETED      => true,
                        Entity::DELETED_AT      => Carbon::now()->getTimestamp(),
                    ];

                    $this->updateOutboxEntry($entry, $update);
                }

                $this->trace->info(
                    TraceCode::ORDER_OUTBOX_SOFT_DELETE_SUCCESS,
                    [
                        Constants::OUBTOX_ENTRIES_COUNT     => count($outboxEntries),
                        Constants::ORDER_OUTBOX             => $orderOutbox,
                        Entity::ORDER_ID                    => $orderId
                    ]
                );
            });

            return true;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::PG_LEDGER_OUTBOX_SOFT_DELETE_FAILURE,
                [
                    Constants::ORDER_OUTBOX             => $orderOutbox,
                    Entity::ORDER_ID                    => $orderOutbox->getOrderId(),
                ]
            );

            $this->trace->count(Metric::ORDER_OUTBOX_SOFT_DELETE_FAILURE, [
                Entity::ORDER_ID           => $orderOutbox->getOrderId(),
            ]);

            return false;
        }
    }

    protected function updateRetryCount(Entity $entry, $retries)
    {
        try
        {
            $this->repo->transaction(function () use ($entry, $retries)
            {
                $update = [
                    Entity::RETRY_COUNT => $retries,
                ];

                $this->updateOutboxEntry($entry, $update);

                $this->trace->info(
                    TraceCode::ORDER_OUTBOX_UPDATE_RETRY_COUNT_SUCCESS,
                    [
                        Entity::ORDER_ID    =>$entry->getOrderId(),
                        Entity::PAYLOAD     => $entry->getPayload(),
                    ]
                );
            });
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::ORDER_OUTBOX_UPDATE_RETRY_COUNT_FAILURE,
                [
                    Entity::ORDER_ID    =>$entry->getOrderId(),
                    Entity::PAYLOAD     => $entry->getPayload(),
                ]
            );
        }
    }

    protected function updateOutboxEntry(Entity $entry, array $update)
    {
        $this->repo->order_outbox->lockForUpdateAndReload($entry);

        $entry->edit($update);

        $this->repo->order_outbox->saveOrFail($entry);
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

                $this->trace->info(
                    TraceCode::ORDER_OUTBOX_SOFT_DELETE_SUCCESS,
                    [
                        Entity::ORDER_ID            => $entry->getOrderId(),
                        Constants::ORDER_OUTBOX     => $entry,
                    ]
                );
            });

            return true;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::ORDER_OUTBOX_SOFT_DELETE_FAILURE,
                [
                    Entity::ORDER_ID        => $entry->getOrderId(),
                    Entity::PAYLOAD         => $entry->getPayload(),
                ]
            );

            $this->trace->count(Metric::ORDER_OUTBOX_SOFT_DELETE_FAILURE, [
                Entity::ORDER_ID           => $entry->getOrderId(),
            ]);

            return false;
        }
    }

    /**
     * Creates partitions till T+6 date
     * Drops the oldest partition with a validation that it should be older than T-7.
     *
     * @return bool[]
     */
    public function createOrderOutboxPartition(): array
    {
        try
        {
            $this->repo->order_outbox->managePartitions();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::TABLE_PARTITION_ERROR);

            return ['success' => false];
        }

        return ['success' => true];
    }
}
