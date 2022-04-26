<?php

namespace RZP\Models\PayoutOutbox;

use Carbon\Carbon;
use Database\Connection;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\PayoutOutbox\Constants as PayoutOutboxConstants;
use RZP\Trace\TraceCode;
use RZP\Exception;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'payout_outbox';

    protected $mode;

    const PARTITION_NAME_PREFIX = 'p_payout_outbox_';

    const PARTITION_NAME = "partition_name";

    /**
     * This function checks for deleted at status
     *
     * @param string $id
     * @param string $merchantId
     * @param string $userId
     * @return mixed
     */
    public function fetchPayoutInOutboxById(string $id, string $merchantId, string $userId)
    {
        $mode = $this->app['rzp.mode'];
        return $this->newQueryWithConnection($mode)
                    ->useWritePdo()
                    ->where(Entity::ID, $id)
                    ->merchantId($merchantId)
                    ->where(Entity::USER_ID, $userId)
                    ->whereNull(Entity::DELETED_AT)
                    ->where(Entity::EXPIRES_AT, ">=", Carbon::now()->timestamp)
                    ->first();
    }

    /**
     * Returns the orphan payout - The payout for which no action was taken
     * Here, we get only orphan payout for a time range, i.e payouts which are orphaned before 15 mins or 30 mins
     *
     * select count(*) from payout_outbox where deleted_at = null and expires_at BETWEEN NOW()-ORPHAN_PAYOUT_RANGE_IN_MINUTES AND NOW();
     *
     * @return mixed
     */
    public function getOrphanedPayoutsFromOutbox() {
        $mode = $this->app['rzp.mode'];
        return $this->newQueryWithConnection($mode)
            ->useWritePdo()
            ->select(Entity::ID)
            ->whereNull(Entity::DELETED_AT)
            ->whereBetween(Entity::EXPIRES_AT, [Carbon::now()->subMinutes(PayoutOutboxConstants::ORPHAN_PAYOUT_RANGE_IN_MINUTES)->timestamp, Carbon::now()->timestamp])
            ->get();
    }

    /**
     * Deletes the orphan payout - The payout for which no action was taken
     *
     * @param $payoutIds
     */
    public function deleteOrphanedPayouts($payoutIds) {
        foreach ($payoutIds as $payoutId) {
            $orphanPayout = $this->fetchPayoutById($payoutId);

            if ($orphanPayout == null || $orphanPayout->trashed()) {
                $this->trace->info(TraceCode::BAD_REQUEST_INVALID_ORPHAN_PAYOUT_ID, ['id' => $payoutId]);
            }

            $this->repo->deleteOrFail($orphanPayout);

            $this->trace->info(TraceCode::DELETED_ORPHAN_PAYOUT, $payoutId);
        }
    }

    /**
     * Gets entry from payout_outbox by id
     *
     * @param string $id
     * @return mixed
     */
    public function fetchPayoutById(string $id)
    {
        $mode = $this->app['rzp.mode'];
        return $this->newQueryWithConnection($mode)
            ->useWritePdo()
            ->where(Entity::ID, $id)
            ->first();
    }

    public function createPayoutInOutbox($input): array
    {
        $payout = $this->core->create($input);

        return $payout->toArrayPublic();
    }

    /*
        There will be a total of 14 partitions for this table always, 6 of which will be for future dates.
        For e.g. if today is 8th May 2021 and the cron is yet to get triggered, 14 partitions will already be there as follows

        1) p_payout_outbox_01May2021 VALUES LESS THAN (1619913600)
        2) p_payout_outbox_02May2021 VALUES LESS THAN (1620000000)
        3) p_payout_outbox_03May2021 VALUES LESS THAN (1620086400)
        .
        .
        8) p_payout_outbox_08May2021 VALUES LESS THAN (1620518400) <<--we are here, cron is yet to run
        .
        .
        13) p_payout_outbox_13May2021 VALUES LESS THAN (1620950400)
        14) p_payout_outbox_Max VALUES LESS THAN (1619913600)

        When the cron runs, it will reorganize p_payout_outbox_Max into p_payout_outbox_14May2021 and p_payout_outbox_Max
        and the cron will drop the oldest partition i.e. p_payout_outbox_01May2021
    */

    public function createPartition()
    {
        $query = "ALTER TABLE " . $this->entity . " REORGANIZE PARTITION p_payout_outbox_Max into ( partition " . $this->getNewestPartitionName() . " values less than (" . $this->getNewestPartitionMaxCreatedAt() . "), partition p_payout_outbox_Max values less than MAXVALUE)";

        $this->trace->info(TraceCode::PAYOUT_OUTBOX_PARTITION_CREATE_QUERY, ['query' => $query]);

        $connection = $this->getConnectionForPartitionQuery();

        DB::connection($connection)->statement($query);
    }

    public function dropPartition()
    {
        $query = "ALTER TABLE " . $this->entity . " drop partition " . $this->getOldestPartitionName();

        $this->trace->info(TraceCode::PAYOUT_OUTBOX_PARTITION_DROP_QUERY, ['query' => $query]);

        $connection = $this->getConnectionForPartitionQuery();

        DB::connection($connection)->statement($query);
    }

    // partition to be created
    protected function getNewestPartitionName()
    {
        $partition = Carbon::now()->addDays(6)->format('dMY');

        return self::PARTITION_NAME_PREFIX . $partition;
    }

    protected function getNewestPartitionMaxCreatedAt()
    {
        return strval(Carbon::now()->addDays(7)->startOfDay()->timestamp);
    }

    // partition to be deleted
    protected function getOldestPartitionName()
    {
        $connection = $this->getConnectionForPartitionQuery();

        $db = DB::connection($connection)->getDatabaseName();

        $partitions = DB::select(DB::RAW("select partition_name as partition_name from information_schema.partitions where table_schema='$db' and table_name='$this->entity' and partition_ordinal_position=1"));

        // This will never happen though, we will always have one partition with ordinal position 1 as we are dropping only after creating new partition
        if (count($partitions) !== 1)
        {
            $this->trace->error(
                TraceCode::PAYOUT_OUTBOX_PARTITION_ERROR,
                []);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, 'No partition to drop');
        }

        $this->trace->info(
            TraceCode::PAYOUT_OUTBOX_PARTITION_TO_DROP,
            ['partition_being_dropped' => $partitions]);

        return $partitions[0]->partition_name;
    }

    protected function getConnectionForPartitionQuery()
    {
        $connection = ($this->mode === Mode::LIVE) ? Connection::PAYOUT_OUTBOX_PARTITION_LIVE : Connection::PAYOUT_OUTBOX_PARTITION_TEST;

        return $connection;
    }
}
