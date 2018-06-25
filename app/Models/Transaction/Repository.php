<?php

namespace RZP\Models\Transaction;

use Carbon\Carbon;
use DB;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Gateway\Billdesk;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Models\Payment\Refund;
use RZP\Models\Pricing\FeeCalculator;
use RZP\Models\Merchant\Invoice\Type as InvoiceType;

class Repository extends Base\Repository
{
    protected $entity = 'transaction';

    protected $signedIds = [
        Entity::SETTLEMENT_ID,
    ];

    protected $appFetchParamRules = array(
        Entity::SETTLED         => 'sometimes|in:0,1',
        Entity::ON_HOLD         => 'sometimes|in:0,1',
        Entity::TYPE            => 'sometimes|in:payment,refund,settlement,adjustment,reversal,transfer',
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
     * @return mixed
     */
    public function fetchUnsettledTransactions(
        $timestamp, string $channel, array $inMerchantIds = [], array $notInMerchantIds = [])
    {
        $merchantId = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        $transactionMerchantId = $this->dbColumn(Entity::MERCHANT_ID);
        $transactionId = $this->dbColumn(Entity::ID);

        $transactionChannel = $this->dbColumn(Entity::CHANNEL);

        $transactionBalance     = $this->dbColumn(Entity::BALANCE);
        $transactionType        = $this->dbColumn(Entity::TYPE);
        $transactionSourceId    = $this->dbColumn(Entity::ENTITY_ID);
        $transactionSettledAt   = $this->dbColumn(Entity::SETTLED_AT);
        $transactionSettled     = $this->dbColumn(Entity::SETTLED);
        $transactionAmount      = $this->dbColumn(Entity::AMOUNT);
        $transactionCredit      = $this->dbColumn(Entity::CREDIT);
        $transactionDebit       = $this->dbColumn(Entity::DEBIT);
        $transactionTax         = $this->dbColumn(Entity::TAX);
        $transactionFee         = $this->dbColumn(Entity::FEE);
        $transactionFeeCredits  = $this->dbColumn(Entity::CREDITS);
        $transactionCreditsType = $this->dbColumn(Entity::CREDIT_TYPE);
        $transactionCreatedAt   = $this->dbColumn(Entity::CREATED_AT);

        $txnFetchStartTime = microtime(true);

        $query = $this->newQuery()
                      ->select(
                          $transactionId,
                          $transactionMerchantId,
                          $transactionBalance,
                          $transactionType,
                          $transactionSourceId,
                          $transactionSettledAt,
                          $transactionSettled,
                          $transactionAmount,
                          $transactionCredit,
                          $transactionDebit,
                          $transactionTax,
                          $transactionFee,
                          $transactionFeeCredits,
                          $transactionCreditsType,
                          $transactionCreatedAt
                      )
                      ->join(Table::MERCHANT, $merchantId, '=', $transactionMerchantId)
                      ->where(Entity::SETTLED_AT, '<', $timestamp)
                      ->where(Entity::ON_HOLD, 0)
                      ->where(Entity::SETTLED, 0)
                      ->where($transactionChannel, $channel)
                      ->where(Entity::TYPE, '!=', Type::SETTLEMENT)
                      ->where(Merchant\Entity::HOLD_FUNDS, 0);

        if (empty($inMerchantIds) === false)
        {
            $query = $query->whereIn($merchantId, $inMerchantIds);
        }

        if (empty($notInMerchantIds) === false)
        {
            $query = $query->whereNotIn($merchantId, $notInMerchantIds);
        }

        $results = $query->get();

        $txnFetchTimeTaken = microtime(true) - $txnFetchStartTime;

        $this->trace->info(TraceCode::SETTLEMENT_TXN_FETCH_TIME_TAKEN, ['time_taken' => $txnFetchTimeTaken]);

        return $results;
    }

    public function fetchUnsettledTransactionsForMerchantUpdate($merchantId)
    {
        $query = $this->newQuery()
                      ->select(['id'])
                      ->where(Transaction\Entity::SETTLED, '=', 0)
                      ->where(Transaction\Entity::TYPE, '!=', Type::SETTLEMENT)
                      ->merchantId($merchantId);

        return $query->get();
    }

    public function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $entityToRelationFetchMap = [])
    {
        $setls = (new Settlement\Repository)->fetchBetweenTimestamp($merchantId, $from, $to);

        $setlIds = $setls->modelKeys();

        $query = $this->newQuery();

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

        $query = $this->newQuery();

        $txns = $query->merchantId($merchantId)
                      ->whereIn(Entity::SETTLEMENT_ID, $setlIds)
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

    public function fetchEntitiesForBrokerReport($merchantId, $from, $to, $count, $skip, $entityToRelationFetchMap)
    {
        $txns = $this->newQuery()
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
            $typeEntities = $this->repo->$type->findManyWithRelations($ids, $eagerLoadRelations);

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
        $fee = $this->newQuery()
                    ->where('transactions.merchant_id', $merchantId)
                    ->where('type', 'payment')
                    ->join('payments', 'transactions.entity_id', '=', 'payments.id')
                    ->whereNotNull('payments.captured_at')
                    ->betweenTime($from, $to)
                    ->sum('transactions.fee');

        $tax = $this->newQuery()
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
    public function settled($txns, array $values)
    {
        $txnCount = $txns->count();

        if ($txnCount === 0)
        {
            return;
        }

        $ids = $txns->getIds();

        $batchedIds = array_chunk($ids, 1000);

        $startTime = microtime(true);

        foreach ($batchedIds as $batch)
        {
            $count = $this->newQuery()
                          ->whereIn(Transaction\Entity::ID, $batch)
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

        $timeTaken = microtime(true) - $startTime;

        $this->trace->info(TraceCode::SETTLEMENT_TXN_UPDATE_TIME_TAKEN, ['time_taken' => $timeTaken]);

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

    public function fetchBySettlement($setl)
    {
        return $this->newQuery()
                    ->where(Transaction\Entity::SETTLEMENT_ID, '=', $setl->getId())
                    ->get();
    }

    public function getCancelledBilldeskTransactions()
    {
        $billdeskPaymentId = Billdesk\Entity::dbColumn(Billdesk\Entity::PAYMENT_ID);
        $billdeskRefStatus = Billdesk\Entity::dbColumn('RefStatus');

        $paymentId = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentStatus = $this->repo->payment->dbColumn(Payment\Entity::STATUS);

        $transactionEntityId = $this->dbColumn(Entity::ENTITY_ID);
        $transactionReconciledAt = $this->dbColumn(Entity::RECONCILED_AT);

        $transactionData = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($transactionData)
                    ->join(Table::PAYMENT, $paymentId, '=', $transactionEntityId)
                    ->join(Table::BILLDESK, $billdeskPaymentId, '=', $paymentId)
                    ->where($billdeskRefStatus, '=', Billdesk\RefundStatus::CANCELLED)
                    ->where($paymentStatus, '=', Payment\Status::REFUNDED)
                    ->whereNull($transactionReconciledAt)
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
        $query = $this->newQuery()
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
        $txnIds = $this->newQuery()
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
        $transactions = $this->newQuery()
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
        return $this->newQuery()
                    ->selectRaw(
                        'SUM(' . Entity::TAX .') AS tax, SUM(' . Entity::FEE . ') AS fee')
                    ->whereBetween(Entity::CREATED_AT, [$start, $end])
                    ->merchantId($merchantId)
                    ->whereNotIn(Entity::TYPE, Type::IGNORE_ENTITIES_FROM_MERCHANT_INVOICE)
                    ->first();
    }

    /**
     * Raw sql query :
     *
     *  select STRAIGHT_JOIN FROM_UNIXTIME(transactions.created_at + 19800,'%D %M, %Y') AS date,
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
     *          ELSE payments.gateway
     *          END) gateway from `transactions`
     *  inner join `payments` on `entity_id` = `payments`.`id`
     *  inner join `terminals` on `terminal_id` = `terminals`.`id`
     *  where `payments`.`gateway` in (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) and
     *  `transactions`.`created_at` between ? and ? and `transactions`.`amount` > 0
     *  group by `date`, `gateway` order by `date` desc
    */
    public function fetchPaymentReconStatusSummary(int $from, int $to, array $gateways): array
    {
        $paymentId = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $terminalId = $this->repo->terminal->dbColumn(Terminal\Entity::ID);

        $query = $this->getSelectParamsQueryForReconSummary();

        //
        // Adding join with payment and terminal
        //
        $query->join(Table::PAYMENT, Entity::ENTITY_ID, '=', $paymentId)
              ->join(Table::TERMINAL, Payment\Entity::TERMINAL_ID, '=', $terminalId);

        $this->getQueryClausesForReconSummary($query, $from, $to, $gateways);

        $reconciledPaymentsSummary = $query->get()
                                           ->toArray();

        return $reconciledPaymentsSummary;
    }

    /**
     * Raw sql query :
     *
     *  select STRAIGHT_JOIN FROM_UNIXTIME(transactions.created_at + 19800,'%D %M, %Y') AS date,
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
     *      END) gateway
     *  from `transactions` inner join `refunds` on `entity_id` = `refunds`.`id`
     *  inner join `payments` on `payments`.`id` = `payment_id`
     *  inner join `terminals` on `terminal_id` = `terminals`.`id`
     *  where `payments`.`gateway` in (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     *  and `refunds`.`status` = ? and `transactions`.`created_at` between ? and ? and `transactions`.`amount` > 0
     *  group by `date`, `gateway` order by `date
     *
     *  Note :  Transaction amount should be greater than 0 to exclude e-mandate transactions of 0 amount.
     *          Such transactions are not considered for reconciliation.
     */

    public function fetchRefundReconStatusSummary(int $from, int $to, array $gateways): array
    {
        $paymentId = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $terminalId = $this->repo->terminal->dbColumn(Terminal\Entity::ID);

        $query = $this->getSelectParamsQueryForReconSummary();

        $this->addRefundJoinForReconSummary($query);

        //
        // Adding join with payment and terminal
        //
        $query->join(Table::PAYMENT, $paymentId, '=', Refund\Entity::PAYMENT_ID)
              ->join(Table::TERMINAL, Payment\Entity::TERMINAL_ID, '=', $terminalId);

        $this->getQueryClausesForReconSummary($query, $from, $to, $gateways);

        $reconciledRefundsSummary = $query->get()
                                          ->toArray();

        return $reconciledRefundsSummary;
    }

    protected function getSelectParamsQueryForReconSummary()
    {
        $transactionPaymentId = $this->dbColumn(Entity::ENTITY_ID);

        $transactionAmount = $this->dbColumn(Entity::AMOUNT);

        $transactionReconciledAt = $this->dbColumn(Entity::RECONCILED_AT);

        $transactionId = $this->dbColumn(Entity::ID);

        $paymentMethod = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $terminalGatewayAcquirer = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY_ACQUIRER);

        $terminalGateway = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY);

        $transactionsCreatedAt = $this->dbColumn(Entity::CREATED_AT);

        $params =  'COUNT('.$transactionPaymentId.') AS total_count'. ','.
            'SUM('.$transactionAmount.')/100 AS total_amount'.','.

            'COUNT(CASE
                       WHEN '.$transactionReconciledAt.' is not null
                            THEN '.$transactionId.'
                        END) recon_count,
                 COUNT(CASE
                       WHEN '.$transactionReconciledAt.' is null
                            THEN '.$transactionId.'
                        END) unrecon_count,

                 SUM(CASE
                     WHEN '.$transactionReconciledAt.' is not null
                        THEN '.$transactionAmount.'
                        ELSE 0
                     END)/100 recon_amount,
                 SUM(CASE
                     WHEN '.$transactionReconciledAt.' is null
                        THEN '.$transactionAmount.'
                        ELSE 0
                     END)/100 unrecon_amount'. ',' .
            '(Case WHEN '. $paymentMethod .' in ( "'. Payment\Method::CARD . '","'. Payment\Method::EMI .'")'.'
                    THEN '. $terminalGatewayAcquirer . '
                    ELSE '. $terminalGateway . '
                  END) gateway';

        $query = $this->newQuery()
                      ->selectRaw('STRAIGHT_JOIN FROM_UNIXTIME('. $transactionsCreatedAt .' + 19800,"%D %M, %Y") AS date' . ',' .
                                    $params);

        return $query;
    }

