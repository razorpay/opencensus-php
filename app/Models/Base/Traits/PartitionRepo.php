<?php

namespace RZP\Models\Base\Traits;

use DB;
use Carbon\Carbon;
use Database\Connection;
use Illuminate\Database\QueryException;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Constants\Partitions;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\ServerErrorException;
use RZP\Exception\BadRequestException;

trait PartitionRepo
{
    /**
     * @throws ServerErrorException
     * @throws BadRequestException
     */
    public function managePartitions()
    {
        //currently, we are only supporting one level of partitioning
        //and even that should be RANGE only.
        $this->validatePartitioning();

        $this->createNewPartitions();

        $this->dropOldPartitions();
    }

    public function createNewPartitions()
    {
        try
        {
            $allPartitions = $this->getAllExistingPartitions();

            $partitionsData = $this->getNewPartitionsToCreate($allPartitions);

            $partitionNameMax = $this->getPartitionNameFromFormat();

            $connection = $this->getConnectionForPartitionQuery();

            foreach ($partitionsData as $newPartitionName => $newPartitionMaxCreatedAt)
            {
                $query = "ALTER TABLE `" . $this->getTableName() . "`
                REORGANIZE PARTITION `$partitionNameMax` INTO (
                    PARTITION `$newPartitionName` VALUES LESS THAN ($newPartitionMaxCreatedAt),
                    PARTITION `$partitionNameMax` VALUES LESS THAN MAXVALUE
                )";

                $this->trace->info(TraceCode::TABLE_PARTITION_CREATE_QUERY, ['query' => $query]);

                DB::connection($connection)->statement($query);
            }
        }
        catch (QueryException $e)
        {
            // duplicate partition name error
            if (($e->getCode() === 'HY000') and (in_array(1517, $e->errorInfo) === true))
            {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::TABLE_DUPLICATE_PARTITION_ERROR);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    ['table' => $this->getTableName()],
                    'Duplicate partition name');
            }
            else
            {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::TABLE_PARTITION_ERROR);

