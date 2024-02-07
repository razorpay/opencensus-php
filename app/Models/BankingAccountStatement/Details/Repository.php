<?php

namespace RZP\Models\BankingAccountStatement\Details;

use RZP\Constants;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Preferences;
use RZP\Models\BankingAccountStatement\Constants as BASConstant;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BANKING_ACCOUNT_STATEMENT_DETAILS;

    public function fetchAccountStatementByBalance(string $balanceId) : Entity{
        return $this->newQuery()
            ->where(Entity::BALANCE_ID, '=', $balanceId)
            ->first();
    }

    public function fetchByAccountNumberAndChannel(string $accountNumber, string $channel, array $statuses = [])
    {
        $accountNumberColumn = $this->dbColumn(Entity::ACCOUNT_NUMBER);

        $channelColumn = $this->dbColumn(Entity::CHANNEL);

        $statusColumn = $this->dbColumn(Entity::STATUS);

        $BASDetailsDbColumns = $this->dbColumn('*');

        $query = $this->newQuery()
                      ->select($BASDetailsDbColumns)
                      ->where($accountNumberColumn, '=', $accountNumber)
                      ->where($channelColumn, '=', $channel);

        if (empty($statuses) === false)
        {
            $query->whereIn($statusColumn, $statuses);
        }

        return $query->first();
    }

    public function fetchActiveAccountsByAccountNumberAndMerchantId(string $merchantId, string $accountNumber, array $statuses = [])
    {
        $accountNumberColumn = $this->dbColumn(Entity::ACCOUNT_NUMBER);

        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);

        $statusColumn = $this->dbColumn(Entity::STATUS);

        $BASDetailsDbColumns = $this->dbColumn('*');

        $statusList = Status::getStatusesForActiveCaFlows();

        $query = $this->newQuery()
                      ->select($BASDetailsDbColumns)
                      ->where($accountNumberColumn, '=', $accountNumber)
                      ->where($merchantIdColumn, '=', $merchantId)
                      ->whereIn($statusColumn, $statusList);

        return $query->get();
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

    public function fetchByChannelOrderByBalanceLastFetchedAt(string $channel, array $merchantIdsExcluded = [], string $accountType = AccountType::DIRECT)
    {
        $channelColumn = $this->dbColumn(Entity::CHANNEL);

        $statusColumn = $this->dbColumn(Entity::STATUS);

        $accountTypeColumn = $this->dbColumn(Entity::ACCOUNT_TYPE);

        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);

        $basDetailsAttr = $this->dbColumn('*');

        $statusList = Status::getStatusesForActiveCaFlows();

        $query = $this->newQuery()
                    ->select($basDetailsAttr)
                    ->where($channelColumn, '=', $channel)
                    ->whereIn($statusColumn, $statusList)
                    ->where($accountTypeColumn, '=', $accountType)
                    ->oldest(Entity::BALANCE_LAST_FETCHED_AT);

        if (empty($merchantIdsExcluded) === false)
        {
            $query = $query->whereNotIn($merchantIdColumn, $merchantIdsExcluded);
        }

        return $query->get();
    }

    public function getMerchantIdsByChannel($channel, $limit)
    {
        $channelColumn                 = $this->dbColumn(Entity::CHANNEL);
        $merchantIdColumn              = $this->dbColumn(Entity::MERCHANT_ID);

        $accountTypeColumn = $this->dbColumn(Entity::ACCOUNT_TYPE);

        $basDetailsAttr = $this->dbColumn('*');

        $statusList = Status::getStatusesForActiveCaFlows();

        return $this->newQuery()
                    ->select($basDetailsAttr)
                    ->where($channelColumn, '=', $channel)
                    ->whereIn(Entity::STATUS, $statusList)
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

        $statusList = Status::getStatusesForActiveCaFlows();

        return $this->newQuery()
                    ->select($basDetailsAttr)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->join(Constants\Table::BALANCE, $basDetailsBalanceIdColumn, '=', $balanceIdColumn)
                    ->where($accountTypeColumn, '=', Merchant\Balance\AccountType::DIRECT)
                    ->where($balanceTypeColumn, '=', Merchant\Balance\Type::BANKING)
                    ->where($channelColumn, '=', $channel)
                    ->whereIn($statusColumn, $statusList)
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

        $statusList = Status::getStatusesForActiveCaFlows();

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($balanceIdColumn)
                    ->whereIn($balanceIdColumn, $balanceIdList)
                    ->where($updatedAtColumn, '>=', $sixHourEarlierTimeStamp)
                    ->whereIn($statusColumn, $statusList)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::BALANCE_ID)
                    ->toArray();
    }

    /**
     * Get balance ids for active BASD entries for given merchant id
     *
     * @param string $merchantId
     *
     * @return mixed
     */
    public function getBalanceIdsForActiveDirectAccounts(string $merchantId)
    {
        $statusColumn     = $this->dbColumn(Entity::STATUS);
        $balanceIdColumn  = $this->dbColumn(Entity::BALANCE_ID);
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);

        $statusList = Status::getStatusesForActiveCaFlows();

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($balanceIdColumn)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->whereIn($statusColumn, $statusList)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::BALANCE_ID)
                    ->toArray();
    }

    /**
     * Get active BASD entries for given merchant id
     *
     * @param string $merchantId
     *
     * @return mixed
     */
    public function getActiveDirectAccountsForMerchantId(string $merchantId)
    {
        $basDetailsAttr = $this->dbColumn('*');

        $statusColumn      = $this->dbColumn(Entity::STATUS);
        $merchantIdColumn  = $this->dbColumn(Entity::MERCHANT_ID);
        $accountTypeColumn = $this->dbColumn(Entity::ACCOUNT_TYPE);

        $allowedStatuses = Status::getStatusesForActiveCaFlows();

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($basDetailsAttr)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->where($accountTypeColumn, '=', AccountType::DIRECT)
                    ->whereIn($statusColumn, $allowedStatuses)
                    ->distinct()
                    ->get();
    }

    public function getByAccountNumbersAndPaginationKeyNull(string $channel, array $accountNumbers)
    {
        return $this->newQuery()
                    ->useWritePdo()
                    ->whereIn(Entity::ACCOUNT_NUMBER, $accountNumbers)
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->whereNull(Entity::PAGINATION_KEY)
                    ->get()
                    ->pluck(Entity::ACCOUNT_NUMBER)
                    ->toArray();
    }

    public function getByAccountNumbersAndLastReconciledAt(string $channel, array $accountNumbers)
    {
        $accountNumberColumn = $this->dbColumn(Entity::ACCOUNT_NUMBER);

        return $this->newQuery()
                    ->select(Entity::MERCHANT_ID, Entity::ACCOUNT_NUMBER, Entity::LAST_RECONCILED_AT)
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->whereIn($accountNumberColumn, $accountNumbers)
                    ->get()
                    ->toArray();
    }

    public function getAccountNumbersWhereGatewayBalanceIsUpdatedRecently(string $channel, $reconLimit)
    {
        $fromTimeStamp = Carbon::now(Constants\Timezone::IST)->subDay()->startOfDay()->getTimestamp();
        $toTimeStamp = Carbon::now(Constants\Timezone::IST)->getTimestamp();

        $query = $this->newQuery()
                      ->select(Entity::ACCOUNT_NUMBER)
                      ->where(Entity::CHANNEL, '=', $channel)
                      ->where(Entity::ACCOUNT_TYPE, '=', AccountType::DIRECT)
                      ->whereBetween(Entity::GATEWAY_BALANCE_CHANGE_AT, [$fromTimeStamp, $toTimeStamp])
                      ->useWritePdo();

        if (isset($reconLimit) === true)
        {
            $query->limit($reconLimit);
        }

        return $query->get()
                     ->pluck(Entity::ACCOUNT_NUMBER)
                     ->toArray();
    }

    /*
     * select `merchants`.`id` from `banking_account_statement_details` inner join `merchants` on
     * `banking_account_statement_details`.`merchant_id` = `merchants`.`id` where `banking_account_statement_details`.`channel` in (?,?,?,?) and
     * `banking_account_statement_details`.`account_type` = ? and `banking_account_statement_details`.`status` in (?, ?) and `merchants`.`activated` = ?
     * and (`parent_id` not in (?) or `parent_id` is null) limit 10000 offset 0
     * DBA thread - https://razorpay.slack.com/archives/C3BPZHG8P/p1686285370722479
     */
    public function getCurrentAccountActivatedMerchants(int    $limit,
                                                        int    $skip,
                                                        array  $merchantIds = [],
                                                        array  $merchantIdsExcluded = []): array
    {
        $channelColumn = $this->dbColumn(Entity::CHANNEL);
        $accountTypeColumn = $this->dbColumn(Entity::ACCOUNT_TYPE);
        $statusColumn = $this->dbColumn(Entity::STATUS);

        $merchantActivatedColumn = $this->repo->merchant->dbColumn(Merchant\Entity::ACTIVATED);
        $merchantIdColumn = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $bankingAccountMerchantIdColumn = $this->repo->banking_account_statement_details->dbColumn(Entity::MERCHANT_ID);

        $statusList = Status::getStatusesForActiveCaFlows();
        $directChannels = \RZP\Models\BankingAccountService\Channel::getDirectTypeChannels();

        $query =  $this->newQueryWithConnection($this->getSlaveConnection())
            ->join(Constants\Table::MERCHANT, $bankingAccountMerchantIdColumn, '=', $merchantIdColumn)
            ->select($merchantIdColumn)
            ->whereIn($channelColumn, $directChannels)
            ->where($accountTypeColumn, '=', AccountType::DIRECT)
            ->whereIn($statusColumn, $statusList)
            ->where($merchantActivatedColumn, '=', 0)
            ->where(function ($query)
            {
                $query->whereNotIn(Merchant\Entity::PARENT_ID, Preferences::NO_MERCHANT_INVOICE_PARENT_MIDS)
                    ->orWhereNull(Merchant\Entity::PARENT_ID);
            })
            ->take($limit)
            ->skip($skip);

        if (empty($merchantIds) === false)
        {
            $query = $query->whereIn($merchantIdColumn, $merchantIds);
        }

        if (empty($merchantIdsExcluded) === false)
        {
            $query = $query->whereNotIn($merchantIdColumn, $merchantIdsExcluded);
        }

        return $query->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    public function getCAMerchantIdsForChannel(string $channel, array $merchantIds = []): array
    {
        $channelColumn = $this->dbColumn(Entity::CHANNEL);
        $accountTypeColumn = $this->dbColumn(Entity::ACCOUNT_TYPE);
        $statusColumn = $this->dbColumn(Entity::STATUS);

        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);

        $statusList = Status::getStatusesForActiveCaFlows();

        $query =  $this->newQueryWithConnection($this->getSlaveConnection())
                       ->select($merchantIdColumn)
                       ->where($channelColumn, '=', $channel)
                       ->where($accountTypeColumn, '=', AccountType::DIRECT)
                       ->whereIn($statusColumn, $statusList);

        if (empty($merchantIds) === false)
        {
            $query = $query->whereIn($merchantIdColumn, $merchantIds);
        }

        return $query->get()
                     ->pluck(Entity::MERCHANT_ID)
                     ->toArray();
    }
}