    /**
     * Raw Sql Query :
     * This query is used in union query of all gateways.
     *
     * Payments:
     * select STRAIGHT_JOIN transactions.created_at,payments.id as payment_id,
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
     * select STRAIGHT_JOIN transactions.created_at,refunds.id as refund_id,
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
        $paymentId = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $refundParams = $this->repo->refund->getAliasesForRefundsDbColumns($refundParams);

        $paymentParams = $this->repo->payment->getAliasesForPaymentsDbColumns($paymentParams);

        $terminalId = $this->repo->terminal->dbColumn(Terminal\Entity::ID);

        $unionQueries = [];

        foreach ($gateways as $gateway)
        {
            $query = $this->getSelectQueryForUnreconciledEntites($paymentParams, $refundParams);

            if (empty($refundParams) === false)
            {
                $this->addRefundJoinForReconSummary($query);

                $query->join(Table::PAYMENT, $paymentId, '=', Refund\Entity::PAYMENT_ID);
            }
            else
            {
                $query->join(Table::PAYMENT, Entity::ENTITY_ID, '=', $paymentId);
            }

            $query->join(Table::TERMINAL, Payment\Entity::TERMINAL_ID, '=', $terminalId);

            $this->getQueryClausesForUnreconciledEntites($query, $from, $to, $gateway, $limit);

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

    protected function getSelectQueryForUnreconciledEntites(array $paymentParams = [], array $refundParams = [])
    {
        $transactionsCreatedAt = $this->dbColumn(Entity::CREATED_AT);

        $paymentMethod = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $terminalGateway = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY);

        $gatewayTerminalId = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY_TERMINAL_ID);

        $terminalGatewayAcquirer = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY_ACQUIRER);

        $selectParams = 'STRAIGHT_JOIN ' . $transactionsCreatedAt . ',';

        if (empty($refundParams) === false)
        {
            $selectParams .= (implode(',', $refundParams)) . ',';
        }

        $selectParams .= (implode(',', $paymentParams)) . ','
                         . '(Case WHEN '. $paymentMethod .' in ( "'. Payment\Method::CARD . '","'. Payment\Method::EMI .'")'.'
                                THEN '. $terminalGatewayAcquirer . '
                                ELSE '. $terminalGateway . '
                            END) gateway ,'. $gatewayTerminalId;

        $query = $this->newQuery()
                      ->selectRaw($selectParams);

        return $query;
    }

    protected function getQueryClausesForUnreconciledEntites($query, int $from, int $to, string $gateway, int $limit)
    {
        $transactionAmount = $this->dbColumn(Entity::AMOUNT);

        $transactionsCreatedAt = $this->dbColumn(Entity::CREATED_AT);

        $paymentGateway = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $query->where($paymentGateway, $gateway)

              // To exclude e-mandate transactions
              ->where($transactionAmount, '>', 0)

              ->whereNull(Transaction\Entity::RECONCILED_AT)
              ->betweenTime($from, $to)
              ->orderBy($transactionsCreatedAt)
              ->limit($limit);
    }

    protected function getQueryClausesForReconSummary($query, $from, $to, $gateways)
    {
        $transactionAmount = $this->dbColumn(Entity::AMOUNT);

        $gateway = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $query->whereIn($gateway, $gateways)

              // To exclude e-mandate transactions
              ->where($transactionAmount, '>', 0)

              ->betweenTime($from, $to)
              ->groupBy('date', 'gateway')
              ->orderBy('date', 'desc');
    }

    protected function addRefundJoinForReconSummary($query)
    {
        $refundId = $this->repo->refund->dbColumn(Refund\Entity::ID);

        $refundStatus = $this->repo->refund->dbColumn(Refund\Entity::STATUS);

        $query->join(Table::REFUND, Entity::ENTITY_ID, '=', $refundId)
              ->where($refundStatus, '=', Refund\Status::PROCESSED);
    }
}