                throw new Exception\ServerErrorException(
                    ErrorCode::SERVER_ERROR,
                    $e->getCode(),
                    ['table' => $this->getTableName()]
                );
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::TABLE_PARTITION_ERROR);

            throw new Exception\ServerErrorException(
                ErrorCode::SERVER_ERROR,
                $e->getCode(),
                ['table' => $this->getTableName()]
            );
        }
    }

    public function dropOldPartitions()
    {
        try
        {
            $allPartitions = $this->getAllExistingPartitions();

            $oldestPartitions = $this->getOldPartitionsToDrop($allPartitions);

            $connection = $this->getConnectionForPartitionQuery();

            foreach ($oldestPartitions as $partition)
            {
                $query = "ALTER TABLE `" . $this->getTableName() . "` DROP PARTITION `$partition`";

                $this->trace->info(TraceCode::TABLE_PARTITION_DROP_QUERY, ['query' => $query]);

                DB::connection($connection)->statement($query);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::TABLE_PARTITION_ERROR);

            throw new Exception\ServerErrorException(
                ErrorCode::SERVER_ERROR,
                $e->getCode(),
                ['table' => $this->getTableName()]
            );
        }
    }

    protected function getNewPartitionsToCreate($allPartitions) : array
    {
        $newPartitionsData = [];

        $strategy = $this->getPartitionStrategy();

        $currentCountOfFuturePartitions = $this->getCurrentCountOfFuturePartitions($allPartitions);

        $desiredCountOfFuturePartitions = Partitions::$futurePartitionCount[$strategy];

        for ($counter = $currentCountOfFuturePartitions; $counter <= $desiredCountOfFuturePartitions; $counter++)
        {
            $currentDate = Carbon::now(Timezone::IST);

            $date = $this->addDates($strategy, $currentDate, $counter);

            $partitionFormat = $date->format($this->getPartitionNameFormat($strategy));

            $newPartitionToBeCreated = $this->getPartitionNameFromFormat($partitionFormat);

            if ($this->checkIfPartitionAlreadyExists($allPartitions, $newPartitionToBeCreated) === false)
            {
                //add one day/month/year as the max value of this partition would be start of next partition
                $nextDate = $this->addDates($strategy, $date, 1);

                $newPartitionMaxCreatedAt = $this->getStartOfPartitionTimestamp($strategy, $nextDate);

                $newPartitionsData[$newPartitionToBeCreated] = $newPartitionMaxCreatedAt;
            }
        }

        return $newPartitionsData;
    }

    // partitions to be deleted
    protected function getOldPartitionsToDrop($allPartitions) : array
    {
        $oldPartitions = [];

        $currentCountOfOldPartitions = $this->getCurrentCountOfOldPartitions($allPartitions);

        $desiredCountOfOldPartitions = $this->getDesiredOldPartitionsCount();

        $partitionsToDropCount = $currentCountOfOldPartitions - $desiredCountOfOldPartitions;

        if ($partitionsToDropCount > 0)
        {
            $oldPartitionsData = array_slice($allPartitions, 0, $partitionsToDropCount);

            foreach ($oldPartitionsData as $oldPartitionData)
            {
                $oldPartitions[] = $oldPartitionData['name'];
            }
        }

        return $oldPartitions;
    }

    protected function getCurrentCountOfFuturePartitions($allPartitions) : int
    {
        $futurePartitions = $counter = 0;
        $todaysTimestamp = Carbon::now(Timezone::IST)->timestamp;

        //this assumes partitions are sorted on ordinal position
        foreach ($allPartitions as $partition)
        {
            if ($partition['timestamp'] == 'MAXVALUE')
            {
                return 0;
            }

            $counter++;
            if ($partition['timestamp'] > $todaysTimestamp)
            {
                //this is current partition
                //subtracting an extra "1" because we cannot count MAX partition
                $futurePartitions = count($allPartitions) - $counter - 1;
                break;
            }
        }

        return $futurePartitions;
    }

    protected function getCurrentCountOfOldPartitions($allPartitions) : int
    {
        $oldPartitions = $counter = 0;
        $todaysTimestamp = Carbon::now(Timezone::IST)->timestamp;

        //this assumes partitions are sorted on ordinal position
        foreach ($allPartitions as $partition)
        {
            if ($partition['timestamp'] == 'MAXVALUE' || $partition['timestamp'] > $todaysTimestamp)
            {
                //this is current partition
                $oldPartitions = $counter;
                break;
            }

            $counter++;
        }

        return $oldPartitions;
    }

    /**
     * @throws BadRequestException
     */
    protected function validatePartitioning() : void
    {
        $this->verifyTableIsRangePartitioned();

        $this->validatePartitioningStrategy();
    }

    /**
     * @throws BadRequestException
     */
    protected function validatePartitioningStrategy() : void
    {
        $strategy = $this->getPartitionStrategy();

        if (in_array($strategy, array_keys(Partitions::$futurePartitionCount)) === false)
        {
            $this->trace->error(
                TraceCode::INVALID_PARTITIONING_TYPE,
                [
                    'table' => $this->getTableName(),
                    'strategy' => $strategy,
                ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [
                    'table' => $this->getTableName(),
                ],
                'invalid partitioning type.');
        }
    }

    /**
     * @throws BadRequestException
     */
    protected function verifyTableIsRangePartitioned() : void
    {
        $allPartitions = $this->getAllExistingPartitions();

        if (count($allPartitions) <= 0)
        {
            $this->trace->error(
                TraceCode::NO_RANGE_PARTITIONING,
                [
                    'table' => $this->getTableName(),
                    'error' => 'No range partitioning found'
                ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [
                    'table' => $this->getTableName(),
                ],
                'no range partitioning found.');
        }
    }

    protected function getAllExistingPartitions() : array
    {
        $connection = $this->getConnectionForPartitionQuery();

        $db = DB::connection($connection)->getDatabaseName();

        $partitions = DB::select(
            DB::RAW(
                "SELECT * FROM information_schema.PARTITIONS WHERE PARTITION_METHOD = 'RANGE'
                                              AND TABLE_SCHEMA = '$db'
                                              AND TABLE_NAME = '" . $this->getTableName() . "'
                                              ORDER BY PARTITION_ORDINAL_POSITION ASC"
            )
        );

        $allPartitions = [];
        foreach ($partitions as $partition)
        {
            $allPartitions[] = [
                'name' => $partition->PARTITION_NAME,
                'timestamp' => $partition->PARTITION_DESCRIPTION,
            ];
        }

        return $allPartitions;
    }

    protected function checkIfPartitionAlreadyExists($allPartitions, $newPartitionToBeCreated) : bool
    {
        foreach ($allPartitions as $partition) {
            if ($partition['name'] == $newPartitionToBeCreated) {
                return true;
            }
        }

        return false;
    }

    protected function getPartitionNameFormat($strategy) : string
    {
        switch ($strategy)
        {
            case Partitions::DAILY: return 'dMY';

            case Partitions::MONTHLY: return 'MY';

            case Partitions::YEARLY: return 'Y';
        }

        return '';
    }

    protected function addDates($strategy, $date, $count)
    {
        switch ($strategy)
        {
            case Partitions::DAILY: return $date->addDays($count);

            case Partitions::MONTHLY: return $date->addMonths($count);

            case Partitions::YEARLY: return $date->addYears($count);
        }

        return null;
    }

    protected function getStartOfPartitionTimestamp($strategy, $date) : int
    {
        switch ($strategy)
        {
            case Partitions::DAILY: return strval($date->startOfDay()->timestamp);

            case Partitions::MONTHLY: return strval($date->startOfMonth()->timestamp);

            case Partitions::YEARLY: return strval($date->startOfYear()->timestamp);
        }

        return 0;
    }

    protected function getConnectionForPartitionQuery() : string
    {
        $mode = ($this->mode ?? $this->app['rzp.mode']) ?? Mode::LIVE;

        return (($mode === Mode::TEST) ?
            Connection::TABLE_PARTITION_TEST : Connection::TABLE_PARTITION_LIVE);
    }

    protected function getPartitionNameFromFormat($format = 'Max') : string
    {
        return 'p_' . $this->getTableName() . '_' . $format;
    }
}
