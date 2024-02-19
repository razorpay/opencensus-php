<?php

namespace RZP\Models\Adjustment;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Adjustment;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Balance;
use RZP\Base\ConnectionType;

class Repository extends Base\Repository
{
    protected $entity = 'adjustment';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::TRANSACTION_ID  => 'sometimes|alpha_dash',
        Entity::SETTLEMENT_ID   => 'sometimes|alpha_dash'
    );

    public function findAdjustmentByDescription($description, $merchantId)
    {
        return $this->newQuery()
            ->where(Entity::DESCRIPTION, '=', $description)
            ->merchantId($merchantId)
            ->exists();
    }

    /**
     * @param $id
     * @return string
     */
    public function findBalanceIdById(string $id)
    {
        $balanceId = $this->repo->adjustment->dbColumn(Adjustment\Entity::BALANCE_ID);

        $result = $this->newQuery()
                       ->select($balanceId)
                       ->where(Entity::ID, '=', $id)
                       ->get();

        if (empty($result) === false)
            return $result->first()[Adjustment\Entity::BALANCE_ID];

        return '';
    }

    /**
     * @param $id
     * @return string
     */
    public function findChannelById(string $id)
    {
        $channel = $this->repo->adjustment->dbColumn(Adjustment\Entity::CHANNEL);

        $result = $this->newQuery()
                       ->select($channel)
                       ->where(Entity::ID, '=', $id)
                       ->get();

        if (empty($result) === false)
            return $result->first()[Adjustment\Entity::CHANNEL];

        return '';
    }

    /**
     * @param $entityId
     * @param $entityType
     * @param $merchantId
     * @return mixed
     */
    public function findAdjustmentByEntityIdAndEntityType($entityId, $entityType, $merchantId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::ENTITY_ID, '=', $entityId)
            ->where(Entity::ENTITY_TYPE, '=', $entityType)
            ->merchantId($merchantId)
            ->get();
    }

    /**
     * This will fetch all adjustments in created state which are created in the last 24 hours
     * after 05-02-2022 and where transaction_id is null.
     * @param int $days
     * @param int $limit
     * @return mixed
     */
    public function fetchCreatedAdjustmentAndTxnIdNullBetweenTimestamp(int $days, int $limit)
    {
        $currentTime = Carbon::now(Timezone::IST)->subMinutes(15)->subDays($days);
        $currentTimeStamp = $currentTime->getTimestamp();

        $lastTimestamp = $currentTime->subDay()->getTimestamp();
        $txnIdFillingTimestamp = Carbon::createFromFormat('d-m-Y', '05-02-2022', Timezone::IST)->getTimestamp();

        if ($lastTimestamp < $txnIdFillingTimestamp)
        {
            $lastTimestamp = $txnIdFillingTimestamp;
        }

        $balanceIdColumn          = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn        = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);

        $adjTransactionIdColumn = $this->repo->adjustment->dbColumn(Entity::TRANSACTION_ID);
        $adjStatusColumn        = $this->repo->adjustment->dbColumn(Entity::STATUS);
        $adjBalanceIdColumn     = $this->repo->adjustment->dbColumn(Entity::BALANCE_ID);
        $adjCreatedAtColumn     = $this->dbColumn(Entity::CREATED_AT);

        $adjAttrs = $this->dbColumn('*');

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $adjBalanceIdColumn)
                    ->select($adjAttrs)
                    ->where($adjStatusColumn, '=', Status::CREATED)
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', Balance\AccountType::SHARED)
                    ->whereNull($adjTransactionIdColumn)
                    ->whereBetween($adjCreatedAtColumn, [$lastTimestamp, $currentTimeStamp])
                    ->limit($limit)
                    ->get();
    }

    /**
     * This will fetch all adjustments in created state where id is in the given list of ids
     * and where transaction_id is null.
     * @param array $ids
     * @return mixed
     */
    public function fetchCreatedAdjustmentWhereTxnIdNullAndIdsIn(array $ids)
    {
        $balanceIdColumn          = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn        = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);

        $adjIdColumn            = $this->repo->adjustment->dbColumn(Entity::ID);
        $adjTransactionIdColumn = $this->repo->adjustment->dbColumn(Entity::TRANSACTION_ID);
        $adjBalanceIdColumn     = $this->repo->adjustment->dbColumn(Entity::BALANCE_ID);

        $adjAttrs = $this->dbColumn('*');

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $adjBalanceIdColumn)
                    ->select($adjAttrs)
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', Balance\AccountType::SHARED)
                    ->whereNull($adjTransactionIdColumn)
                    ->whereIn($adjIdColumn, $ids)
                    ->get();
    }

    public function fetchAdjustmentsWithMissingTransactions($from, $to, $status, $transactorIds = [])
    {
        if(count($transactorIds) === 0)
        {
            return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                ->where(Adjustment\Entity::CREATED_AT, '>=', $from)
                ->where(Adjustment\Entity::CREATED_AT, '<=', $to)
                ->where(Adjustment\Entity::STATUS, '=', $status)
                ->where(Adjustment\Entity::TRANSACTION_ID, '=', null)
                ->get();
        }

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->where(Adjustment\Entity::CREATED_AT, '>=', $from)
            ->where(Adjustment\Entity::CREATED_AT, '<=', $to)
            ->where(Adjustment\Entity::STATUS, '=', $status)
            ->where(Adjustment\Entity::TRANSACTION_ID, '=', null)
            ->whereIn(Adjustment\Entity::ID, $transactorIds)
            ->get();
    }

}
