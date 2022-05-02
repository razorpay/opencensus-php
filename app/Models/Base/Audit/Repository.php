<?php


namespace RZP\Models\Base\Audit;

use DB;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use Database\Connection;
use RZP\Trace\TraceCode;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $mode;

    const PARTITION_NAME_PREFIX = 'p_audit_info_';

    const PARTITION_NAME = "partition_name";

    protected $entity = 'audit_info';


    /*
        There will be a total of 14 partitions for this table always, 6 of which will be for future dates.
        For e.g. if today is 8th May 2021 and the cron is yet to get triggered, 14 partitions will already be there as follows

        1) p_audit_info_01May2021 VALUES LESS THAN (1619913600)
        2) p_audit_info_02May2021 VALUES LESS THAN (1620000000)
        3) p_audit_info_03May2021 VALUES LESS THAN (1620086400)
        .
        .
        8) p_audit_info_08May2021 VALUES LESS THAN (1620518400) <<--we are here, cron is yet to run
        .
        .
        13) p_audit_info_13May2021 VALUES LESS THAN (1620950400)
        14) p_audit_info_Max VALUES LESS THAN (1619913600)

        When the cron runs, it will reorganize audit_info_Max into p_audit_info_14May2021 and p_audit_info_Max
        and the cron will drop the oldest partition i.e. p_audit_info_01May2021
    */

    public function createPartition()
    {
        $query = "ALTER TABLE " . $this->entity . " REORGANIZE PARTITION p_audit_info_Max into ( partition " . $this->getNewestPartitionName() . " values less than (" . $this->getNewestPartitionMaxCreatedAt() . "), partition p_audit_info_Max values less than MAXVALUE)";

        $this->trace->info(TraceCode::AUDIT_INFO_PARTITION_CREATE_QUERY, ['query' => $query]);

        $connection = $this->getConnectionForPartitionQuery();

        DB::connection($connection)->statement($query);

    }

    public function dropPartition()
    {
        $query = "ALTER TABLE " . $this->entity . " drop partition " . $this->getOldestPartitionName();

        $this->trace->info(TraceCode::AUDIT_INFO_PARTITION_DROP_QUERY, ['query' => $query]);

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
                TraceCode::AUDIT_INFO_PARTITION_ERROR,
                []);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, 'No partition to drop');
        }

        $this->trace->info(
            TraceCode::AUDIT_INFO_PARTITION_TO_DROP,
            ['partition_being_dropped' => $partitions]);

        return $partitions[0]->partition_name;
    }

    protected function getConnectionForPartitionQuery()
    {
        $connection =  Connection::AUDIT_INFO_PARTITION_LIVE ;

        return $connection;
    }
}
