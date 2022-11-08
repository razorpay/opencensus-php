<?php

namespace RZP\Models\Checkout\Order;

use App;
use DB;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Base\Repository as BaseRepository;
use RZP\Constants\Mode;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use Database\Connection;
use RZP\Trace\TraceCode;
use Illuminate\Database\QueryException;

class Repository extends BaseRepository
{
    protected $entity = 'checkout_order';

    protected $mode;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->mode = $app['rzp.mode'];

        parent::__construct();
    }

    protected const PARTITION_NAME_PREFIX = 'p_checkout_orders_';

    /**
     * There will be a total of 14 partitions for this table always,6 of which will be for future dates.
     * For e.g. if today is 8th May 2021 and the cron is yet to get triggered,
     * 14 partitions will already be there as follows:
     *
     * 1) p_checkout_orders_01May2021 VALUES LESS THAN (1619913600)
     * 2) p_checkout_orders_02May2021 VALUES LESS THAN (1620000000)
     * 3) p_checkout_orders_03May2021 VALUES LESS THAN (1620086400)
     * .
     * .
     * 8) p_checkout_orders_08May2021 VALUES LESS THAN (1620518400) <<-- we are here, cron is yet to run
     * .
     * .
     * 13) p_checkout_orders_13May2021 VALUES LESS THAN (1620950400)
     * 14) p_checkout_orders_Max VALUES LESS THAN MAXVALUE
     *
     * When the cron runs, it will try to create the partitions for the next 7 days
     * and the cron will drop the oldest partition i.e. p_checkout_orders_01May2021
     *
     * Query being used in Create Partition (T+6) -
     * ALTER TABLE
     *   checkout_orders REORGANIZE PARTITION p_checkout_orders_Max into
     *   (
     *     partition p_checkout_orders_26Sep2022 values less than (1664236800),
     *     partition p_checkout_orders_Max values less than MAXVALUE
     *   )
     *
     * Run reorganize commands for the next 5days as well to ensure all the next 5days partitions exist
     *
     * @return void
     */
    public function createPartition(): void
    {
        $connection = $this->getConnectionForPartitionQuery();

        try
        {
            // Create T+6 Partition
            $sql = "ALTER TABLE %s REORGANIZE PARTITION p_checkout_orders_Max into ( partition %s "
                . "values less than (%d), partition p_checkout_orders_Max values less than MAXVALUE)";

            $query = sprintf(
                $sql,
                Table::CHECKOUT_ORDER,
                $this->getFuturePartitionName(6),
                $this->getFuturePartitionCreatedAt(6)
            );

            $this->trace->info(TraceCode::CHECKOUT_ORDERS_PARTITION_CREATE_QUERY, ['query' => $query]);

            DB::connection($connection)->statement($query);
        }
        catch (QueryException $e)
        {
            if (($e->getCode() !== 'HY000') || (in_array(1517, $e->errorInfo) === false))
            {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::CHECKOUT_ORDERS_PARTITION_ERROR);

                throw $e;
            }

            // duplicate partition name error
            $this->trace->traceException($e, Trace::INFO, TraceCode::CHECKOUT_ORDERS_PARTITION_EXISTS_ALREADY);
        }

        $this->createMissingFuturePartitions();
    }

    /**
     * Drops the oldest partition with a validation that it should be older than T-7.
     *
     * @return void
     * @throws Exception\BadRequestException
     */
    public function dropPartition(): void
    {
        $oldestPartitionDetails = $this->getOldestPartitionDetails();

        $oldestPartitionTimestamp = (int) $oldestPartitionDetails->partition_description;

        $this->ensurePartitionOlderThanSevenDays($oldestPartitionTimestamp);

        $oldestPartitionName = $oldestPartitionDetails->partition_name;

        $this->trace->info(
            TraceCode::CHECKOUT_ORDERS_PARTITION_TO_DROP,
            ['partition_being_dropped' => $oldestPartitionDetails]
        );

        $sql = "ALTER TABLE %s DROP PARTITION %s";

        $query = sprintf(
            $sql,
            Table::CHECKOUT_ORDER,
            $oldestPartitionName
        );

        $this->trace->info(TraceCode::CHECKOUT_ORDERS_PARTITION_DROP_QUERY, ['query' => $query]);

        $connection = $this->getConnectionForPartitionQuery();

        DB::connection($connection)->statement($query);
    }

    protected function getFuturePartitionName(int $days): string
    {
        $partition = Carbon::now()->addDays($days)->format('dMY');

        return self::PARTITION_NAME_PREFIX . $partition;
    }

    protected function getFuturePartitionCreatedAt(int $days): string
    {
        return (string)Carbon::now()->addDays($days + 1)->startOfDay()->timestamp;
    }

    /**
     * Query being used to get the oldest partition details:
     *  SELECT PARTITION_NAME AS partition_name, PARTITION_DESCRIPTION AS partition_description
     *  FROM
     *    information_schema.partitions
     *  WHERE
     *    table_schema = 'api_test'
     *    AND table_name = 'checkout_orders'
     *    AND partition_ordinal_position = 1
     */
    protected function getOldestPartitionDetails()
    {
        $connection = $this->getConnectionForPartitionQuery();

        $db = DB::connection($connection)->getDatabaseName();

        $sql = "SELECT PARTITION_NAME AS partition_name, PARTITION_DESCRIPTION AS partition_description FROM information_schema.partitions "
            . " WHERE table_schema='%s' AND table_name='%s' AND partition_ordinal_position=1";

        $query = sprintf($sql, $db, Table::CHECKOUT_ORDER);

        $partitions = DB::select(DB::RAW($query));

        return $partitions[0];
    }

    /**
     * Validates that the partition should be older than T-7
     *
     * @param  int  $partitionTimestamp Partition created_at range max timestamp
     *
     * @return void
     *
     * @throws Exception\BadRequestException
     */
    protected function ensurePartitionOlderThanSevenDays(int $partitionTimestamp): void
    {
        $sevenDaysAgo = Carbon::today()->subDays(7)->getTimestamp();

        // For ex: 5 days ago > 7 days ago timestamp, in that case throw an exception
        if ($partitionTimestamp > $sevenDaysAgo)
        {
            $this->trace->error(
                TraceCode::CHECKOUT_ORDERS_LOW_PARTITION_COUNT_ERROR,
                [
                    'message' => 'Oldest partition is less than 7 days old',
                    'partition_timestamp' => $partitionTimestamp,
                ]
            );

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, 'Low partition count');
        }
    }

    protected function getConnectionForPartitionQuery(): string
    {
        return ($this->mode === Mode::LIVE) ?
            Connection::CHECKOUT_ORDERS_PARTITION_LIVE :
            Connection::CHECKOUT_ORDERS_PARTITION_TEST;
    }

    /**
     * Ensuring T+5, T+4, T+3, T+2, T+1 Partitions exist
     *
     * @return void
     */
    protected function createMissingFuturePartitions(): void
    {
        $connection = $this->getConnectionForPartitionQuery();

        for ($days = 5; $days >= 1; $days--)
        {
            try
            {
                $sql = "ALTER TABLE %s REORGANIZE PARTITION %s into ( partition %s "
                    . "values less than (%d), partition %s values less than (%d))";

                $query = sprintf(
                    $sql,
                    Table::CHECKOUT_ORDER,
                    $this->getFuturePartitionName($days + 1),
                    $this->getFuturePartitionName($days),
                    $this->getFuturePartitionCreatedAt($days),
                    $this->getFuturePartitionName($days + 1),
                    $this->getFuturePartitionCreatedAt($days + 1)
                );

                $this->trace->info(TraceCode::CHECKOUT_ORDERS_PARTITION_CREATE_QUERY, ['query' => $query]);

                DB::connection($connection)->statement($query);
            }
            catch (QueryException $e)
            {
                // duplicate partition name error
                if (($e->getCode() === 'HY000') and (in_array(1517, $e->errorInfo) === true))
                {
                    $this->trace->traceException($e, Trace::INFO, TraceCode::CHECKOUT_ORDERS_PARTITION_EXISTS_ALREADY);

                    continue;
                }

                $this->trace->traceException($e, Trace::ERROR, TraceCode::CHECKOUT_ORDERS_PARTITION_ERROR);

                throw $e;
            }
        }
    }
}
