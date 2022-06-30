<?php

namespace RZP\Models\FundAccount\Validation;

use Carbon\Carbon;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Balance;
use RZP\Models\FundAccount\Type;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::FUND_ACCOUNT_VALIDATION;

    protected $expands = [
        Entity::FUND_ACCOUNT,
    ];

    public function getFundAccountValidationsToFail(array $favIds): Base\PublicCollection
    {
        return $this->newQuery()
            ->whereIn(Entity::ID, $favIds)
            ->where(Entity::STATUS, "=" , Status::CREATED)
            ->where(Entity::FUND_ACCOUNT_TYPE, "=", Type::BANK_ACCOUNT)
            ->get();
    }

    /**
     * calculates the sum of `fee` and `tax` of all the fund_account_validations created in a given time frame
     *
     * select SUM(tax) AS tax,SUM(fees) AS fee
     * from `fund_account_validations`
     * where `fund_account_validations`.`merchant_id` = ?
     * and `fund_account_validations`.`balance_id` = ?
     * and `fund_account_validations`.`created_at` between ? and ?
     *
     * @param string $merchantId
     * @param string $balanceId
     * @param int    $startTime
     * @param int    $endTime
     *
     * @return mixed
     */
    public function fetchFeesAndTaxForFAVsForGivenBalanceId(
        string $merchantId,
        string $balanceId,
        int $startTime,
        int $endTime)
    {
        $favsBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $favsCreatedAtColumn = $this->dbColumn(Entity::CREATED_AT);

        return $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
                    ->selectRaw(
                        'SUM(' . Entity::TAX .') AS tax,
                         SUM(' . Entity::FEES . ') AS fee')
                    ->merchantId($merchantId)
                    ->where($favsBalanceIdColumn, $balanceId)
                    ->whereBetween($favsCreatedAtColumn, [$startTime, $endTime])
                    ->first();
    }

    public function fetchCompletedFAVByAccountNumber(string $accountNumber, int $startTime)
    {
        $fundAccountId = $this->repo->fund_account->dbColumn(\RZP\Models\FundAccount\Entity::ID);
        $fundAccountIdInFAV = $this->dbColumn(Entity::FUND_ACCOUNT_ID);

        $bankAccountIdInFundAccount = $this->repo->fund_account->dbColumn(\RZP\Models\FundAccount\Entity::ACCOUNT_ID);
        $bankAccountId = $this->repo->bank_account->dbColumn(\RZP\Models\BankAccount\Entity::ID);

        $createdAtInFAV = $this->dbColumn(Entity::CREATED_AT);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->leftJoin(Table::FUND_ACCOUNT, $fundAccountIdInFAV, '=', $fundAccountId)
            ->leftJoin(Table::BANK_ACCOUNT, $bankAccountIdInFundAccount, '=', $bankAccountId)
            ->where(Entity::FUND_ACCOUNT_TYPE, '=', Type::BANK_ACCOUNT)
            ->where(Entity::STATUS, '=', Status::COMPLETED)
            ->where(Entity::ACCOUNT_STATUS, '=', AccountStatus::ACTIVE)
            ->where($createdAtInFAV, '>', $startTime);

        $bankAccountNumberCol = $this->repo->bank_account->dbColumn(\RZP\Models\BankAccount\Entity::ACCOUNT_NUMBER);

        return $query->where($bankAccountNumberCol, $accountNumber)
                     ->orderBy($createdAtInFAV, 'desc')
                     ->first();
    }

    public function fetchFAVWithExpands(string $id, array $expands) {
        return $this->newQuery()
                    ->with($expands)
                    ->where(Entity::ID, $id)
                    ->first();
    }

    /**
     * This will fetch all FAVs in created state which are created in the last 24 hours
     * after 05-02-2022 and where transaction_id is null.
     * @param int $days
     * @param int $limit
     * @return mixed
     */
    public function fetchCreatedFAVAndTxnIdNullBetweenTimestamp(int $days, int $limit)
    {
        $currentTime = Carbon::now(Timezone::IST)->subMinutes(15)->subDays($days);
        $currentTimeStamp = $currentTime->getTimestamp();

        $lastTimestamp = $currentTime->subDay()->getTimestamp();
        $txnIdFillingTimestamp = Carbon::createFromFormat('d-m-Y', '05-02-2022', Timezone::IST)->getTimestamp();

        if ($lastTimestamp < $txnIdFillingTimestamp)
        {
            $lastTimestamp = $txnIdFillingTimestamp;
        }

        $balanceIdColumn            = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn          = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn   = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);

        $favTransactionIdColumn   = $this->repo->fund_account_validation->dbColumn(Entity::TRANSACTION_ID);
        $favStatusColumn          = $this->repo->fund_account_validation->dbColumn(Entity::STATUS);
        $favBalanceIdColumn       = $this->repo->fund_account_validation->dbColumn(Entity::BALANCE_ID);
        $favFundAccountTypeColumn = $this->repo->fund_account_validation->dbColumn(Entity::FUND_ACCOUNT_TYPE);
        $favCreatedAtColumn       = $this->dbColumn(Entity::CREATED_AT);

        $favAttrs = $this->dbColumn('*');

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $favBalanceIdColumn)
                    ->select($favAttrs)
                    ->where($favStatusColumn, '=', Status::CREATED)
                    ->where($favFundAccountTypeColumn, '=', Type::BANK_ACCOUNT)
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', Balance\AccountType::SHARED)
                    ->whereNull($favTransactionIdColumn)
                    ->whereBetween($favCreatedAtColumn, [$lastTimestamp, $currentTimeStamp])
                    ->limit($limit)
                    ->get();
    }

    /**
     * This will fetch all favs in created state where id is in the given list of ids
     * and where transaction_id is null.
     * @param array $ids
     * @return mixed
     */
    public function fetchCreatedFAVWhereTxnIdNullAndIdsIn(array $ids)
    {
        $balanceIdColumn            = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn          = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn   = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);

        $favIdColumn              = $this->repo->fund_account_validation->dbColumn(Entity::ID);
        $favTransactionIdColumn   = $this->repo->fund_account_validation->dbColumn(Entity::TRANSACTION_ID);
        $favBalanceIdColumn       = $this->repo->fund_account_validation->dbColumn(Entity::BALANCE_ID);
        $favFundAccountTypeColumn = $this->repo->fund_account_validation->dbColumn(Entity::FUND_ACCOUNT_TYPE);

        $favAttrs = $this->dbColumn('*');

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $favBalanceIdColumn)
                    ->select($favAttrs)
                    ->where($favFundAccountTypeColumn, '=', Type::BANK_ACCOUNT)
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', Balance\AccountType::SHARED)
                    ->whereNull($favTransactionIdColumn)
                    ->whereIn($favIdColumn, $ids)
                    ->get();
    }
}
