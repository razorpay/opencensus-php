<?php

namespace RZP\Models\Transaction;

use DB;
use Cache;
use Carbon\Carbon;

use RZP\Base\ConnectionType;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Base\Common;
use RZP\Base\BuilderEx;
use RZP\Models\Adjustment;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Models\Admin\Org;
use RZP\Gateway\Billdesk;
use RZP\Constants\Product;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Constants\Entity as E;
use RZP\Models\Payment\Refund;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Balance;
use RZP\Models\Transfer\Constant;
use RZP\Constants\Entity as ConstantEntity;

class Repository extends Base\Repository
{
    protected $entity = 'transaction';

    protected $signedIds = [
        Entity::SETTLEMENT_ID,
    ];

    protected $appFetchParamRules = array(
        Entity::SETTLED         => 'sometimes|in:0,1',
        Entity::ON_HOLD         => 'sometimes|in:0,1',
        Entity::TYPE            => 'sometimes|in:payment,refund,settlement,adjustment,reversal,transfer,payout,credit_transfer',
        Entity::SETTLEMENT_ID   => 'sometimes|alpha_dash|min:14|max:19',
        Entity::ENTITY_ID       => 'sometimes|alpha_dash|min:14',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::RECONCILED      => 'sometimes|in:0,1',
    );

    public function fetchByEntityAndAssociateMerchant($entity)
    {
        $txn = $this->newQuery()
                    ->where(Transaction\Entity::ENTITY_ID, '=', $entity->getId())
                    ->firstOrFail();

        $entity->transaction()->associate($txn);
        $txn->source()->associate($entity);

        $txn->merchant()->associate($entity->merchant);

        return $txn;
    }

    public function fetchBySourceAndAssociateMerchant($entity)
    {
        $txn = $this->newQuery()
                    ->where(Transaction\Entity::ENTITY_ID, '=', $entity->getId())
                    ->first();

        if ($txn === null)
        {
          return null;
        }

        $entity->transaction()->associate($txn);
        $txn->source()->associate($entity);

        $txn->merchant()->associate($entity->merchant);

        return $txn;
    }

    public function fetchTxnsExpectedToSettle($timestamp)
    {
        return $this->newQuery()
                    ->where(Transaction\Entity::SETTLED_AT, '=', $timestamp)
                    ->where(Transaction\Entity::SETTLED, '=', 0)
                    ->where(Transaction\Entity::TYPE, '!=', Type::SETTLEMENT)
                    ->orderBy(Transaction\Entity::MERCHANT_ID)
                    ->orderBy(Transaction\Entity::ID)
                    ->get();
    }

    /**
     * We DO NOT want to eager load any relationship for any of the entities
     * because every transaction will store a separate copy for each of its
     * relation. So 2 transactions of the same merchant will have a copy each
     * of merchant, bank_account, and balance – effectively 2 * 3 storage.
     * Now, compare it against the case where we keep only 1 copy of each of
     * these relations – in a case where lacs of transactions of a merchant
     * are going to be settled. The difference is memory used will be huge.
     * Hence instead will query it separately.
     *
     * @param $timestamp
     * @param string $channel
     * @param array $inMerchantIds
     * @param array $notInMerchantIds
     * @param boolean $fetchAll
     * @param boolean $useLimit
     * @param array $params
     * @return mixed
     */
    public function fetchUnsettledTransactions(
        $timestamp, string $channel, array $inMerchantIds = [], array $notInMerchantIds = [], bool $fetchAll = true,
        bool $useLimit = false, array $params = [])
    {
        // SELECT `transactions`.`id`.`merchant_id`
        // FROM transactions
        // INNER JOIN
        //     (SELECT `id`
        //      FROM merchants
        //      WHERE hold_funds = 0
        //          AND merchants.activated_at IS NOT NULL
        //          AND merchants.id NOT IN ('8ytYezIThlseJd',
        //                                   '7BfRNg10LH7N6T')
        //     ) AS settle_merchants ON settle_merchants.id = transactions.merchant_id
        // WHERE settled_at < now()
        //     AND on_hold = 0
        //     AND settled = 0
        //     AND transactions.channel = 'axis'
        //     AND type != 'settlement'

        $txnFetchStartTime      = microtime(true);

        $activatedMerchants = $this->repo->merchant->fetchMerchantsForSettlement($inMerchantIds, $notInMerchantIds);

        $transactionType        = $this->dbColumn(Entity::TYPE);
        $transactionOnHold      = $this->dbColumn(Entity::ON_HOLD);
        $transactionChannel     = $this->dbColumn(Entity::CHANNEL);
        $transactionSettled     = $this->dbColumn(Entity::SETTLED);
        $transactionBalanceId   = $this->dbColumn(Entity::BALANCE_ID);
        $transactionSettledAt   = $this->dbColumn(Entity::SETTLED_AT);

        $balanceId              = $this->repo->balance->dbColumn(Entity::ID);
        $balanceTypeColumn      = $this->repo->balance->dbColumn(Entity::TYPE);


        $selectedColumns = $this->fetchRequiredColumnsForSettlement($fetchAll);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->select($selectedColumns)
                      ->joinSub($activatedMerchants->toSql(), 'settle_merchants', function($join)
                                {
                                    $join->on('settle_merchants.id', '=', 'transactions.merchant_id');
                                })
                      ->mergeBindings($activatedMerchants->getQuery())
                      ->leftJoin(Table::BALANCE, $balanceId, '=', $transactionBalanceId)
                      ->where(function ($query) use ($transactionBalanceId, $balanceTypeColumn)
                              {
                                  $query->whereNull($transactionBalanceId)
                                        ->orWhere($balanceTypeColumn, Balance\Type::PRIMARY);
                              })
                      ->where($transactionOnHold, 0)
                      ->where($transactionSettled, 0)
                      ->where($transactionChannel, $channel)
                      ->where($transactionType, '!=', Type::SETTLEMENT)
                      ->whereNotNull($transactionSettledAt);

        if ($useLimit === true)
        {
            $limit = (int) Cache::get(ConfigKey::SETTLEMENT_TRANSACTION_LIMIT);

            if ($limit !== 0)
            {
                $query->limit($limit);
            }
        }

        $query = $this->addSettlementFilters($query, $timestamp, $params);

        $results = $query->get();

        $txnFetchTimeTaken = microtime(true) - $txnFetchStartTime;

        $this->trace->info(
            TraceCode::SETTLEMENT_TXN_FETCH_TIME_TAKEN,
            [
                'time_taken' => $txnFetchTimeTaken,
                'txn_count'  => $results->count(),
                'params'     => $params,
            ]);

        return $results;
    }

    /**
     * calculates the sum of `fee` and `tax` for the instant speed refunds
     *  - captured for a merchant in a given time frame
     *  - based on filter type passed REFUND_LTE_1K, REFUND_GT_1K_LTE_10K, REFUND_GT_10K
     *
     * @param string $merchantId
     * @param int $start
     * @param int $end
     * @param string $filterType
     *
     * @return mixed
     * @throws Exception\LogicException
     */
    public function fetchFeesAndTaxForRefundByType(
        string $merchantId,
        int $start,
        int $end)
    {
        /*
            SELECT Sum(transactions.tax) AS tax,
                   Sum(transactions.fee) AS fee
            FROM   `transactions`
            WHERE  `transactions`.`type` = ?
                   AND `transactions`.`created_at` BETWEEN ? AND ?
                   AND `transactions`.`merchant_id` = ?
            LIMIT  1
         */
        $query = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
            ->selectRaw('SUM(' . $this->dbColumn(Entity::TAX) . ') AS tax, SUM(' . $this->dbColumn(Entity::FEE) . ') AS fee')
            ->where($this->dbColumn(Entity::TYPE), '=', 'refund')
            ->whereBetween($this->dbColumn(Entity::CREATED_AT), [$start, $end]);

        $query->merchantId($merchantId);

        return $query->first();
    }

    public function fetchUnsettledTransactionsForMerchantUpdate($merchantId)
    {
        $transactionIdColumn        = $this->dbColumn(Entity::ID);
        $transactionTypeColumn      = $this->dbColumn(Entity::TYPE);
        $transactionBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);

        $balanceIdColumn   = $this->repo->balance->dbColumn(Entity::ID);
        $balanceTypeColumn = $this->repo->balance->dbColumn(Entity::TYPE);

        $query = $this->newQuery()
                      ->select([$transactionIdColumn])
                      ->leftJoin(Table::BALANCE, $balanceIdColumn, '=', $transactionBalanceIdColumn)
                      ->where(function($query) use ($transactionBalanceIdColumn, $balanceTypeColumn)
                              {
                                  $query->whereNull($transactionBalanceIdColumn)
                                        ->orWhere($balanceTypeColumn, Balance\Type::PRIMARY);
                              })
                      ->where(Transaction\Entity::SETTLED, '=', 0)
                      ->where($transactionTypeColumn, '!=', Type::SETTLEMENT)
                      ->merchantId($merchantId);

