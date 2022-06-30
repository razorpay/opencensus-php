<?php

namespace RZP\Models\Adjustment;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Balance;

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


}
