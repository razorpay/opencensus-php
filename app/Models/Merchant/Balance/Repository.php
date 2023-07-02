<?php

namespace RZP\Models\Merchant\Balance;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;

class Repository extends Base\Repository
{
    protected $entity = 'balance';

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = [
        Entity::ACCOUNT_NUMBER => 'sometimes|alpha_num',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|unsigned_id|size:14',
    ];

    public function findOrFailById($id)
    {
        return $this->newQuery()
                    ->where(Entity::ID, '=', $id)
                    ->firstOrFail();
    }

    public function findOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query
            ->merchantIdAndType($id)
            ->firstOrFail();
    }

    public function getBankingBalanceWithMerchantIdAndAccountNumberOrFail($merchantId, $accountNumber)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                    ->where(Entity::ACCOUNT_TYPE, '=', AccountType::SHARED)
                    ->where(Entity::TYPE, '=', Type::BANKING)
                    ->firstOrFail();
    }

    public function findOrFailPublic($id, $columns = array('*'), string $connectionType = null)
    {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query
            ->merchantIdAndType($id)
            ->firstOrFailPublic();
    }

    public function getBalanceLockForUpdate($id)
    {
        assertTrue($this->isTransactionActive());

        return Entity::lockForUpdate()->newQuery()
                     ->merchantIdAndType($id)
                     ->firstOrFail();
    }

    // not in use
    public function getMerchantBalanceLockForUpdate($merchant)
    {
        assertTrue($this->isTransactionActive());

        return $this->getBalanceLockForUpdate($merchant->getKey());
    }

    public function getMerchantBalance($merchant): Entity
    {
        $balance = $this->findOrFailPublic($merchant->getId());

        $balance->merchant()->associate($merchant);

        return $balance;
    }

    public function getMerchantBalances(string $merchantId): Base\PublicCollection
    {
        $balances = $this->newQuery()
                         ->where(Entity::MERCHANT_ID, $merchantId)
                         ->get();

        return $balances;
    }

    public function editMerchantAmountCredits($merchant, $amountCredits)
    {
        $channel = $merchant->getChannel();

        return $this->transaction(function() use ($merchant, $amountCredits, $channel)
        {
            return $this->editMerchantAmountCreditsInTransaction($merchant, $amountCredits, $channel);
        });
    }

    private function editMerchantAmountCreditsInTransaction($merchant, $amountCredits, $channel)
    {
        assertTrue($this->isTransactionActive());

        $balance = $this->findOrFail($merchant->getId());

        // $nodalBalance = $this->getNodalBalanceLockForUpdate($channel);
        //
        // $nodalCredits = $nodalBalance->getAmountCredits();
        //
        // $nodalCredits = $nodalCredits - $balance->getAmountCredits() + $amountCredits;
        //
        // $nodalBalance->setAmountCredits($nodalCredits);

        $balance->setAmountCredits($amountCredits);

        $balance->saveOrFail();

        // $nodalBalance->saveOrFail();

        return $balance;
    }

    public function editMerchantFeeCredits($merchant, $feeCredits)
    {
        $channel = $merchant->getChannel();

        return $this->transaction(function() use ($merchant, $feeCredits, $channel)
        {
            return $this->editMerchantFeeCreditsInTransaction($merchant, $feeCredits, $channel);
        });
    }

    public function editMerchantRefundCredits($merchant, $credits)
    {
        $channel = $merchant->getChannel();

        return $this->transaction(function() use ($merchant, $credits, $channel)
        {
            return $this->editMerchantRefundCreditsInTransaction($merchant, $credits, $channel);
        });
    }

    private function editMerchantFeeCreditsInTransaction($merchant, $feeCredits, $channel)
    {
        assertTrue($this->isTransactionActive());

        $balance = $this->findOrFail($merchant->getId());

        $balance->setFeeCredits($feeCredits);

        $balance->saveOrFail();

        return $balance;
    }

    private function editMerchantRefundCreditsInTransaction($merchant, $credits, $channel)
    {
        assertTrue($this->isTransactionActive());

        $balance = $this->findOrFail($merchant->getId());

        $balance->setRefundCredits($credits);

        $balance->saveOrFail();

        return $balance;
    }

    public function updateBalance($balance)
    {
        assertTrue($this->isTransactionActive());

        $balance->saveOrFail();
    }

    public function updateBalanceWithOldBalanceCheck($balance, $oldBalance)
    {
        assertTrue($this->isTransactionActive());

        return $this->newQuery()
                    ->where(Entity::ID, $balance->getId())
                    ->where(Entity::BALANCE, $oldBalance)
                    ->update($balance->getDirty());
    }

    public function updateBalanceDirectly($balance, $amount)
    {
        $balance->increment(Entity::BALANCE, $amount);
    }

    public function createBalance($balance)
    {
        assertTrue($balance->exists === false);

        $balance->saveOrFail();
    }

    public function getNodalBalance($channel)
    {
        $func = 'get' . ucfirst($channel) . 'Balance';

        return $this->$func();
    }

    public function getKotakBalance()
    {
        assertTrue($this->isTransactionActive());

        return $this->findOrFail(Merchant\Account::NODAL_ACCOUNT);
    }

    public function getNodalBalanceLockForUpdate($channel)
    {
        $func = 'get' . ucfirst($channel) . 'BalanceLockForUpdate';

        return $this->$func();
    }

    public function getKotakBalanceLockForUpdate()
    {
        assertTrue($this->isTransactionActive());

        return $this->getBalanceLockForUpdate(Merchant\Account::NODAL_ACCOUNT);
    }

    public function getAtomBalanceLockForUpdate()
    {
        assertTrue($this->isTransactionActive());

        return $this->getBalanceLockForUpdate(Merchant\Account::ATOM_ACCOUNT);
    }

    /**
     * Returns merchant ids where updated at > $minUpdatedAtTimeStamp
     *
     * @param int $minUpdatedAtTimeStamp
     *
     * @return array
     */
    public function getMerchantsIdsForEsSync(int $minUpdatedAtTimeStamp): array
    {
        $merchantIds = $this->newQueryWithConnection($this->getSlaveConnection())
                            ->where(Entity::TYPE, '=', Type::PRIMARY)
                            ->where(Entity::UPDATED_AT, '>=', $minUpdatedAtTimeStamp)
                            ->groupBy(Entity::MERCHANT_ID)
                            ->select(Entity::MERCHANT_ID)
                            ->get();

        $merchantIds = isset($merchantIds) ? $merchantIds->toArray() : [];

        $merchantIds = array_pluck($merchantIds, Entity::MERCHANT_ID);

        return $merchantIds;
    }

    public function getBalances($limit)
    {
        return $this->newQuery()
                    ->whereRaw(Entity::ID . '=' . Entity::MERCHANT_ID)
                    ->limit($limit)
                    ->get();
    }

    /**
     * @param string      $merchantId
     * @param string      $balanceType
     * @param string|null $connection
     * @return mixed
     */
    public function getMerchantBalanceByType(string $merchantId, string $balanceType, string $connection = null)
    {
        $query = $connection !== null ? $this->newQueryWithConnection($connection) : $this->newQuery();

        return $query->merchantIdAndType($merchantId, $balanceType)
                     ->first();
    }

    public function getMerchantBalanceByTypeFromDataLake(string $merchantId, string $balanceType)
    {
        $rawQuery = "select * from hive.realtime_hudi_api.balance b where b.merchant_id = '%s' and b.type = '%s' limit 1";

        $dataLakeQuery = sprintf($rawQuery, $merchantId, $balanceType);

        return $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery)[0];
    }

    /**
     * @param string      $merchantId
     * @param string      $balanceType
     * @param string|null $connection
     *
     * @return mixed
     */
    public function getMerchantBalancesByType(string $merchantId, string $balanceType, string $connection = null)
    {
        $query = ($connection !== null) ? $this->newQueryWithConnection($connection) : $this->newQuery();

        return $query->merchantIdAndType($merchantId, $balanceType)
                     ->get();
    }

    public function getMerchantBalanceByTypeAndAccountType(
        string $merchantId,
        string $balanceType,
        string $accType,
        string $connection = null)
    {
        $query = $connection !== null ? $this->newQueryWithConnection($connection) : $this->newQuery();

        return $query->merchantIdAndType($merchantId, $balanceType)
                     ->where(Entity::ACCOUNT_TYPE, $accType)
                     ->first();
    }

    public function getMerchantBalanceByTypeAndAccountTypeForUpdate(
        string $merchantId,
        string $balanceType,
        string $accType,
        string $connection = null)
    {
        assertTrue($this->isTransactionActive());

        return Entity::lockForUpdate()->newQuery()
                     ->merchantIdAndType($merchantId, $balanceType)
                     ->where(Entity::ACCOUNT_TYPE, $accType)
                     ->firstOrFail();
    }

    public function getMerchantBalancesByTypeAndAccountType(
        string $merchantId,
        string $balanceType,
        string $accType,
        string $connection = null)
    {
        $query = $connection !== null ? $this->newQueryWithConnection($connection) : $this->newQuery();

        return $query->merchantIdAndType($merchantId, $balanceType)
                     ->where(Entity::ACCOUNT_TYPE, $accType)
                     ->get();
    }

    public function getMerchantBalancesByTypeAndAccountTypeAndBalanceIds(
        string $merchantId,
        string $balanceType,
        string $accType,
        array $balanceIds,
        string $connection = null)
    {
        $query = $connection !== null ? $this->newQueryWithConnection($connection) : $this->newQuery();

        return $query->merchantIdAndType($merchantId, $balanceType)
                     ->where(Entity::ACCOUNT_TYPE, $accType)
                     ->whereIn(Entity::ID, $balanceIds)
                     ->get();
    }

    public function getBalanceIdByAccountNumberOrFail(string $accountNumber): string
    {
        return $this->getBalanceByAccountNumberOrFail($accountNumber)->getId();
    }

    public function getBalanceByAccountNumberOrFail(string $accountNumber, string $merchantId = null): Entity
    {
        if ($merchantId === null)
        {
            //
            // Gets merchant identifier from current auth context.
            // Must not use $this->merchantId because when auth's merchant is set/reset it does not affect
            // singleton repository objects which are already resolved.
            //
            $merchantId = $this->auth->getMerchantId();
        }

        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->merchantIdAndType($merchantId, Type::BANKING)
                    ->firstOrFailPublic();
    }

    public function getBalanceByTypeAccountNumberAndAccountTypeOrFail(
        string $accountNumber,
        string $type,
        string $accountType): Entity
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->where(Entity::TYPE, $type)
                    ->where(Entity::ACCOUNT_TYPE, $accountType)
                    ->firstOrFailPublic();
    }

    public function getBalanceByAccountNumberAndMerchantIDOrFail(string $accountNumber, $merchantId): Entity
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->merchantIdAndType($merchantId, Type::BANKING)
                    ->firstOrFailPublic();
    }

    public function getBalanceByMerchantIdAccountNumberAndChannelOrFail(
        string $merchantId,
        string $accountNumber,
        string $channel): Entity
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->where(Entity::CHANNEL, $channel)
                    ->merchantIdAndType($merchantId, Type::BANKING)
                    ->firstOrFailPublic();
    }

    /**
     * Filter out Balance Id for balances where balance has updated in last 6 hours
     *
     * @param $balanceIdList
     *
     * @return mixed
     */
    public function getBankingBalanceIdsWhereBalanceUpdatedRecently(array $balanceIdList)
    {
        $idColumn        = $this->dbColumn(Entity::ID);
        $typeColumn      = $this->dbColumn(Entity::TYPE);
        $updatedAtColumn = $this->dbColumn(Entity::UPDATED_AT);

        $sixHourEarlierTimeStamp = Carbon::now(Timezone::IST)->subHours(6)->getTimestamp();

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($idColumn)
                    ->whereIn($idColumn, $balanceIdList)
                    ->where($typeColumn, '=', Type::BANKING)
                    ->where($updatedAtColumn, '>=', $sixHourEarlierTimeStamp)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::ID)
                    ->toArray();
    }

    public function getBalancesForBalanceIds(array $balanceIdList)
    {
        $idColumn        = $this->dbColumn(Entity::ID);
        $balanceColumn   = $this->dbColumn(Entity::BALANCE);
        $updatedAtColumn = $this->dbColumn(Entity::UPDATED_AT);

        $sixHourEarlierTimeStamp = Carbon::now(Timezone::IST)->subHours(6)->getTimestamp();

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($idColumn, $balanceColumn)
                    ->whereIn($idColumn, $balanceIdList)
                    ->where($updatedAtColumn, '>=', $sixHourEarlierTimeStamp)
                    ->get()
                    ->pluck(Entity::BALANCE, Entity::ID)
                    ->toArray();
    }

    public function getBalancesForMerchantIds(array $merchantIds, $balanceType)
    {
        return $this->newQuery()
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->where(Entity::TYPE, $balanceType)
                    ->pluck(Entity::BALANCE, Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function getBalancesForAccountNumbersForTypeBanking($accountNumbers, $merchantID)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ACCOUNT_NUMBER, $accountNumbers)
                    ->where(Entity::MERCHANT_ID, $merchantID)
                    ->where(Entity::TYPE, Type::BANKING)
                    ->get();
    }

    public function getBalancesByMerchantIDForTypeBanking($merchantID)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantID)
                    ->where(Entity::TYPE, Type::BANKING)
                    ->get();
    }

    public function getBalanceByIdFromWhatsappDB(string $id)
    {
        $idColumn = $this->dbColumn(Entity::ID);

        return $this->newQueryWithConnection($this->getWhatsappDatabaseConnection())
                    ->where($idColumn, $id)
                    ->first();
    }

    public function getBalanceSumFromSubBalances(array $balanceIdList, Merchant\Entity $merchant)
    {
        $idColumn = $this->dbColumn(Entity::ID);

        $balanceColumn = $this->dbColumn(Entity::BALANCE);

        if ($merchant->isFeatureEnabled(Feature\Constants::MERCHANT_ROUTE_WA_INFRA))
        {
            return $this->newQueryWithConnection($this->getWhatsappDatabaseConnection())
                        ->whereIn($idColumn, $balanceIdList)
                        ->sum($balanceColumn);
        }

        return $this->newQuery()
                    ->whereIn($idColumn, $balanceIdList)
                    ->sum($balanceColumn);
    }

    public function getCANpsCohortList(int $startTime, int $endTime)
    {
        $balanceIdColumn                = $this->dbColumn(Entity::ID);
        $balanceCreatedColumn           = $this->dbColumn(Entity::CREATED_AT);
        $bankingAccountsBalanceIdColumn = $this->repo->banking_account->dbColumn(BankingAccount\Entity::BALANCE_ID);
        $activationStatus               = $this->repo->banking_account->dbColumn(BankingAccount\Entity::STATUS);
        $accountTypeColumn              = $this->repo->banking_account->dbColumn(BankingAccount\Entity::ACCOUNT_TYPE);

        $selectAttr = [
            $this->dbColumn(Entity::MERCHANT_ID),
        ];

        return $this->newQuery()
                    ->select($selectAttr)
                    ->join(Table::BANKING_ACCOUNT, $balanceIdColumn, '=', $bankingAccountsBalanceIdColumn)
                    ->where($accountTypeColumn, '=', BankingAccount\AccountType::CURRENT)
                    ->where($activationStatus, '=', BankingAccount\Status::ACTIVATED)
                    ->whereBetween($balanceCreatedColumn, [$startTime, $endTime])
                    ->groupBy(Entity::MERCHANT_ID)
                    ->get();
    }

    /**
     * @param string      $merchantId
     * @param string      $accountNumber
     * @param string      $channel
     *
     * @param string      $accountType
     *
     * @param string|null $connection
     *
     * @return Entity
     */
    public function getBalanceByMerchantIdAccountNumberChannelAndAccountType(
        string $merchantId,
        string $accountNumber,
        string $channel,
        string $accountType,
        string $connection = null)
    {
        $query = $connection !== null ? $this->newQueryWithConnection($connection) : $this->newQuery();

        return $query->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                     ->where(Entity::CHANNEL, $channel)
                     ->where(Entity::ACCOUNT_TYPE, $accountType)
                     ->merchantIdAndType($merchantId, Type::BANKING)
                     ->first();
    }

    public function getBalanceByMerchantIdChannelsAndAccountType(string $merchantId,
                                                                 array $channels,
                                                                 string $accountType)
    {
        return $this->newQuery()
                    ->whereIn(Entity::CHANNEL, $channels)
                    ->where(Entity::ACCOUNT_TYPE, $accountType)
                    ->merchantIdAndType($merchantId, Type::BANKING)
                    ->first();
    }

    public function getBalancesByMerchantIdChannelsAndAccountType(string $merchantId,
                                                                  array $channels,
                                                                  string $accountType)
    {
        return $this->newQuery()
                    ->whereIn(Entity::CHANNEL, $channels)
                    ->where(Entity::ACCOUNT_TYPE, $accountType)
                    ->merchantIdAndType($merchantId, Type::BANKING)
                    ->get();
    }

    public function getMerchantsWithBalanceUpdatedInTimeRange($from, $to)
    {
        $startTime = microtime(true);

        $result = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
                       ->select(Entity::MERCHANT_ID)
                       ->where(Entity::TYPE, '=', Type::PRIMARY)
                       ->where(Entity::UPDATED_AT, '>', $from)
                       ->where(Entity::UPDATED_AT, '<=', $to)
                       ->groupBy(Entity::MERCHANT_ID)
                       ->pluck(Entity::MERCHANT_ID)
                       ->toArray();

        $this->trace->info(
            TraceCode::SETTLEMENT_DEBUGGING_FRAMEWORK_MERCHANT_FETCH_TIME_TAKEN,
            [
                'time_taken' => get_diff_in_millisecond($startTime),
                'count'      => count($result),
            ]);

        return $result;
    }

    /**
     * Filter balance ids with merchants having payout_service_enabled feature
     *
     * @return mixed
     */
    public function getBalanceIdsWithAMerchantsHavingPayoutServiceEnabled(array $balanceIdList = [])
    {
        $idColumn         = $this->dbColumn(Entity::ID);
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $balanceColumn    = $this->dbColumn(Entity::BALANCE);

        $featureEntityIdColumn   = $this->repo->feature->dbColumn(Feature\Entity::ENTITY_ID);
        $featureEntityTypeColumn = $this->repo->feature->dbColumn(Feature\Entity::ENTITY_TYPE);
        $featureNameColumn       = $this->repo->feature->dbColumn(Feature\Entity::NAME);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($idColumn, $balanceColumn)
                    ->join(Table::FEATURE, $merchantIdColumn, '=', $featureEntityIdColumn)
                    ->where($featureEntityTypeColumn, Feature\Constants::MERCHANT)
                    ->whereIn($idColumn, $balanceIdList)
                    ->where($featureNameColumn, Feature\Constants::PAYOUT_SERVICE_ENABLED)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::BALANCE, Entity::ID)
                    ->toArray();
    }

    public function addQueryParamAccountNumberSuffix($query, $param)
    {
        $accountNumber       = $this->dbColumn(Entity::ACCOUNT_NUMBER);
        $accountNumberSuffix = $param[Entity::ACCOUNT_NUMBER_SUFFIX];

        $query->where($accountNumber, 'like', '%' . $accountNumberSuffix);
    }

    public function getBalanceByAccountNumber(string $accountNumber)
    {
        /*
         SELECT balance.channel, balance.account_type
            from balance
            where balance.account_number = $accountNumber
            and balance.type = 'banking';
        */
        $channelColumn   = $this->repo->balance->dbColumn(Entity::CHANNEL);
        $accountTypeColumn = $this->repo->balance->dbColumn(Entity::ACCOUNT_TYPE);

        // Index exists on the account_number column
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->select($channelColumn, $accountTypeColumn)
            ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
            ->where(Entity::TYPE, Type::BANKING)
            ->first();
    }
}