        return $query->get();
    }

    public function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $entityToRelationFetchMap = [])
    {
        $setls = (new Settlement\Repository)->fetchBetweenTimestamp($merchantId, $from, $to);

        $setlIds = $setls->modelKeys();

        $query = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection());

        $txns = $query->merchantId($merchantId)
                      ->where(function($query) use ($from, $to, $setlIds)
                      {
                        $query->betweenTime($from, $to);

                        if (count($setlIds) !== 0)
                        {
                            $query->orWhereIn(Entity::SETTLEMENT_ID, $setlIds);
                        }
                      })
                      ->take($count)
                      ->skip($skip)
                      ->latest()
                      ->get();

        $txns = $this->fetchAssociatedRelationsWithLoadedEntities($txns, 'source', $entityToRelationFetchMap);

        $this->trace->info(
            TraceCode::MERCHANT_REPORT_GENERATION,
            [
                'method'    => __METHOD__,
                'time'      => time(),
            ]);

        return $txns;
    }

    public function fetchEntitiesForReconReport($merchantId, $from, $to, $count, $skip, $entityToRelationFetchMap = [])
    {
        $setls = (new Settlement\Repository)->fetchBetweenTimestamp($merchantId, $from, $to);

        $setlIds = $setls->modelKeys();

        $query = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection());

        $txns = $query
                      ->merchantId($merchantId)
                      ->whereIn(Entity::SETTLEMENT_ID, $setlIds)
                      ->take($count)
                      ->skip($skip)
                      ->latest()
                      ->orderBy(Common::ID, 'desc')
                      ->get();

        $txns = $this->fetchAssociatedRelationsWithLoadedEntities($txns, 'source', $entityToRelationFetchMap);

        $this->trace->info(
            TraceCode::MERCHANT_REPORT_GENERATION,
            [
                'method'    => __METHOD__,
                'time'      => time(),
            ]);

        return $txns;
    }

    public function fetchEntitiesForBrokerReport($merchantId, $from, $to, $count, $skip, $entityToRelationFetchMap)
    {
        $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $txns = $this->newQueryWithConnection($connectionType)
                     ->merchantId($merchantId)
                     ->betweenTime($from, $to)
                     ->whereIn(Entity::TYPE, ['payment', 'refund'])
                     ->with('merchant', 'feesBreakup')
                     ->latest()
                     ->get();

        $this->trace->info(
            TraceCode::MERCHANT_REPORT_GENERATION,
            ['time' => time()]);

        $txns = $this->fetchAssociatedRelationsWithLoadedEntities($txns, 'source', $entityToRelationFetchMap);

        return $txns;
    }

    public function fetchEntitiesForDSPReport($merchantId, $from, $to, $count, $skip, $entityToRelationFetchMap)
    {
        $txns = $this->newQuery()
                     ->merchantId($merchantId)
                     ->betweenTime($from, $to)
                     ->whereIn(Entity::TYPE, ['payment'])
                     ->with('settlement')
                     ->latest()
                     ->get();

        $this->trace->info(
            TraceCode::MERCHANT_REPORT_GENERATION,
            ['time' => time()]);

        $txns = $this->fetchAssociatedRelationsWithLoadedEntities($txns, 'source', $entityToRelationFetchMap);

        return $txns;
    }

    /**
     * Fetches and associates with Transaction entity
     *
     * @param $entities - Array of Transaction entities
     * @param  $relation - Relation
     * @param $entityToRelationFetchMap - Array of Arrays. Each subarray is a key-value pair.
     *          Key - String - Name of the entity that led to the creation of the transaction
     *                          i.e. value of `type` column in Transactions table
     *          Value - Array - of relationships to fetch for the given Key
     *      For example ['x' => ['y', 'z'], 'a' => ['b']]
     *      This means that when the `type` of transaction is 'x', fetch relations 'y', and 'z'
     *      And when the `type` of transaction is `a`, fetch relations 'b'
     * @param $type - String - The name of the column that has the `source` of the transaction
     * @param $idCol - String - The name of the column that has the  `id` of the `source` of the transaction
     */
    public function fetchAssociatedRelationsWithLoadedEntities(
        $entities,
        $relation,
        $entityToRelationFetchMap = [],
        $idCol = 'entity_id',
        $typeCol = 'type')
    {
        $relationships = [];
        $objects = [];

        // Collects in a map -- ids of different types
        foreach ($entities as $entity)
        {
            $relationships[$entity->$typeCol][] = $entity->$idCol;
        }

        foreach ($relationships as $type => $ids)
        {
            // Finds the list of relations to eager load for the given $type
            $eagerLoadRelations = $entityToRelationFetchMap[$type] ?? [];

            // Queries to eager load the ids of the $type, and also the required relations
            $typeEntities = $this->repo->$type->findManyWithRelations($ids, $eagerLoadRelations, array('*'), true);

            // Creates an id to entity map of the above queried entities
            foreach ($typeEntities as $entity)
            {
                $objects[$entity->getId()] = $entity;
            }
        }

        // Associates, as per the $relation, the above queried relations with the $entity
        foreach ($entities as $entity)
        {
            $typeEntity = $objects[$entity->$idCol];

            $entity->setRelation($relation, $typeEntity);
        }

        return $entities;
    }

    public function fetchDataForInvoice($merchantId, $from, $to)
    {
        $fee = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
                    ->where('transactions.merchant_id', $merchantId)
                    ->where('type', 'payment')
                    ->join('payments', 'transactions.entity_id', '=', 'payments.id')
                    ->whereNotNull('payments.captured_at')
                    ->betweenTime($from, $to)
                    ->sum('transactions.fee');

        $tax = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
                    ->where('transactions.merchant_id', $merchantId)
                    ->where('type', 'payment')
                    ->join('payments', 'transactions.entity_id', '=', 'payments.id')
                    ->whereNotNull('payments.captured_at')
                    ->betweenTime($from, $to)
                    ->sum('transactions.service_tax');

        // Total fee includes our cut + tax
        return [
            'total_fee'         => $fee,
            // This is a combined tax column
            // and includes more than just service_tax (sb cess, kk cess)
            'tax'               => $tax
        ];
    }

    public function fetchTransactionsForAuthorizedRefundedPayments()
    {
        $txns = $this->newQuery()
                     ->where(Transaction\Entity::TYPE, '=', Type::REFUND)
                     ->where(Transaction\Entity::SETTLED, '=', 1)
                     ->whereNull(Transaction\Entity::BALANCE)
                     ->get();

        //
        // Transactions with only refunded authorized payments
        // The previous txns can contain those refunds where balance went to 0
        // after the refund.
        //
        $txns2 = new Base\PublicCollection;

        foreach ($txns as $txn)
        {
            $refund = $txn->source;
            $payment = $refund->payment;

            if ($payment->hasBeenCaptured() === false)
            {
                $txns2->push($txn);
            }
        }

        return $txns2;
    }


    public function fetchMerchantIdListWithGmvAboveThreshold(array $merchantIdList, int $gmvThreshold): array
    {
        return $this->newQueryWithConnection($this->getReportingReplicaConnection())
                    ->whereIn(Entity::MERCHANT_ID, $merchantIdList)
                    ->groupBy(Entity::MERCHANT_ID)
                    ->selectRaw('SUM(' . Entity::CREDIT . ') as gmv,' . Entity::MERCHANT_ID)
                    ->having('gmv', '>=' , $gmvThreshold)
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function filterMerchantsWithFirstTransactionAboveTimestamp(array $merchantIdList, int $timestamp)
    {
        return $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
            ->whereIn(Entity::MERCHANT_ID, $merchantIdList)
            ->groupBy(Entity::MERCHANT_ID)
            ->selectRaw('MIN(' . Entity::CREATED_AT . ') as first_created_at,' . Entity::MERCHANT_ID)
            ->having('first_created_at', '>=', $timestamp)
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function filterMerchantsWithFirstTransactionBetweenTimestamps(
        array $merchantIdList, int $from, int $to, bool $withConnection = true)
    {
        if($withConnection === true)
        {
            $query = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection());
        }
        else
        {
            $query = $this->newQuery();
        }

        return $query->whereIn(Entity::MERCHANT_ID, $merchantIdList)
            ->groupBy(Entity::MERCHANT_ID)
            ->selectRaw('MIN(' . Entity::CREATED_AT . ') as first_created_at,' . Entity::MERCHANT_ID)
            ->having('first_created_at', '>=', $from)
            ->having('first_created_at', '<=', $to)
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function fetchTransactedMerchants(
        string $type, int $from, int $to = null, bool $regularMerchantsOnly = true, bool $withConnection = true)
    {
        $transactionsMerchantIdColumn  = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantIdColumn              = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantOrgIdColumn           = $this->repo->merchant->dbColumn(Merchant\Entity::ORG_ID);
        $merchantParentIdColumn        = $this->repo->merchant->dbColumn(Merchant\Entity::PARENT_ID);

        if($withConnection === true)
        {
            $query = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection());
        }
        else
        {
            $query = $this->newQuery();
        }

        $query = $query->join(Table::MERCHANT, $merchantIdColumn, '=', $transactionsMerchantIdColumn)
            ->select(Entity::MERCHANT_ID)
            ->where($this->dbColumn(Entity::TYPE), '=', $type)
            ->where($this->dbColumn(Entity::CREATED_AT), '>=', $from);

        if ($to !== null)
        {
            $query->where($this->dbColumn(Entity::CREATED_AT), '<=', $to);
        }

        if ($regularMerchantsOnly === true)
        {
            $query->where($merchantOrgIdColumn, '=',  Org\Entity::RAZORPAY_ORG_ID)
                ->where($merchantParentIdColumn, '=', null);
        }

        return $query->distinct()
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();

    }

    public function fetchTotalAmountByTransactionTypeAboveThreshold(
        array $merchantIdList, string $type, int $threshold): array
    {
        return $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
            ->where($this->dbColumn(Entity::TYPE), '=', $type)
            ->whereIn(Entity::MERCHANT_ID, $merchantIdList)
            ->groupBy(Entity::MERCHANT_ID)
            ->selectRaw('SUM(' . Entity::AMOUNT . ') as total,' . Entity::MERCHANT_ID)
            ->having('total', '>=' , $threshold)
            ->get()
            ->toArray();
    }

    public function fetchTotalAmountByTransactionTypeBelowThreshold(
        array $merchantIdList, string $type, int $threshold): array
    {
        return $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
            ->where($this->dbColumn(Entity::TYPE), '=', $type)
            ->whereIn(Entity::MERCHANT_ID, $merchantIdList)
            ->groupBy(Entity::MERCHANT_ID)
            ->selectRaw('SUM(' . Entity::AMOUNT . ') as total,' . Entity::MERCHANT_ID)
            ->having('total', '<' , $threshold)
            ->get()
            ->toArray();
    }

    public function fetchTotalAmountByTransactionTypeWithThresholdInRange(
        array $merchantIdList, string $type, int $threshold): array
    {
        $query = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
            ->where($this->dbColumn(Entity::TYPE), '=', $type)
            ->whereIn(Entity::MERCHANT_ID, $merchantIdList)
            ->groupBy(Entity::MERCHANT_ID)
            ->selectRaw('SUM(' . Entity::AMOUNT . ') as total,' . Entity::MERCHANT_ID);

        $query1 = clone $query;
        $query2 = clone $query;

        $merchantsGmvList = $query->having('total', '>=' , $threshold)
                        ->get()
                        ->toArray();

        $merchantsNinetyOnePercentileGmvList = $query1->having('total', '>=' , $threshold * 0.91)
                        ->having('total', '<' , $threshold)
                        ->get()
                        ->toArray();

        $merchantsNinetyPercentileGmvList = $query2->having('total', '>=' , $threshold * 0.90)
                        ->having('total', '<' , $threshold * 0.91)
                        ->get()
                        ->toArray();

        return [$merchantsGmvList, $merchantsNinetyOnePercentileGmvList, $merchantsNinetyPercentileGmvList];
    }

    public function updateSettledAtToNow($txn)
    {
        $id = $txn->getId();

        return $this->newQuery()
                    ->where(Transaction\Entity::ID, '=', $id)
                    ->where(Transaction\Entity::SETTLED, '=', false)
                    ->update([Transaction\Entity::SETTLED_AT  => 1]);
    }

    /**
     * @param       $txns   - Array of transaction entities to be updated
     * @param array $values - Array. Key - Column name, Value - Column value
     *
     * @throws Exception\LogicException
     */
    public function settled($txns, array $values, $logging = true)
    {
        $txnCount = $txns->count();

        if ($txnCount === 0)
        {
            return;
        }

        $ids = $txns->getIds();

        $batchedIds = array_chunk($ids, 1000);

        if ($logging === true)
        {
            $startTime = microtime(true);

            $this->trace->info(TraceCode::SETTLEMENT_TXN_UPDATE_BEGIN,
                [
                    'merchant_id' => $txns->first()->getMerchantId(),
                    'txn_count'   => $txnCount,
                ]);

        }

        foreach ($batchedIds as $batch)
        {
            $count = $this->newQuery()
                          ->whereIn(Transaction\Entity::ID, $batch)
                          ->where(Transaction\Entity::SETTLED, 0)
                          ->whereNull(Transaction\Entity::SETTLEMENT_ID)
                          ->update($values);

            $expected = count($batch);

            if ($logging === true)
            {
                $this->trace->info(TraceCode::SETTLEMENT_TXN_BATCH_UPDATED,
                    [
                        'merchant_id'       => $txns->first()->getMerchantId(),
                        'txn_batch_count'   => $expected,
                        'time_taken'        => microtime(true) - $startTime,
                    ]);
            }

            if ($count !== $expected)
            {
                throw new Exception\LogicException(
                    'Failed to update expected number of rows.',
                    null,
                    [
                        'expected' => $expected,
                        'updated'  => $count,
                    ]);
            }
        }

        if ($logging === true)
        {
            $timeTaken = microtime(true) - $startTime;

            $this->trace->info(TraceCode::SETTLEMENT_TXN_UPDATE_TIME_TAKEN, ['time_taken' => $timeTaken]);
        }

        return $txnCount;
    }

    public function updateSettlementId($txns, $settlementId)
    {
        if ($txns->count() === 0)
        {
            return;
        }

        $ids = $txns->getIds();

        $values = [Transaction\Entity::SETTLEMENT_ID  => $settlementId];

        $count = $this->newQuery()
                      ->whereIn(Transaction\Entity::ID, $ids)
                      ->update($values);

        $expected = count($ids);

        if ($count !== $expected)
        {
            throw new Exception\LogicException(
                'Failed to update expected number of rows.',
                null,
                [
                    'expected'      => $expected,
                    'updated'       => $count,
                    'settlement_id' => $settlementId
                ]);
        }

        return $count;
    }

    /**
     * @param array $txnIds - Array of transaction ids to be updated
     * @param array $values - Array. Key - Column name, Value - Column value
     * @param boolean $logging
     *
     * @throws Exception\LogicException
     */
    public function updateAsSettled($txnIds, array $values, $logging = true)
    {
        if (sizeof($txnIds) === 0)
        {
            return;
        }

        $batchedIds = array_chunk($txnIds, 500);

        if ($logging === true)
        {
            $startTime = microtime(true);
        }

        foreach ($batchedIds as $batch)
        {
            $count = $this->newQuery()
                ->whereIn(Transaction\Entity::ID, $batch)
                ->where(Transaction\Entity::SETTLED, 0)
                ->whereNull(Transaction\Entity::SETTLEMENT_ID)
                ->update($values);

            $expected = count($batch);

            if ($count !== $expected)
            {
                throw new Exception\LogicException(
                    'Failed to update expected number of rows.',
                    null,
                    [
                        'expected' => $expected,
                        'updated'  => $count,
                    ]);
            }
        }

        if ($logging === true)
        {
            $timeTaken = microtime(true) - $startTime;

            $this->trace->info(TraceCode::SETTLEMENT_TXN_UPDATE_TIME_TAKEN, ['time_taken' => $timeTaken]);
        }
    }

    public function fetchSettledTransactionsWithoutSettlementId($type, $count)
    {
        return $this->newQuery()
            ->select(Entity::ID)
            ->where(Transaction\Entity::TYPE, $type)
            ->where(Transaction\Entity::SETTLED, 1)
            ->whereNull(Transaction\Entity::SETTLEMENT_ID)
            ->take($count)
            ->get()->pluck('id')->all();
    }

    public function updateSettledToFalse($type, $txnIds)
    {
        if (count($txnIds) === 0)
        {
            $this->trace->info(
                TraceCode::FUND_ACCOUNT_VALIDATION_TRANSACTION_FIX_COMPLETED);

            return 0;
        }

        $values = [Transaction\Entity::SETTLED  => false];

        $count = $this->newQuery()
            ->whereIn(Transaction\Entity::ID, $txnIds)
            ->where(Transaction\Entity::TYPE, $type)
            ->where(Transaction\Entity::SETTLED, true)
            ->whereNull(Transaction\Entity::SETTLEMENT_ID)
            ->update($values);

        return $count;
    }

    /**
     * Update channel for unsettled transactions
     *
     * @param string $merchantId
     * @param array $transactionIds
     * @param string $channel
     * @return mixed
     */
    public function bulkChannelUpdateForMerchantTransactions(
        string $merchantId, array $transactionIds, string $channel)
    {
        $attributes = [Entity::CHANNEL => $channel];

        $batchedIds = array_chunk($transactionIds, 1000);

        $count = 0;

        foreach ($batchedIds as $batch)
        {
            $query = $this->newQuery()
                            ->where(Entity::MERCHANT_ID, $merchantId)
                            ->whereIn(Entity::ID, $batch);

            $updated = $query->update($attributes);

            $count += $updated;
        }

        return $count;
    }

    /**
     * Use this method with caution.
     * It updates channel for all transactions that belong to given settlement_id.
     * It should be run only for a settlement that is in Failed status.
     *
     * @param $settlementId
     * @param $transactionIds
     * @param $channel
     * @return mixed
     */
    public function updateChannelForSettlement($settlementId, $transactionIds, $channel)
    {
        $values = [Transaction\Entity::CHANNEL => $channel];

        $count = $this->newQuery()
                      ->where(Transaction\Entity::SETTLEMENT_ID, $settlementId)
                      ->whereIn(Transaction\Entity::ID, $transactionIds)
                      ->update($values);

        return $count;
    }

    public function findByEntityId($entityId, $merchant, $fail = false)
    {
        $txn = $this->newQuery()
                    ->where(Transaction\Entity::ENTITY_ID, '=', $entityId)
                    ->merchantId($merchant->getId())
                    ->first();

        if (($txn === null) and
            ($fail))
        {
            throw new Exception\LogicException(
                'Failed to find transaction with entity_id',
                null,
                [
                    'entity_id'     => $entityId,
                    'merchant_id'   => $merchant->getId(),
                ]);
        }

        return $txn;
    }

    public function findByEntityIdWithoutMerchant($entityId)
    {
        $txn = $this->newQuery()
            ->where(Transaction\Entity::ENTITY_ID, '=', $entityId)
            ->first();

        return $txn;
    }

    public function fetchBySettlement($setl, $txnToRelationFetchMap)
    {
        $txns = $this->newQuery()
                    ->where(Transaction\Entity::SETTLEMENT_ID, '=', $setl->getId())
                    ->with('merchant', 'settlement')
                    ->get();

        $txns = $this->fetchAssociatedRelationsWithLoadedEntities($txns, 'source', $txnToRelationFetchMap);

        return $txns;
    }

    public function fetchBySettlementIdAndSource($settlementId, $source, $skip, $limit, $sourceId)
    {
        $result = $this->newQueryWithConnection($this->getSlaveConnection())
                       ->where(Transaction\Entity::SETTLEMENT_ID, '=', $settlementId)
                       ->where(Transaction\Entity::TYPE, '=', $source);

        if($sourceId != null)
        {
            $result = $result->where(Transaction\Entity::ENTITY_ID, '=', $sourceId);
        }

        return $result->take($limit)
                      ->skip($skip)
                      ->get();
    }

    /**
     * Updated reconciled_at to current time for given entities
     *
     * @param $entityIds
     * @param $reconciledType
     * @return mixed
     */
    public function bulkReconciliationUpdate($entityIds, $reconciledType = ReconciledType::NA)
    {
        $time = time();

        $attributes = [
                        Entity::RECONCILED_AT   => $time,
                        Entity::RECONCILED_TYPE => $reconciledType
                      ];

        return $this->newQuery()
                    ->whereIn(Entity::ENTITY_ID, $entityIds)
                    ->update($attributes);
    }

    public function getCancelledBilldeskPaymentTransactions(int $limit = 200)
    {
        $billdeskPaymentId = Billdesk\Entity::dbColumn(Billdesk\Entity::PAYMENT_ID);
        $billdeskRefStatus = Billdesk\Entity::dbColumn('RefStatus');

        $paymentId = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentStatus = $this->repo->payment->dbColumn(Payment\Entity::STATUS);

        $transactionEntityId = $this->dbColumn(Entity::ENTITY_ID);
        $transactionReconciledAt = $this->dbColumn(Entity::RECONCILED_AT);

        $transactionData = $this->dbColumn('*');

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
                    ->select($transactionData)
                    ->join(Table::PAYMENT, $paymentId, '=', $transactionEntityId)
                    ->join(Table::BILLDESK, $billdeskPaymentId, '=', $paymentId)
                    ->where($billdeskRefStatus, '=', Billdesk\RefundStatus::CANCELLED)
                    ->where($paymentStatus, '=', Payment\Status::REFUNDED)
                    ->whereNull($transactionReconciledAt)
                    ->limit($limit)
                    ->get();
    }

    /**
     * select `transactions`.* from `transactions`
     * inner join `refunds` on `refunds`.`id` = `transactions`.`entity_id`
     * inner join `payments` on `refunds`.`payment_id` = `payments`.`id`
     * inner join `billdesk` on `billdesk`.`payment_id` = `payments`.`id`
     * where `billdesk`.`RefStatus` = '0699'
     * and `payments`.`status` = 'refunded'
     * and `transactions`.`reconciled_at` is null
     *
     * @param $limit
     * @return mixed
     */
    public function getCancelledBilldeskPaymentRefundTransactions(int $limit = 200)
    {
        $billdeskPaymentId = Billdesk\Entity::dbColumn(Billdesk\Entity::PAYMENT_ID);
        $billdeskRefStatus = Billdesk\Entity::dbColumn('RefStatus');

        $paymentId = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentStatus = $this->repo->payment->dbColumn(Payment\Entity::STATUS);

        $refundId = $this->repo->refund->dbColumn(Refund\Entity::ID);
        $refundPaymentId = $this->repo->refund->dbColumn(Refund\Entity::PAYMENT_ID);

        $transactionEntityId = $this->dbColumn(Entity::ENTITY_ID);
        $transactionReconciledAt = $this->dbColumn(Entity::RECONCILED_AT);

        $transactionData = $this->dbColumn('*');

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
                    ->select($transactionData)
                    ->join(Table::REFUND, $refundId, '=', $transactionEntityId)
                    ->join(Table::PAYMENT, $refundPaymentId, '=', $paymentId)
                    ->join(Table::BILLDESK, $billdeskPaymentId, '=', $paymentId)
                    ->where($billdeskRefStatus, '=', Billdesk\RefundStatus::CANCELLED)
                    ->where($paymentStatus, '=', Payment\Status::REFUNDED)
                    ->whereNull($transactionReconciledAt)
                    ->limit($limit)
                    ->get();
    }

    protected function addQueryParamEntityId($query, $params)
    {
        $entityId = $params[Entity::ENTITY_ID];

        Entity::stripSignWithoutValidation($entityId);

        $query->where(Entity::ENTITY_ID, '=', $entityId);
    }

    protected function addQueryParamReconciled($query, $params)
    {
        $reconciled = $params[Entity::RECONCILED];

        if ($reconciled === '0')
        {
            $query->whereNull(Entity::RECONCILED_AT);
        }
        else if ($reconciled === '1')
        {
            $query->whereNotNull(Entity::RECONCILED_AT);
        }
    }

    public function getTransactionsToBeMigrated()
    {
        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
                    ->select('transactions.*')
                    ->join(Table::PAYMENT, Entity::ENTITY_ID, '=', 'payments.id')
                    ->where(Entity::TYPE, 'payment')
                    ->where(Entity::GRATIS, false)
                    ->where('transactions.service_tax', '>', 0)
                    ->whereNotNull(Payment\Entity::CAPTURED_AT)
                    ->whereNotIn("transactions.id", function($query)
                    {
                        $query->select(FeeBreakup\Entity::TRANSACTION_ID)
                              ->from(Table::FEE_BREAKUP);
                    });

        return $query->limit(1000)->get();
    }

    public function getTransactionForReport($merchantId, $from, $to)
    {
        $txnIds = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
                       ->where("transactions.merchant_id", $merchantId)
                       ->where(Entity::TYPE, 'payment')
                       ->join(Table::PAYMENT, Entity::ENTITY_ID, '=', 'payments.id')
                       ->whereNotNull(Payment\Entity::CAPTURED_AT)
                       ->betweenTime($from, $to)
                       ->select("transactions.id")
                       ->get();

        return $txnIds;
    }

    public function getTransactionsToSetPricingId()
    {
        $transactions = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
                            ->select('transactions.*')
                            ->join(Table::PAYMENT, Entity::ENTITY_ID, '=', 'payments.id')
                            ->where(Entity::TYPE, 'payment')
                            ->whereNotNull(Payment\Entity::CAPTURED_AT)
                            ->whereNull(Entity::PRICING_RULE_ID)
                            ->get();

        return $transactions;
    }

    public function fetchForPayment(Payment\Entity $payment)
    {
        if ($payment->hasRelation('transaction'))
        {
            return $payment->transaction;
        }

        $transaction = $this->findOrFail($payment->getTransactionId());

        $payment->setRelation('transaction', $transaction);

        return $transaction;
    }

    public function fetchGratisTransactions(string $merchantId, int $timestamp)
    {
        $createdAt = $this->dbColumn(Entity::CREATED_AT);

        $paymentId = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $transactionData = $this->dbColumn('*');

        return $this->newQuery()
                    ->where(Entity::TYPE, Type::PAYMENT)
                    ->where($createdAt, '>=', $timestamp)
                    ->where(Transaction\Entity::GRATIS, '=', 1)
                    ->merchantId($merchantId)
                    ->join(Table::PAYMENT, Entity::ENTITY_ID, '=', $paymentId)
                    ->whereNotNull(Payment\Entity::CAPTURED_AT)
                    ->select($transactionData)
                    ->get();
    }

    /**
     * calcualtes the sum of `fee` and `tax` of all the transaction created for a merchant in given time frame.
     * Conciders only transactions whose type is not in `IGNORE_ENTITIES_FROM_MERCHANT_INVOICE`
     * Only consider transactions made through primary balance of merchant
     *
     * @param string $merchantId
     * @param int    $start
     * @param int    $end
     *
     * @return mixed
     */
    public function fetchFeesAndTaxForTransactions(
        string $merchantId,
        int $start,
        int $end)
    {
        //
        // There is no variation based on transaction type here.
        // All the transaction here will be part of `OTHERS` section
        // Because this will look at only transaction which not in ignore list
        // And Payment is part of ignore list and only payment has the type difference
        //
        $balanceIDColumn                    = $this->repo->balance->dbColumn(Entity::ID);
        $transactionsBalanceIDColumn        = $this->repo->transaction->dbColumn(Entity::BALANCE_ID);
        $transactionsCreatedATColumn        = $this->repo->transaction->dbColumn(Entity::CREATED_AT);
        $transactionsTypeColumn             = $this->repo->transaction->dbColumn(Entity::TYPE);
        $balanceTypeColumn                  = $this->repo->balance->dbColumn(Balance\Entity::TYPE);

        return $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
                    ->selectRaw('SUM(' . Entity::TAX .') AS tax, SUM(' . Entity::FEE . ') AS fee')
                    ->join(Entity::BALANCE, $transactionsBalanceIDColumn, $balanceIDColumn)
                    ->whereBetween($transactionsCreatedATColumn, [$start, $end])
                    ->merchantId($merchantId)
                    ->whereNotIn($transactionsTypeColumn, Type::IGNORE_ENTITIES_FROM_MERCHANT_INVOICE)
                    ->where($balanceTypeColumn, Product::PRIMARY)
                    ->first();
    }

    public function fetchFeesAndTaxForPrimaryFundAccountValidations(string $merchantId,
                                                                    int $start,
                                                                    int $end)
    {
        $balanceIDColumn             = $this->repo->balance->dbColumn(Entity::ID);
        $transactionsBalanceIDColumn = $this->repo->transaction->dbColumn(Entity::BALANCE_ID);
        $transactionsCreatedATColumn = $this->repo->transaction->dbColumn(Entity::CREATED_AT);
        $transactionsTypeColumn      = $this->repo->transaction->dbColumn(Entity::TYPE);
        $balanceTypeColumn           = $this->repo->balance->dbColumn(Balance\Entity::TYPE);

        return $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
                    ->selectRaw('SUM(' . Entity::TAX . ') AS tax, SUM(' . Entity::FEE . ') AS fee')
                    ->join(Entity::BALANCE, $transactionsBalanceIDColumn, $balanceIDColumn)
                    ->whereBetween($transactionsCreatedATColumn, [$start, $end])
                    ->merchantId($merchantId)
                    ->where($transactionsTypeColumn, Type::FUND_ACCOUNT_VALIDATION)
                    ->where($balanceTypeColumn, Product::PRIMARY)
                    ->first();
    }

    /**
     * calculates the sum of `fee` and `tax` for the transaction of particular type created for a merchant in given time frame.
     *
     * @param string $merchantId
     * @param string $type
     * @param int $start
     * @param int $end
     *
     * @return mixed
     */
    public function fetchFeesAndTaxForTransactionsByType(
        string $merchantId,
        string $type,
        int $start,
        int $end)
    {
        //
        // There is no variation based on transaction type here.
        // All the transaction here will be part of `OTHERS` section
        // Because this will look at only transaction which not in ignore list
        // And Payment is part of ignore list and only payment has the type difference
        //
        return $this->newQuery()
                    ->selectRaw(
                        'SUM(' . Entity::TAX .') AS tax, SUM(' . Entity::FEE . ') AS fee')
                    ->where(Entity::TYPE, $type)
                    ->whereBetween(Entity::CREATED_AT, [$start, $end])
                    ->merchantId($merchantId)
                    ->first();
    }

    /**
     * Raw sql query :
     *
     *  select FROM_UNIXTIME(transactions.created_at + 19800,'%D %M, %Y') AS date,
     *  COUNT(transactions.entity_id) AS total_count,SUM(transactions.amount)/100 AS total_amount,
     *  COUNT(CASE
     *      WHEN transactions.reconciled_at is not null
     *          THEN transactions.id
     *          END) recon_count,
     *  COUNT(CASE
     *      WHEN transactions.reconciled_at is null
     *          THEN transactions.id
     *          END) unrecon_count,
     *  SUM(CASE
     *      WHEN transactions.reconciled_at is not null
     *          THEN transactions.amount
     *          ELSE 0
     *          END)/100 recon_amount,
     *  SUM(CASE
     *      WHEN transactions.reconciled_at is null
     *          THEN transactions.amount
     *          ELSE 0
     *          END)/100 unrecon_amount,
     *  (Case WHEN payments.method in ('card','emi')
     *          THEN terminals.gateway_acquirer
     *          ELSE payments.gateway, `payments`.`method`
     *          END) gateway from `transactions`
     *  inner join `payments` on `entity_id` = `payments`.`id`
     *  inner join `terminals` on `terminal_id` = `terminals`.`id`
     *  where `payments`.`gateway` in (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) and
     *  `transactions`.`created_at` between ? and ? and `transactions`.`amount` > 0
     *  group by `date`, `gateway`, `payments`.`method` order by `date` desc
     *
     * @param int   $from
     * @param int   $to
     *
     * @return array
     */
    public function fetchPaymentReconStatusSummary(int $from, int $to): array
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $paymentIdColumn = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $terminalIdColumn = $this->repo->terminal->dbColumn(Terminal\Entity::ID);

        $query = $this->getSelectParamsQueryForReconSummary(ConstantEntity::PAYMENT);

        //
        // Adding join with payment and terminal
        //
        $query->join(Table::PAYMENT, Entity::ENTITY_ID, '=', $paymentIdColumn);

        //
        // Doing a left join because bank_transfer payments currently don't have terminal
        // TODO: Remove this when bank_transfers use terminals in payment flow
        //
        $query->leftJoin(Table::TERMINAL, Payment\Entity::TERMINAL_ID, '=', $terminalIdColumn);

        $this->getQueryClausesForReconSummary($query, $from, $to, ConstantEntity::PAYMENT);

        $reconciledPaymentsSummary = $query->get()
                                           ->toArray();

        return $reconciledPaymentsSummary;
    }

    protected function getQueryClausesForUnreconSummaryByGateway($query, array $dates,  string $entityName, string $gateway)
    {
        $transactionAmountColumn = $this->dbColumn(Entity::AMOUNT);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentGatewayColumn = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $transactionReconciledAtColumn = $this->dbColumn(Entity::RECONCILED_AT);

        if ($entityName === ConstantEntity::PAYMENT)
        {
            $timestampColumn = $this->dbColumn(Entity::CREATED_AT);

            $settledByColumn = $this->repo->payment->dbColumn(Payment\Entity::SETTLED_BY);
        }
        else
        {
            $timestampColumn = $this->repo->refund->dbColumn(Refund\Entity::PROCESSED_AT);

            $settledByColumn = $this->repo->refund->dbColumn(Payment\Entity::SETTLED_BY);
        }

        // To exclude e-mandate transactions and non-active gateways, we put 'where' clause here
        $query->where($transactionAmountColumn, '>', 0)
              ->where($paymentGatewayColumn, '=', $gateway);

        $query->where(function($query) use($dates, $timestampColumn)
        {
            foreach ($dates as $index => $date)
            {
                $from = $date;

                $to = Carbon::createFromTimestamp($date)->addDays(1)->getTimestamp();

                if ($index === 0)
                {
                    $query->whereBetween($timestampColumn, [$from, $to]);
                }
                else
                {
                    $query->orWhereBetween($timestampColumn, [$from, $to]);
                }
            }
        });

        // Exclude reconciled and direct settlement txns
        $query->whereNull($transactionReconciledAtColumn)
              ->where($settledByColumn, '=', 'Razorpay');

        $query->groupBy('date', 'gateway', $paymentMethodColumn)
              ->orderBy('date', 'desc');

        return $query;
    }

    /**
     * Raw SQL Query
     *
     *    (select
     *    FROM_UNIXTIME(transactions.created_at + 19800,\"%D %M, %Y\") AS date,
     *    COUNT(transactions.id) count,
     *    (Case WHEN payments.method in ("card","emi")
     *    THEN terminals.gateway_acquirer
     *    ELSE terminals.gateway
     *    END) gateway,
     *    payments.method from `transactions`
     *    inner join `payments` on `entity_id` = `payments`.`id`
     *    inner join `terminals` on `terminal_id` = `terminals`.`id`
     *    where `transactions`.`amount` > ?
     *    and `payments`.`gateway` = ?
     *    and (`transactions`.`created_at` between ? and ?)
     *    and `transactions`.`reconciled_at` is null
     *    group by `date`, `gateway`, `payments`.`method`
     *    order by `date` desc)
     *    union all
     *    (select FROM_UNIXTIME(transactions.created_at + 19800,\"%D %M, %Y\") AS date,
     *    COUNT(transactions.id) count,
     *    (Case WHEN payments.method in ("card","emi")
     *    THEN terminals.gateway_acquirer
     *    ELSE terminals.gateway
     *    END) gateway,
     *    payments.method from `transactions`
     *    inner join `payments` on `entity_id` = `payments`.`id`
     *    inner join `terminals` on `terminal_id` = `terminals`.`id`
     *    where `transactions`.`amount` > ?
     *    and `payments`.`gateway` = ?
     *    and (`transactions`.`created_at` between ? and ?)
     *    and `transactions`.`reconciled_at` is null
     *    group by `date`, `gateway`, `payments`.`method`
     *    order by `date` desc)
    ...
     */
    public function fetchPaymentUnreconStatusSummary(array $gatewaysWithDate): array
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $unionQueries= [];

        $paymentIdColumn = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $terminalIdColumn = $this->repo->terminal->dbColumn(Terminal\Entity::ID);

        foreach ($gatewaysWithDate as $gateway => $dates)
        {
            $query = $this->getMinimumSelectParamsQueryForUnreconSummary(ConstantEntity::PAYMENT);

            $query->join(Table::PAYMENT, Entity::ENTITY_ID, '=', $paymentIdColumn)
                  ->join(Table::TERMINAL, Payment\Entity::TERMINAL_ID, '=', $terminalIdColumn);

            $query = $this->getQueryClausesForUnreconSummaryByGateway($query, $dates, ConstantEntity::PAYMENT, $gateway);

            $unionQueries[] = $query;
        }

        $unionQuery = null;

        foreach ($unionQueries as $unionQueryElement)
        {
            $unionQuery = $unionQuery ? $unionQuery->unionAll($unionQueryElement) : $unionQueryElement;
        }

        $reconciledPaymentsSummary = $unionQuery->get()
                                                ->toArray();

        return $reconciledPaymentsSummary;
    }

    /**
     * Raw sql query :
     *
     *  select FROM_UNIXTIME(transactions.created_at + 19800,'%D %M, %Y') AS date,
     *  COUNT(transactions.entity_id) AS total_count,SUM(transactions.amount)/100 AS total_amount,
     *  COUNT(CASE
     *  WHEN transactions.reconciled_at is not null
     *      THEN transactions.id
     *      END) recon_count,
     *  COUNT(CASE
     *  WHEN transactions.reconciled_at is null
     *      THEN transactions.id
     *      END) unrecon_count,
     *  SUM(CASE
     *  WHEN transactions.reconciled_at is not null
     *      THEN transactions.amount
     *      ELSE 0
     *      END)/100 recon_amount,
     *  SUM(CASE
     *  WHEN transactions.reconciled_at is null
     *      THEN transactions.amount
     *      ELSE 0
     *      END)/100 unrecon_amount,
     *  (Case
     *  WHEN payments.method in ('card','emi')
     *      THEN terminals.gateway_acquirer
     *      ELSE payments.gateway
     *      END) gateway, `payments`.`method`
     *  from `transactions` inner join `refunds` on `entity_id` = `refunds`.`id`
     *  inner join `payments` on `payments`.`id` = `payment_id`
     *  inner join `terminals` on `terminal_id` = `terminals`.`id`
     *  where `payments`.`gateway` in (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     *  and `refunds`.`status` = ? and `transactions`.`created_at` between ? and ? and `transactions`.`amount` > 0
     *  group by `date`, `gateway`, `payments`.`method` order by `date
     *
     *  Note :  Transaction amount should be greater than 0 to exclude e-mandate transactions of 0 amount.
     *          Such transactions are not considered for reconciliation.
     *
     * @param int   $from
     * @param int   $to
     *
     * @return array
     */

    public function fetchRefundReconStatusSummary(int $from, int $to): array
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $paymentIdColumn = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $terminalIdColumn = $this->repo->terminal->dbColumn(Terminal\Entity::ID);

        $query = $this->getSelectParamsQueryForReconSummary(ConstantEntity::REFUND);

        $this->addRefundJoinForReconSummary($query);

        //
        // Adding join with payment and terminal
        //
        $query->join(Table::PAYMENT, $paymentIdColumn, '=', Refund\Entity::PAYMENT_ID)
              ->join(Table::TERMINAL, Payment\Entity::TERMINAL_ID, '=', $terminalIdColumn);

        $this->getQueryClausesForReconSummary($query, $from, $to, ConstantEntity::REFUND);

        $reconciledRefundsSummary = $query->get()
                                          ->toArray();

        return $reconciledRefundsSummary;
    }

    public function fetchRefundUnreconStatusSummary(array $gatewaysWithDate): array
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $unionQueries = [];

        $paymentIdColumn = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $terminalIdColumn = $this->repo->terminal->dbColumn(Terminal\Entity::ID);

        foreach ($gatewaysWithDate as $gateway => $dates)
        {
            $query = $this->getMinimumSelectParamsQueryForUnreconSummary(ConstantEntity::REFUND);

            $this->addRefundJoinForReconSummary($query);

            $query->join(Table::PAYMENT, $paymentIdColumn, '=', Refund\Entity::PAYMENT_ID)
                  ->join(Table::TERMINAL, Payment\Entity::TERMINAL_ID, '=', $terminalIdColumn);

            $query = $this->getQueryClausesForUnreconSummaryByGateway($query, $dates, ConstantEntity::REFUND, $gateway);

            $unionQueries[] = $query;
        }

        $unionQuery = null;

        foreach ($unionQueries as $unionQueryElement)
        {
            $unionQuery = $unionQuery ? $unionQuery->unionAll($unionQueryElement) : $unionQueryElement;
        }

        $reconciledPaymentsSummary = $unionQuery->get()
                                                ->toArray();

        return $reconciledPaymentsSummary;
    }

    protected function getSelectParamsQueryForReconSummary(string $entityName)
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $transactionPaymentIdColumn = $this->dbColumn(Entity::ENTITY_ID);

        $transactionAmountColumn = $this->dbColumn(Entity::AMOUNT);

        $transactionReconciledAtColumn = $this->dbColumn(Entity::RECONCILED_AT);

        $transactionIdColumn = $this->dbColumn(Entity::ID);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $terminalGatewayAcquirerColumn = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY_ACQUIRER);

        $terminalGatewayColumn = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY);

        $timestampColumn = $this->dbColumn(Entity::CREATED_AT);

        //
        // For refunds : use 'processedAt' instead of txn createdAt
        // Bcoz some refunds got success recently which were created
        // 1-2 months ago and thus we do not get these in recon summary
        // report if we use txn createdAt.
        //
        if ($entityName === ConstantEntity::REFUND)
        {
            $timestampColumn = $this->repo->refund->dbColumn(Refund\Entity::PROCESSED_AT);
        }

        $params =  'COUNT('.$transactionPaymentIdColumn.') AS total_count'. ','.
            'SUM('.$transactionAmountColumn.')/100 AS total_amount'.','.

            'COUNT(CASE
                       WHEN '.$transactionReconciledAtColumn.' is not null
                            THEN '.$transactionIdColumn.'
                        END) recon_count,
                 COUNT(CASE
                       WHEN '.$transactionReconciledAtColumn.' is null
                            THEN '.$transactionIdColumn.'
                        END) unrecon_count,

                 SUM(CASE
                     WHEN '.$transactionReconciledAtColumn.' is not null
                        THEN '.$transactionAmountColumn.'
                        ELSE 0
                     END)/100 recon_amount,
                 SUM(CASE
                     WHEN '.$transactionReconciledAtColumn.' is null
                        THEN '.$transactionAmountColumn.'
                        ELSE 0
                     END)/100 unrecon_amount'. ',' .
            '(Case WHEN '. $paymentMethodColumn .' in ( "'. Payment\Method::CARD . '","'. Payment\Method::EMI .'")'.'
                    THEN '. $terminalGatewayAcquirerColumn . '
                    ELSE '. $terminalGatewayColumn . '
                  END) gateway, '. $paymentMethodColumn;

        $dateCol = 'FROM_UNIXTIME(' . $timestampColumn . ' + 19800,"%D %M, %Y") AS date';

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->selectRaw($dateCol . ',' . $params);

        return $query;
    }

    protected function getMinimumSelectParamsQueryForUnreconSummary(string $entityName)
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $transactionIdColumn = $this->dbColumn(Entity::ID);

        $timestampColumn = $this->dbColumn(Entity::CREATED_AT);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $terminalGatewayAcquirerColumn = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY_ACQUIRER);

        $terminalGatewayColumn = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY);

        //
        // For refunds : use 'processedAt' instead of txn createdAt
        // Bcoz some refunds got success recently which were created
        // 1-2 months ago and thus we do not get these in recon summary
        // report if we use txn createdAt.
        //
        if ($entityName === ConstantEntity::REFUND)
        {
                $timestampColumn = $this->repo->refund->dbColumn(Refund\Entity::PROCESSED_AT);
        }

        $params = 'COUNT('.$transactionIdColumn.') count,'.
            '(Case WHEN '. $paymentMethodColumn .' in ( "'. Payment\Method::CARD . '","'. Payment\Method::EMI .'")'.'
                THEN '. $terminalGatewayAcquirerColumn . '
            ELSE '. $terminalGatewayColumn . '
            END) gateway, '. $paymentMethodColumn;

        $dateCol = 'FROM_UNIXTIME(' . $timestampColumn . ' + 19800,"%D %M, %Y") AS date';

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                ->selectRaw($dateCol . ',' . $params);

        return $query;
    }

    /**
     * Raw Sql Query :
     * This query is used in union query of all gateways.
     *
     * Payments:
     * select transactions.created_at,payments.id as payment_id,
     * payments.method as payment_method,payments.amount as payment_amount,
     * payments.status as payment_status,payments.disputed as payment_disputed,
     * payments.merchant_id as payment_merchant_id,
     * payments.terminal_id as payment_terminal_id,
     * payments.reference2 as payment_reference2,
     * payments.captured_at as payment_captured_at,
     * payments.authorized_at as payment_authorized_at,
     * payments.amount_refunded as payment_amount_refunded,
     * (Case
     *   WHEN payments.method in ('card','emi')
     *   THEN terminals.gateway_acquirer
     *   ELSE payments.gateway
     * END) gateway,
     * terminals.gateway_terminal_id
     * from `transactions`
     * inner join `payments` on `entity_id` = `payments`.`id`
     * inner join `terminals` on `terminal_id` = `terminals`.`id`
     * where `payments`.`gateway` = ? and `reconciled_at` is null and
     * `transactions`.`created_at` between ? and ?
     * order by `transactions`.`created_at` asc limit 100
     *
     * Refunds:
     * select transactions.created_at,refunds.id as refund_id,
     * refunds.amount as refund_amount,refunds.status as refund_status,
     * payments.id as payment_id,payments.method as payment_method,
     * payments.amount as payment_amount,payments.status as payment_status,
     * payments.disputed as payment_disputed,
     * payments.merchant_id as payment_merchant_id,
     * payments.terminal_id as payment_terminal_id,
     * payments.reference2 as payment_reference2,
     * payments.captured_at as payment_captured_at,
     * payments.authorized_at as payment_authorized_at,
     * payments.amount_refunded as payment_amount_refunded,
     * (Case
     *   WHEN payments.method in ('card','emi')
     *   THEN terminals.gateway_acquirer
     *   ELSE payments.gateway
     * END) gateway,
     * terminals.gateway_terminal_id
     * from `transactions`
     * inner join `refunds` on `entity_id` = `refunds`.`id`
     * inner join `payments` on `payments`.`id` = `payment_id`
     * inner join `terminals` on `terminal_id` = `terminals`.`id`
     * where `refunds`.`status` = ? and `payments`.`gateway` = ? and
     * `reconciled_at` is null and
     * `transactions`.`created_at` between ? and ? and `transactions`.`amount` > 0
     * order by `transactions`.`created_at` asc limit 100
     *
     * Note : Using case query as requires gateway_acquirer only in case of card gateways.
     *        Transaction amount should be greater than 0 to exclude e-mandate transactions of 0 amount.
     *        Such transactions are not considered for reconciliation.
     *
     * @param int   $from
     * @param int   $to
     * @param array $gateways
     * @param int   $limit
     * @param array $paymentParams
     * @param array $refundParams
     *
     * @return array
     */
    public function fetchUnreconciledEntitiesBetweenDates(
                                        int $from,
                                        int $to,
                                        array $gateways,
                                        int $limit,
                                        array $paymentParams = [],
                                        array $refundParams = []
                                        ): array
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $paymentIdColumn = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $refundParams = $this->repo->refund->getAliasesForRefundsDbColumns($refundParams);

        $paymentParams = $this->repo->payment->getAliasesForPaymentsDbColumns($paymentParams);

        $terminalIdColumn = $this->repo->terminal->dbColumn(Terminal\Entity::ID);

        $unionQueries = [];

        foreach ($gateways as $gateway)
        {
            $query = $this->getSelectQueryForUnreconciledEntites($paymentParams, $refundParams);

            if (empty($refundParams) === false)
            {
                $entityName = ConstantEntity::REFUND;

                $this->addRefundJoinForReconSummary($query);

                $query->join(Table::PAYMENT, $paymentIdColumn, '=', Refund\Entity::PAYMENT_ID);
            }
            else
            {
                $entityName = ConstantEntity::PAYMENT;

                $query->join(Table::PAYMENT, Entity::ENTITY_ID, '=', $paymentIdColumn);
            }

            if (Payment\Gateway::isNonTerminalGateway($gateway) === false)
            {
                $query->join(Table::TERMINAL, Payment\Entity::TERMINAL_ID, '=', $terminalIdColumn);
            }
            else
            {
                //
                // Still need to join because there are columns being selected from there and
                // removing those is to significant a change for a temporary hack like this
                //
                // TODO: Remove this when bank_transfers use terminals in payment flow
                //
                $query->leftJoin(Table::TERMINAL, Payment\Entity::TERMINAL_ID, '=', $terminalIdColumn);
            }

            $this->getQueryClausesForUnreconciledEntities($query, $from, $to, $gateway, $limit, $entityName);

            $unionQueries[] = $query;
        }

        $unionQuery = null;

        // Generate a union query
        foreach ($unionQueries as $unionQueryElement)
        {
            $unionQuery = $unionQuery ? $unionQuery->unionAll($unionQueryElement) : $unionQueryElement;
        }

        $unreconciledEntities = $unionQuery->get()
                                           ->toArray();

        return $unreconciledEntities;
    }

    public function fetchMultipleTransactionsFromIds(array $transactionIds)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ID, $transactionIds)
                    ->get();
    }

    protected function getSelectQueryForUnreconciledEntites(array $paymentParams = [], array $refundParams = [])
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $transactionsCreatedAtColumn = $this->dbColumn(Entity::CREATED_AT);

        $refundProcessedAtColumn = $this->repo->refund->dbColumn(Refund\Entity::PROCESSED_AT);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $terminalGatewayColumn = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY);

        $gatewayTerminalIdColumn = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY_TERMINAL_ID);

        $terminalGatewayAcquirerColumn = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY_ACQUIRER);

        if (empty($refundParams) === false)
        {
            $selectParams = $refundProcessedAtColumn . ',';
            $selectParams .= (implode(',', $refundParams)) . ',';
        }
        else
        {
            $selectParams = $transactionsCreatedAtColumn . ',';
        }

        $paymentParams = implode(',', $paymentParams);

        $gatewayCol = '('.
            'CASE '.
            'WHEN '. $paymentMethodColumn .' in ( "'. Payment\Method::CARD . '","'. Payment\Method::EMI .'")' .
                ' THEN '. $terminalGatewayAcquirerColumn .
                ' ELSE '. $terminalGatewayColumn .
            ' END' .
        ') gateway';

        $selectParams .= implode(',', [$paymentParams, $gatewayCol, $gatewayTerminalIdColumn]);

        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
                      ->selectRaw($selectParams);

        return $query;
    }

    protected function getQueryClausesForUnreconciledEntities($query, int $from, int $to, string $gateway, int $limit, $entityName)
    {
        $transactionAmountColumn = $this->dbColumn(Entity::AMOUNT);

        $paymentGatewayColumn = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        //
        // This '$timestampColumn' holds transactions.createdAt column
        // for payments and refunds.processedAt column for refunds.
        //
        // Reason : For refunds, use 'processedAt' instead of txn createdAt
        // Bcoz some refunds got success recently which were created
        // 1-2 months ago and thus xwe do not get these in recon summary
        // report if we use txn createdAt.
        //
        if ($entityName === ConstantEntity::PAYMENT)
        {
            $timestampColumn = $this->dbColumn(Entity::CREATED_AT);
        }
        else
        {
            $timestampColumn = $this->repo->refund->dbColumn(Refund\Entity::PROCESSED_AT);
        }

        $query->where($paymentGatewayColumn, $gateway)

              // To exclude e-mandate transactions
              ->where($transactionAmountColumn, '>', 0)
              ->whereNull(Transaction\Entity::RECONCILED_AT)
              ->whereBetween($timestampColumn, [$from, $to])
              ->orderBy($timestampColumn)
              ->limit($limit);
    }

    protected function getQueryClausesForReconSummary($query, $from, $to, string $entityName)
    {
        $transactionAmountColumn = $this->dbColumn(Entity::AMOUNT);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        //
        // This '$timestampColumn' holds transactions.createdAt column
        // for payments and refunds.processedAt column for refunds.
        //
        // Reason : For refunds, use 'processedAt' instead of txn createdAt
        // Bcoz some refunds got success recently which were created
        // 1-2 months ago and thus xwe do not get these in recon summary
        // report if we use txn createdAt.
        //
        if ($entityName === ConstantEntity::PAYMENT)
        {
            $timestampColumn = $this->dbColumn(Entity::CREATED_AT);

            $settledByColumn = $this->repo->payment->dbColumn(Payment\Entity::SETTLED_BY);
        }
        else
        {
            $timestampColumn = $this->repo->refund->dbColumn(Refund\Entity::PROCESSED_AT);

            $settledByColumn = $this->repo->refund->dbColumn(Payment\Entity::SETTLED_BY);
        }

        // To exclude e-mandate transactions and non-active gateways, we put 'where' clause here
        $query->where($transactionAmountColumn, '>', 0)
              ->where($settledByColumn, '=', 'Razorpay')
              ->whereBetween($timestampColumn, [$from, $to])
              ->groupBy('date', 'gateway', $paymentMethodColumn)
              ->orderBy('date', 'desc');
    }

    protected function addRefundJoinForReconSummary($query)
    {
        $refundId = $this->repo->refund->dbColumn(Refund\Entity::ID);

        $refundStatus = $this->repo->refund->dbColumn(Refund\Entity::STATUS);

        $query->join(Table::REFUND, Entity::ENTITY_ID, '=', $refundId)
              ->where($refundStatus, '=', Refund\Status::PROCESSED);
    }

    /**
     * gives the amount which has to be settled to the merchant on given time
     *
     * @param string $merchantId
     * @param        $balanceId
     * @param        $timestamp
     * @return mixed
     */
    public function getMerchantSettlementAmount(
        string $merchantId,
        Balance\Entity $balance,
        int $timestamp)
    {
        $startTime = microtime(true);

        $cacheTag = Entity::getCacheTag($this->entity, $merchantId, $balance->getType(), $timestamp);

        $result = $this->app['cache']->get($cacheTag);

        //
        // return the data if data available in cache
        //
        if ($result !== null)
        {
            return $result;
        }

        $transactionType        = $this->dbColumn(Entity::TYPE);
        $transactionMerchantId  = $this->dbColumn(Entity::MERCHANT_ID);
        $transactionCredit      = $this->dbColumn(Entity::CREDIT);
        $transactionDebit       = $this->dbColumn(Entity::DEBIT);
        $transactionOnHold      = $this->dbColumn(Entity::ON_HOLD);
        $transactionSettled     = $this->dbColumn(Entity::SETTLED);
        $transactionBalanceId   = $this->dbColumn(Entity::BALANCE_ID);
        $transactionSettledAt   = $this->dbColumn(Entity::SETTLED_AT);

        $query = $this->newQuery()
                      ->select(DB::raw("(SUM($transactionCredit)-SUM($transactionDebit)) as settlement_amount"))
                      ->where($transactionMerchantId, $merchantId)
                      ->where($transactionOnHold, 0)
                      ->where($transactionSettled, 0)
                      ->where($transactionType, '!=', Type::SETTLEMENT)
                      ->whereNotNull($transactionSettledAt)
                      ->where($transactionSettledAt, '<=', $timestamp);

        if ($balance->isTypePrimary() === true)
        {
            $query->where(function ($query) use ($transactionBalanceId, $balance) {
                $query->where($transactionBalanceId, $balance->getId())
                      ->orWhereNull($transactionBalanceId);
            });
        }
        else
        {
            $query->where($transactionBalanceId, $balance->getId());
        }

        $results = $query->first();

        //
        // caching the data for a minute
        //
        $this->app['cache']->put($cacheTag, $results, 1 * 60); // multiplying by 60, since put() expects in seconds

        $this->trace->info(
            TraceCode::SETTLEMENT_AMOUNT_FETCH_TIME_TAKEN,
            [
                'time_taken'   => get_diff_in_millisecond($startTime),
                'merchant_id'  => $merchantId,
                'settled_at'   => $timestamp,
                'balance_id'   => $balance->getId(),
            ]);

        return $results;
    }

    /**
     * @param string $mid
     * @param string $channel
     * @param Balance\Entity $balance
     * @param array $params
     *
     * @return Base\PublicCollection
     */
    public function fetchUnsettledTransactionsForProcessing(
        string $mid, Balance\Entity $balance, array $params = []): Base\PublicCollection
    {
        $txnFetchStartTime = microtime(true);

        $selectedColumns = $this->fetchRequiredColumnsForSettlement(true);

        $transactionMerchantId  = $this->dbColumn(Entity::MERCHANT_ID);
        $transactionType        = $this->dbColumn(Entity::TYPE);
        $transactionOnHold      = $this->dbColumn(Entity::ON_HOLD);
        $transactionSettled     = $this->dbColumn(Entity::SETTLED);
        $transactionBalanceId   = $this->dbColumn(Entity::BALANCE_ID);
        $transactionSettledAt   = $this->dbColumn(Entity::SETTLED_AT);

        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

	    // The filter on channel is dropped since we have a new index which works without it. WEF Feb 2020.
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->select($selectedColumns)
                      ->where($transactionMerchantId, $mid)
                      ->where($transactionOnHold, 0)
                      ->where($transactionSettled, 0)
                      ->where($transactionType, '!=', Type::SETTLEMENT)
                      ->whereNotNull($transactionSettledAt);

        if ($balance->isTypePrimary() === true)
        {
            $query->where(function ($query) use ($transactionBalanceId, $balance) {
                $query->where($transactionBalanceId, $balance->getId())
                      ->orWhereNull($transactionBalanceId);
            });
        }
        else
        {
            $query->where($transactionBalanceId, $balance->getId());
        }

        $query = $this->addSettlementFilters($query, $timestamp, $params);

        $results = $query->get();

        $this->trace->info(
            TraceCode::SETTLEMENT_TXN_FETCH_TIME_TAKEN,
            [
                'time_taken'   => get_diff_in_millisecond($txnFetchStartTime),
                'txn_count'    => $results->count(),
                'merchant_id'  => $mid,
                'settled_at'   => $timestamp,
                'params'       => $params,
                'balance_type' => $balance->getType(),
                'balance_id'   => $balance->getId(),
                'connection'   => $query->getConnection()->getName(),
            ]);

        return $results;
    }

    public function fetchUnsettledCommissionTransactions(
        Merchant\Entity $partner,
        $fromTimestamp,
        int $toTimestamp,
        $limit = null,
        $afterId = null): Base\PublicCollection
    {
        $commissionBalance = $partner->commissionBalance;

        if ($commissionBalance === null)
        {
            return new Base\PublicCollection;
        }

        $txnFetchStartTime      = microtime(true);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->where(Entity::MERCHANT_ID, $partner->getId())
                      ->where(Entity::BALANCE_ID, $commissionBalance->getId())
                      ->where(Entity::SETTLED_AT, '<=', $toTimestamp)
                      ->where(Entity::TYPE, E::COMMISSION)
                      ->where(Entity::SETTLED, 0)
                      ->where(Entity::ON_HOLD, 1)
                      ->with('source')
                      ->orderBy(Entity::ID);

        if (empty($fromTimestamp) === false)
        {
            $query->where(Entity::SETTLED_AT, '>=', $fromTimestamp);
        }

        if ($afterId !== null)
        {
            $query->where(Entity::ID, '>', $afterId);
        }

        if (empty($limit) === false)
        {
            $query->take($limit);
        }

        $results = $query->get();

        $txnFetchTimeTaken = microtime(true) - $txnFetchStartTime;

        $this->trace->info(
            TraceCode::COMMISSION_TRANSACTION_FETCH_TIME_TAKEN,
            [
                'time_taken' => $txnFetchTimeTaken,
                'txn_count'  => $results->count(),
                'after_id'   => $afterId,
                'limit'      => $limit,
            ]);


        return $results;
    }

    public function fetchRequiredColumnsForSettlement(bool $fetchAll = true): array
    {
        $selectedColumns = [];

        $columns = [ Transaction\Entity::MERCHANT_ID ];

        if ($fetchAll === true)
        {
            $columns = array_merge([
                Transaction\Entity::ID,
                Transaction\Entity::TAX,
                Transaction\Entity::FEE,
                Transaction\Entity::TYPE,
                Transaction\Entity::DEBIT,
                Transaction\Entity::CREDIT,
                Transaction\Entity::AMOUNT,
                Transaction\Entity::SETTLED,
                Transaction\Entity::CHANNEL,
                Transaction\Entity::BALANCE,
                Transaction\Entity::ENTITY_ID,
                Transaction\Entity::CREATED_AT,
                Transaction\Entity::SETTLED_AT,
                Transaction\Entity::CREDITS,
                Transaction\Entity::CREDIT_TYPE
            ], $columns);

        }

        foreach ($columns as $col)
        {
            $selectedColumns[] = $this->dbColumn($col);
        }

        return $selectedColumns;
    }

    public function fetchTransactionCountForSettlementId(string $setlId): int
    {
        return $this->newQuery()
                    ->select(Entity::ID)
                    ->where(Transaction\Entity::SETTLEMENT_ID, $setlId)
                    ->count();
    }

    private function fetchTransactionsForSettlementIdQuery(string $setlId)
    {
        $paymentIdColumn      = $this->repo->payment->dbColumn(Entity::ID);
        $transferIdColumn     = $this->repo->payment->dbColumn(Payment\Entity::TRANSFER_ID);
        $settlementIdColumn   = $this->dbColumn(Entity::SETTLEMENT_ID);
        $type                 = $this->dbColumn(Entity::TYPE);
        $entityIdColumn       = $this->dbColumn(Entity::ENTITY_ID);

        return $this->newQuery()
                    ->join(Table::PAYMENT, $entityIdColumn, '=', $paymentIdColumn)
                    ->where($settlementIdColumn, $setlId)
                    ->where($type, 'payment')
                    ->whereNotNull($transferIdColumn);
    }

    public function fetchTransactionsForSettlementIdCount(string $setlId)
    {
        return $this->fetchTransactionsForSettlementIdQuery($setlId)
                    ->count();
    }

    public function fetchTransactionsForSettlementId(string $setlId, $skip = 0)
    {
          return $this->fetchTransactionsForSettlementIdQuery($setlId)
                      ->skip($skip * Constant::CHUNK)
                      ->take(Constant::CHUNK)
                      ->get();
    }

    public function getTransactionBalanceType(string $transactionId)
    {
        $id                     = $this->dbColumn(Entity::ID);
        $transactionBalanceId   = $this->dbColumn(Entity::BALANCE_ID);
        $balanceTypeColumn      = $this->repo->balance->dbColumn(Entity::TYPE);
        $balanceId              = $this->repo->balance->dbColumn(Entity::ID);

        return $this->newQuery()
                    ->select($balanceTypeColumn)
                    ->leftJoin(Table::BALANCE, $balanceId, '=', $transactionBalanceId)
                    ->where($id , $transactionId)
                    ->value(Entity::TYPE);
    }

    public function getSettlableTransactions(
        string $merchantId,
        array $opt,
        Balance\Entity $balance,
        $fetchAllIds = false,
        array $limits = []): Base\PublicCollection {

        $txnId              = $this->dbColumn(Entity::ID);
        $txnBalanceId       = $this->dbColumn(Entity::BALANCE_ID);
        $txnMerchantId      = $this->dbColumn(Entity::MERCHANT_ID);
        $txnEntityId        = $this->dbColumn(Entity::ENTITY_ID);
        $txnType            = $this->dbColumn(Entity::TYPE);
        $txnCurrency        = $this->dbColumn(Entity::CURRENCY);
        $txnCredit          = $this->dbColumn(Entity::CREDIT);
        $txnDebit           = $this->dbColumn(Entity::DEBIT);
        $txnFee             = $this->dbColumn(Entity::FEE);
        $txnTax             = $this->dbColumn(Entity::TAX);
        $txnOnHold          = $this->dbColumn(Entity::ON_HOLD);
        $txnSettledAt       = $this->dbColumn(Entity::SETTLED_AT);
        $txnSettled         = $this->dbColumn(Entity::SETTLED);
        $txnCreatedAt       = $this->dbColumn(Entity::CREATED_AT);
        $txnType            = $this->dbColumn(Entity::TYPE);

        $startTime = microtime(true);

        $query = $this->newQueryWithConnection($this->getSlaveConnection());

        if($fetchAllIds === true)
        {
            $query->select($txnId);
        }
        else
        {
            $query->select($txnId, $txnBalanceId, $txnMerchantId, $txnEntityId, $txnType, $txnCurrency, $txnCredit, $txnDebit, $txnFee, $txnTax, $txnOnHold, $txnCreatedAt);
        }

        if ($balance->isTypePrimary() === true)
        {
            $query->where(function ($query) use ($txnBalanceId, $balance) {
                $query->where($txnBalanceId, $balance->getId())
                    ->orWhereNull($txnBalanceId);
            });
        }
        else
        {
            $query->where($txnBalanceId, $balance->getId());
        }

        $query->whereNotNull($txnSettledAt)
              ->where($txnSettled, 0)
              ->where($txnMerchantId, $merchantId)
              ->where($txnType, '!=', Type::SETTLEMENT);

        if (empty($limits) == false)
        {
            $query->offset($limits['offset']);
            $query->limit($limits['limit']);
        }

        if (empty($opt['transaction_ids']) === false)
        {
            $query->whereIn($txnId, $opt['transaction_ids']);
        }

        if ($opt['from'] !== null)
        {
            if ($opt['to'] !== null)
            {
                $query->whereBetween($txnCreatedAt, [$opt['from'], $opt['to']]);
            } else {
                $query->where($txnCreatedAt, '>=', $opt['from']);
            }
        }

        if ($opt['source_type'] !== null) {
            $query->where($txnType, $opt['source_type']);
        }

        $result = $query->get();

        unset($opt['transaction_ids']);

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_FETCH_TIME_TAKEN,
            [
                'merchant_id'   => $merchantId,
                'options'       => $opt,
                'limits'        => $limits,
                'txn_count'     => $result->count(),
                'balance_id'    => $balance->getId(),
                'fetch_all_ids' => $fetchAllIds,
                'time_taken'    => microtime(true) - $startTime,
            ]);

        return $result;
    }

    public function getUnsettledTransactionSumAndCount(
        string $merchantId,
        array $balance): array {

        $startTime = microtime(true);

        $rawQuery = "select (SUM(t.credit)-SUM(t.debit)) as settlement_amount, COUNT(t.id) as count from hive.realtime_hudi_api.transactions t where t.merchant_id = '%s' and on_hold = 0 and t.settled = 0 and t.type != 'settlement' and t.settled_at is not null and t.created_at <= %s and (t.balance_id = '%s' or t.balance_id is null) limit 1";

        $dataLakeQuery = sprintf($rawQuery, $merchantId, $balance[E::UPDATED_AT], $balance[ENTITY::ID]);

        $resultArray = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery)[0];

        $this->trace->info(
            TraceCode::LEDGER_RECON_FOR_MERCHANT_QUERY_TIME_TAKEN,
            [
                'merchant_id'        => $merchantId,
                'txn_count'          => $resultArray['count'],
                'balance_id'         => $balance[ENTITY::ID],
                'balance'            => $balance[E::BALANCE],
                'unsettled_amount'   => $resultArray['settlement_amount'],
                'difference'         => $resultArray['settlement_amount'] - $balance[E::BALANCE],
                'balance_updated_at' => $balance[E::UPDATED_AT],
                'time_taken'         => microtime(true) - $startTime,
            ]);

        return $resultArray;
    }

    /**
     * {@inheritDoc}
     */
    protected function modifyQueryForIndexing(BuilderEx $query)
    {
        // Eager loading relation is optimal during bulk indexing, also filters for specific type.
        $query->where(Entity::TYPE, ConstantEntity::PAYOUT)
              ->with('source');
    }

    /**
     * @param Base\PublicEntity $entity
     *
     * @return array
     */
    protected function serializeForIndexing(Base\PublicEntity $entity): array
    {
        if ($entity->isBalanceTypeBanking() === false)
        {
            return [];
        }

        $serialized = parent::serializeForIndexing($entity);

        $enitityType = $entity->getType();

        if ($enitityType === ConstantEntity::PAYOUT)
        {
            $serialized[Statement\Entity::UTR] = $entity->source->getUtr();

            $fa = $entity->source->fundAccount;

            if ($fa->getSourceType() === ConstantEntity::CONTACT)
            {
                $contact = $fa->source;
                $serialized[Statement\Entity::CONTACT_NAME] = $contact->getName();
                $serialized[Statement\Entity::CONTACT_EMAIL] = $contact->getEmail();
                $serialized[Statement\Entity::CONTACT_PHONE] = $contact->getContact();
                $serialized[Statement\Entity::FUND_ACCOUNT_NUMBER] = $fa->getAccountDestinationAsText(false);
                $notes = $entity->source->getNotes();

                // TODO:: Add test cases
                try
                {
                    if($notes !== null)
                    {
                        $serialized[Common::NOTES] = $notes->toArray();

                        $serialized[Common::NOTES] = array_map(
                            function ($key, $value)
                            {
                                return compact('key', 'value');
                            },
                            array_keys($serialized[Common::NOTES]),
                            $serialized[Common::NOTES]
                        );
                    }
                }
                catch (\Throwable $e)
                {
                    $this->trace->info(
                        TraceCode::ES_TRANSACTION_NOTES_SYNC,
                        [
                            'notes' => $notes
                        ]);
                }
            }
        }

        return $serialized;
    }

    /**
     * {@inheritDoc}
     */
    public function isEsSyncNeeded(string $action, array $dirty = null, Base\PublicEntity $entity = null): bool
    {
        // Additionally, checks if transaction is on banking balance. Others are not required as of now.
        return ((($entity === null) or ($entity->isBalanceTypeBanking() === true)) and
                (parent::isEsSyncNeeded($action, $dirty, $entity) === true));
    }

    public function addSettlementFilters($query, $timestamp, $params)
    {
        $transactionSettledAt   = $this->dbColumn(Entity::SETTLED_AT);
        $transactionCreatedAt   = $this->dbColumn(Entity::CREATED_AT);

        if((isset($params[Entity::CREATED_AT]) === false) and (isset($params[Entity::SETTLED_AT]) === false))
        {
            $query->where($transactionSettledAt, '<=', $timestamp);
        }
        else
        {
            if(isset($params[Entity::SETTLED_AT]) === true)
            {
                $query->where($transactionSettledAt, '<=', $params[Entity::SETTLED_AT]);
            }

            if(isset($params[Entity::CREATED_AT]) === true)
            {
                $query->where($transactionCreatedAt, '<=', $params[Entity::CREATED_AT]);
            }
        }

        return $query;
    }

    public function getMerchantSettledAtTime(array $mids, string $start, $end)
    {
        $transactionType        = $this->dbColumn(Entity::TYPE);
        $transactionOnHold      = $this->dbColumn(Entity::ON_HOLD);
        $transactionChannel     = $this->dbColumn(Entity::CHANNEL);
        $transactionSettled     = $this->dbColumn(Entity::SETTLED);
        $transactionSettledAt   = $this->dbColumn(Entity::SETTLED_AT);
        $transactionBalanceId   = $this->dbColumn(Entity::BALANCE_ID);
        $settlementCredit       = $this->dbColumn(Entity::CREDIT);
        $settlementDebit        = $this->dbColumn(Entity::DEBIT);
        $transactionMerchantId  = $this->dbColumn(Entity::MERCHANT_ID);

        $balanceId              = $this->repo->balance->dbColumn(Entity::ID);
        $balanceTypeColumn      = $this->repo->balance->dbColumn(Entity::TYPE);

        $query = $this->newQuery()
            ->select($transactionMerchantId, $transactionSettledAt)
            ->leftJoin(Table::BALANCE, $balanceId, '=', $transactionBalanceId)
            ->where(function ($query) use ($transactionBalanceId, $balanceTypeColumn)
            {
                $query->whereNull($transactionBalanceId)
                      ->orWhere($balanceTypeColumn, Balance\Type::PRIMARY);
            })
            ->whereNotNull($transactionSettledAt)
            ->where($transactionSettled, 0)
            ->where($transactionSettledAt, '>=', $start)
            ->whereNotIn($transactionMerchantId, $mids)
            ->where($transactionType, '!=', Type::SETTLEMENT)
            ->groupBy($transactionMerchantId, $transactionSettledAt);

        if (empty($end) === false)
        {
            $query->where($transactionSettledAt, '<=', $end);
        }
        return $query->get();
    }

    public function verifySettlementTransactions(array $txnIds)
    {
        $selectedColumns = [];

        $columns = [
            Transaction\Entity::ID,
            Transaction\Entity::TAX,
            Transaction\Entity::FEE,
            Transaction\Entity::DEBIT,
            Transaction\Entity::CREDIT,
            Transaction\Entity::CURRENCY,
            Transaction\Entity::MERCHANT_ID,
        ];

        $transactionId          = $this->dbColumn(Entity::ID);
        $transactionSettled     = $this->dbColumn(Entity::SETTLED);
        $transactionSourceId    = $this->dbColumn(Entity::ENTITY_ID);
        $transactionBalanceId   = $this->dbColumn(Entity::BALANCE_ID);
        $transactionSourceType  = $this->dbColumn(Entity::TYPE);

        $balanceId              = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn      = $this->repo->balance->dbColumn(Balance\Entity::TYPE);

        foreach ($columns as $col)
        {
            $selectedColumns[] = $this->dbColumn($col);
        }

        $selectedColumns[] = $transactionSourceId.' as source_id';
        $selectedColumns[] = $balanceTypeColumn.' as balance_type';
        $selectedColumns[] = $transactionSourceType.' as source_type';

        return $this->newQuery()
            ->select($selectedColumns)
            ->whereIn($transactionId, $txnIds)
            ->join(Table::BALANCE, $balanceId, '=', $transactionBalanceId)
            ->where(function ($query) use ($transactionBalanceId, $balanceTypeColumn)
            {
                $query->whereNull($transactionBalanceId)
                    ->orWhereIn($balanceTypeColumn, [Balance\Type::PRIMARY, Balance\Type::COMMISSION]);
            })
            ->where($transactionSettled, 0)
            ->where($transactionSourceType, '!=', Type::SETTLEMENT)
            ->get()
            ->keyBy(Transaction\Entity::ID);
    }

    /**
     * It fetches the transactions for ledger service to compare shadow data
     *
     * @param array $merchantIds
     * @param $from
     * @param $to
     * @param int $count
     * @param int $skip
     * @param string|null $lastProcessedTxnId
     * @param string $balanceType
     * @param string $balanceAccountType
     * @return mixed
     */
    public function fetchBankingTransactionsForLedgerRecon(array $merchantIds, $from, $to, int $count = 1000, int $skip = 0, string $lastProcessedTxnId = null,
                                                           $balanceType = Balance\Type::BANKING, $balanceAccountType = Merchant\Balance\AccountType::SHARED)
    {
        // select column
        $transactionIdColumn = $this->dbColumn(Entity::ID);
        $transactionAmountColumn = $this->dbColumn(Entity::AMOUNT);
        $transactionTaxColumn = $this->dbColumn(Entity::TAX);
        $transactionFeeColumn = $this->dbColumn(Entity::FEE);
        $transactionEntityIdColumn = $this->dbColumn(Entity::ENTITY_ID);
        $transactionTypeColumn = $this->dbColumn(Entity::TYPE);
        $transactionBalanceColumn = $this->dbColumn(Entity::BALANCE);
        $transactionCreditTypeColumn = $this->dbColumn(Entity::CREDIT_TYPE);

        $merchantIdColumn = $this->repo->merchant->dbColumn(Entity::ID);
        $merchantFeeModelColumn = $this->repo->merchant->dbColumn(Entity::FEE_MODEL);

        $transactionBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $transactionMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $transactionCreatedAtColumn = $this->dbColumn(Entity::CREATED_AT);

        $balanceIdColumn   = $this->repo->balance->dbColumn(Entity::ID);
        $balanceTypeColumn = $this->repo->balance->dbColumn(Entity::TYPE);
        $balanceAccountTypeColumn   = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ACCOUNT_TYPE);

        $selectColumn = [
            $transactionIdColumn,
            $transactionAmountColumn,
            $transactionTaxColumn,
            $transactionFeeColumn,
            $transactionEntityIdColumn,
            $transactionTypeColumn,
            $transactionMerchantIdColumn,
            $transactionBalanceColumn,
            $merchantFeeModelColumn,
            $transactionCreditTypeColumn
        ];

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->select($selectColumn)
                      ->leftjoin(Table::BALANCE, $balanceIdColumn, '=', $transactionBalanceIdColumn)
                      ->leftjoin(Table::MERCHANT, $merchantIdColumn, '=', $transactionMerchantIdColumn)
                      // To fetch only banking transaction until pg use cases are onboarded
                      ->where(function ($query) use ($balanceType, $transactionBalanceIdColumn, $balanceTypeColumn)
                      {
                          $query->WhereIn($balanceTypeColumn, [$balanceType]);
                      })
                      ->where($balanceAccountTypeColumn, '=', $balanceAccountType)
                      ->whereIn($transactionMerchantIdColumn, $merchantIds);

        if (empty($lastProcessedTxnId) === false)
        {
            // lastProcessedTxnId is stored in ledger redis which is the last transaction
            // processed by data comparator in its one cycle. When the data comparator starts again,
            // it fetched transactions which are not processed in its initial cycle, i.e.,
            // transactions which are greater than the last processed transaction id and created before the given time
            $query->where($transactionIdColumn, '>', $lastProcessedTxnId)
                  ->where($transactionCreatedAtColumn, '<', $to);
        }
        else
        {
            $query->betweenTime($from, $to);
        }

        return $query->take($count)
                     ->skip($skip)
                     ->oldest($transactionCreatedAtColumn)
                     ->get();
    }

    public function findById($id)
    {
        $idColumn = $this->repo->transaction->dbColumn(Entity::ID);

        return $this->newQuery()
                    ->where($idColumn, '=', $id)
                    ->get();
    }

    public function checkIfIdExists(string $id)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $id)
                    ->exists();
    }

    public function fetchFirstTransactionDetails(
        string $merchantId): array
    {
        $transactionIdColumn       = $this->dbColumn(Entity::ID);
        $merchantIdColumn          = $this->dbColumn(Entity::MERCHANT_ID);
        $createdAtColumn           = $this->dbColumn(Entity::CREATED_AT);
        $transactionAmountColumn   = $this->dbColumn(Entity::AMOUNT);
        $transactionCurrencyColumn = $this->dbColumn(Entity::CURRENCY);
        $selectColumn              = [
            $transactionIdColumn,
            $transactionAmountColumn,
            $transactionCurrencyColumn,
            $merchantIdColumn,
            $createdAtColumn
        ];

        return $this->newQueryWithConnection($this->getReportingReplicaConnection())
                    ->select($selectColumn)
                    ->where($this->dbColumn(Entity::TYPE), '=', 'payment')
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->first()
                    ->toArray();
    }

    public function emptyTransactionsForMerchantId(string $merchantId): bool
    {
        return $this->newQueryWithConnection($this->getReportingReplicaConnection())
            ->where($this->dbColumn(Entity::TYPE), '=', 'payment')
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->count() === 0;
    }

    public function isMerchantPaymentCountAboveThreshold($merchantId,$paymentCountThreshold)
    {
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $type = $this->dbColumn(Entity::TYPE);

        return $this->newQueryWithConnection($this->getReportingReplicaConnection())
                    ->select($merchantIdColumn)
                    ->where($type, '=', 'payment')
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->take($paymentCountThreshold)
                    ->get();
    }

    public function fetchCommissionPayoutsCount(array $commissionIds) : int
    {
        return $this->newQuery()
                    ->where($this->dbColumn(Entity::TYPE), Type::COMMISSION)
                    ->where($this->dbColumn(Entity::ON_HOLD), false)
                    ->whereIn($this->dbColumn(Entity::ENTITY_ID), $commissionIds)
                    ->count();
    }

    public function getDebitAndCreditValues($merchantId, $startTimestamp)
    {
        $query = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
                      ->select(Entity::ID, Entity::DEBIT, Entity::CREDIT, Entity::BALANCE)
                      ->where(Entity::MERCHANT_ID, $merchantId)
                      ->where(Entity::CREATED_AT, '>=', $startTimestamp)
                      ->orderBy(Entity::CREATED_AT, 'asc');

        return $query->get()->toArray();
    }

    public function fetchTransactionsWithSkip($skip, $count, $merchantIds=null, $startTime=null, $endTime=null)
    {
        $transactionIdColumn          = $this->dbColumn(Entity::ID);
        $transactionBalanceIdColumn   = $this->dbColumn(Entity::BALANCE_ID);
        $transactionMerchantIdColumn  = $this->dbColumn(Entity::MERCHANT_ID);
        $transactionCreatedAtColumn   = $this->dbColumn(Entity::CREATED_AT);

        $balanceIdColumn     = $this->repo->balance->dbColumn(Entity::ID);
        $balanceTypeColumn   = $this->repo->balance->dbColumn(Entity::TYPE);
        $merchantIdColumn    = $this->repo->merchant->dbColumn(Entity::ID);

        $selectColumn  = [
            $transactionIdColumn,
        ];

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select($selectColumn)
            ->leftjoin(Table::BALANCE, $balanceIdColumn, '=', $transactionBalanceIdColumn)
            ->leftjoin(Table::MERCHANT, $merchantIdColumn, '=', $transactionMerchantIdColumn)
            // To fetch only banking transactions
            ->where(function ($query) use ($transactionBalanceIdColumn, $balanceTypeColumn)
            {
                $query->WhereIn($balanceTypeColumn, [Balance\Type::BANKING]);
            });

        if($merchantIds != null)
        {
            $query->whereIn($transactionMerchantIdColumn, $merchantIds);
        }

        if($startTime != null)
        {
            $query->where($transactionCreatedAtColumn, '>=', $startTime);
        }

        if($endTime != null)
        {
            $query->where($transactionCreatedAtColumn, '<=', $endTime);
        }

        return $query->take($count)
                    ->skip($skip)
                    ->oldest($transactionCreatedAtColumn)
                    ->get()
                    ->pluck(Entity::ID)
                    ->toArray();
    }

    public function fetchTransactionsForMerchantIdWithSkip($merchantId, $skip, $count)
    {
        $transactionIdColumn          = $this->dbColumn(Entity::ID);
        $transactionBalanceIdColumn   = $this->dbColumn(Entity::BALANCE_ID);
        $transactionMerchantIdColumn  = $this->dbColumn(Entity::MERCHANT_ID);
        $transactionCreatedAtColumn   = $this->dbColumn(Entity::CREATED_AT);

        $balanceIdColumn     = $this->repo->balance->dbColumn(Entity::ID);
        $balanceTypeColumn   = $this->repo->balance->dbColumn(Entity::TYPE);
        $merchantIdColumn    = $this->repo->merchant->dbColumn(Entity::ID);

        $selectColumn  = [
            $transactionIdColumn,
        ];

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select($selectColumn)
            ->leftjoin(Table::BALANCE, $balanceIdColumn, '=', $transactionBalanceIdColumn)
            ->leftjoin(Table::MERCHANT, $merchantIdColumn, '=', $transactionMerchantIdColumn)
            // To fetch only banking transactions
            ->where(function ($query) use ($transactionBalanceIdColumn, $balanceTypeColumn)
            {
                $query->WhereIn($balanceTypeColumn, [Balance\Type::BANKING]);
            })
            ->where($transactionMerchantIdColumn, $merchantId);

        return $query->take($count)
            ->skip($skip)
            ->oldest($transactionCreatedAtColumn)
            ->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    public function getBySettlementIdAndTypes($settlementId, $types, $connection = null)
    {
        try
        {
            $query = $this->newQueryOnSlave();
            if(isset($connection))
            {
                $query = $this->newQueryWithConnection($connection);
            }
            return $query
                ->where(Entity::SETTLEMENT_ID, $settlementId)
                ->whereIn(Entity::TYPE, $types)
                ->get();
        }
        catch(\Exception $e)
        {
            $this->trace->error(Tracecode::ERROR_EXCEPTION, [
                "error" => $e
            ]);
        }
    }

    public function getBySettlementIdAndTypesWithOffset($settlementId, $types, $offset =0, $limit = 10000)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::SETTLEMENT_ID, $settlementId)
            ->whereIn(Entity::TYPE, $types)
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function getCountBySettlementIdAndTypes($settlementId, $types)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::SETTLEMENT_ID, $settlementId)
            ->whereIn(Entity::TYPE, $types)
            ->count();
    }

    public function getCountAndAmountByMerchantAndOnholdAndTypes($merchantId, $onhold, $types, $start, $end)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->selectRaw('COUNT(' . $this->dbColumn(Entity::ID) . ') AS count, SUM(' . $this->dbColumn(Entity::CREDIT) . ') AS total_credit')
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::ON_HOLD, $onhold)
            ->whereIn(Entity::TYPE, $types)
            ->whereBetween($this->dbColumn(Entity::CREATED_AT), [$start, $end])
            ->first()
            ->toArray();
    }

    public function getTransactionsByMerchantAndOnholdAndTypes(
        $merchantId, $onhold, $types, $start, $end, $limit = 1000)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::ON_HOLD, $onhold)
            ->whereIn(Entity::TYPE, $types)
            ->whereBetween($this->dbColumn(Entity::CREATED_AT), [$start, $end])
            ->limit($limit)
            ->get();
    }

    public function getDisputesBySettlementId($settlementId, $connection = null)
    {
        try {
            $query = $this->newQueryOnSlave();
            if (isset($connection)) {
                $query = $this->newQueryWithConnection($connection);
            }

            $transactionSettlementIdCol = $this->repo->transaction->dbColumn(Entity::SETTLEMENT_ID);
            $transactionEntityIdCol = $this->repo->transaction->dbColumn(Entity::ENTITY_ID);

            $adjustmentIdCol = $this->repo->adjustment->dbColumn(Adjustment\Entity::ID);
            $adjustmentTypeCol = $this->repo->adjustment->dbColumn(Adjustment\Entity::ENTITY_TYPE);

            return $query
                ->select(Table::TRANSACTION . '.*')
                ->join(Table::ADJUSTMENT, $transactionEntityIdCol, "=", $adjustmentIdCol)
                ->where($transactionSettlementIdCol, $settlementId)
                ->where($adjustmentTypeCol, Type::DISPUTE)
                ->get();
        } catch (\Exception $e) {
            $this->trace->error(Tracecode::ERROR_EXCEPTION, [
                "error" => $e
            ]);
        }
    }

    public function updateTransactionWithContextAsComment(string $id, int $balance, string $context, string $mode)
    {
        $query = "UPDATE /*" . $context . "*/ transactions SET balance = " . $balance . " ,updated_at = " . now()->getTimestamp() .
                 " where id = '" . $id . "';";

        DB::connection($mode)->statement($query);
    }
}
