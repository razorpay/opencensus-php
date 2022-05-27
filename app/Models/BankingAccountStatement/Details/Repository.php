<?php

namespace RZP\Models\BankingAccountStatement\Details;

use RZP\Constants;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BANKING_ACCOUNT_STATEMENT_DETAILS;

    public function fetchAccountStatementByBalance(string $balanceId) : Entity{
        return $this->newQuery()
            ->where(Entity::BALANCE_ID, '=', $balanceId)
            ->first();
    }

    public function fetchByAccountNumberAndChannel(string $accountNumber, string $channel)
    {
        $accountNumberColumn = $this->dbColumn(Entity::ACCOUNT_NUMBER);

        $channelColumn = $this->dbColumn(Entity::CHANNEL);

        $BASDetailsDbColumns = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($BASDetailsDbColumns)
                    ->where($accountNumberColumn, '=', $accountNumber)
                    ->where($channelColumn, '=', $channel)
                    ->first();
    }

    public function fetchAccountNumbersByChannelOrderByLastStatementAttemptAt(string $channel, string $accountType = AccountType::DIRECT)
    {
        $channelColumn = $this->dbColumn(Entity::CHANNEL);

        $statusColumn = $this->dbColumn(Entity::STATUS);

        $accountTypeColumn = $this->dbColumn(Entity::ACCOUNT_TYPE);

        $basDetailsAttr = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($basDetailsAttr)
                    ->where($channelColumn, '=', $channel)
                    ->where($statusColumn, '=', Status::ACTIVE)
                    ->where($accountTypeColumn, '=', $accountType)
                    ->oldest(Entity::LAST_STATEMENT_ATTEMPT_AT)
                    ->get();
    }

    public function fetchByChannelOrderByBalanceLastFetchedAt(string $channel, string $accountType = AccountType::DIRECT)
    {
        $channelColumn = $this->dbColumn(Entity::CHANNEL);

        $statusColumn = $this->dbColumn(Entity::STATUS);

        $accountTypeColumn = $this->dbColumn(Entity::ACCOUNT_TYPE);

        $basDetailsAttr = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($basDetailsAttr)
                    ->where($channelColumn, '=', $channel)
                    ->where($statusColumn, '=', Status::ACTIVE)
                    ->where($accountTypeColumn, '=', $accountType)
                    ->oldest(Entity::BALANCE_LAST_FETCHED_AT)
                    ->get();
    }

    public function getMerchantIdsByChannel($channel, $limit)
    {
        $channelColumn                 = $this->dbColumn(Entity::CHANNEL);
        $merchantIdColumn              = $this->dbColumn(Entity::MERCHANT_ID);

        $accountTypeColumn = $this->dbColumn(Entity::ACCOUNT_TYPE);

        $basDetailsAttr = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($basDetailsAttr)
                    ->where($channelColumn, '=', $channel)
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where($accountTypeColumn, '=', AccountType::DIRECT)
                    ->oldest(Entity::BALANCE_LAST_FETCHED_AT)
                    ->limit($limit)
                    ->pluck($merchantIdColumn);
    }

    public function getDirectBasDetailEntityByMerchantIdAndChannel($merchantId, string $channel)
    {
        $basDetailsBalanceIdColumn     = $this->dbColumn(Entity::BALANCE_ID);
        $channelColumn                 = $this->dbColumn(Entity::CHANNEL);
        $merchantIdColumn              = $this->dbColumn(Entity::MERCHANT_ID);
        $statusColumn                  = $this->dbColumn(Entity::STATUS);

        $balanceIdColumn                = $this->repo->balance->dbColumn(Entity::ID);
        $accountTypeColumn              = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ACCOUNT_TYPE);
        $balanceTypeColumn              = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $basDetailsAttr = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($basDetailsAttr)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->join(Constants\Table::BALANCE, $basDetailsBalanceIdColumn, '=', $balanceIdColumn)
                    ->where($accountTypeColumn, '=', Merchant\Balance\AccountType::DIRECT)
                    ->where($balanceTypeColumn, '=', Merchant\Balance\Type::BANKING)
                    ->where($channelColumn, '=', $channel)
                    ->where($statusColumn, '=', Status::ACTIVE)
                    ->first();
    }

    public function getDirectBasDetailEntityByMerchantAndBalanceId($merchantId, $balanceId)
    {
        $basDetailsBalanceIdColumn     = $this->dbColumn(Entity::BALANCE_ID);
        $channelColumn                 = $this->dbColumn(Entity::CHANNEL);
        $merchantIdColumn              = $this->dbColumn(Entity::MERCHANT_ID);

        $balanceIdColumn                = $this->repo->balance->dbColumn(Entity::ID);
        $accountTypeColumn              = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ACCOUNT_TYPE);
        $balanceTypeColumn              = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $basDetailsAttr = $this->dbColumn('*');

        return $this->newQuery()
            ->select($basDetailsAttr)
            ->where($merchantIdColumn, '=', $merchantId)
            ->join(Constants\Table::BALANCE, $basDetailsBalanceIdColumn, '=', $balanceIdColumn)
            ->where($balanceIdColumn, '=', $balanceId)
            ->where($accountTypeColumn, '=', Merchant\Balance\AccountType::DIRECT)
            ->where($balanceTypeColumn, '=', Merchant\Balance\Type::BANKING)
            ->first();
    }

    /**
     * Filter out Balance Id for balances where gateway balance has updated in last 6 hours
     *
     * @param array $balanceIdList
     *
     * @return mixed
     */
    public function getBalanceIdsWhereGatewayBalanceUpdatedRecently(array $balanceIdList)
    {
        $statusColumn    = $this->dbColumn(Entity::STATUS);
        $balanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $updatedAtColumn = $this->dbColumn(Entity::UPDATED_AT);

        $sixHourEarlierTimeStamp = Carbon::now(Constants\Timezone::IST)->subHours(6)->getTimestamp();

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($balanceIdColumn)
                    ->whereIn($balanceIdColumn, $balanceIdList)
                    ->where($updatedAtColumn, '>=', $sixHourEarlierTimeStamp)
                    ->where($statusColumn, '=', Status::ACTIVE)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::BALANCE_ID)
                    ->toArray();
    }
}
