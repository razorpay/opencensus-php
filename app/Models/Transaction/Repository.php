<?php

namespace RZP\Models\Transaction;

use DB;

use RZP\Constants\Table;
use RZP\Constants\Entity as E;
use RZP\Exception;
use RZP\Gateway\Billdesk;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant\Invoice\Type as InvoiceType;
use RZP\Models\Pricing\FeeCalculator;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Schedule;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    protected $entity = 'transaction';

    protected $signedIds = [
        Entity::SETTLEMENT_ID,
    ];

    protected $appFetchParamRules = array(
        Entity::SETTLED         => 'sometimes|in:0,1',
        Entity::ON_HOLD         => 'sometimes|in:0,1',
        Entity::TYPE            => 'sometimes|in:payment,refund,settlement,adjustment',
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

    public function fetchUnsettledTransactions($timestamp, $channel)
    {
        $merchantId = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        $transactionMerchantId = $this->dbColumn(Entity::MERCHANT_ID);
        $transactionId = $this->dbColumn(Entity::ID);
        $transactionData = $this->dbColumn('*');
        $transactionChannel = $this->dbColumn(Entity::CHANNEL);

        $txns = $this->newQuery()
                    ->select($transactionData)
                    ->join(Table::MERCHANT, $merchantId, '=', $transactionMerchantId)
                    ->where(Entity::SETTLED_AT, '<', $timestamp)
                    ->where(Entity::ON_HOLD, 0)
                    ->where(Entity::SETTLED, '=', 0)
                    ->where($transactionChannel, '=', $channel)
                    ->where(Entity::TYPE, '!=', Type::SETTLEMENT)
                    ->where(Merchant\Entity::HOLD_FUNDS, '=', 0)
                    ->with('merchant', 'merchant.bankAccount', 'merchant.balance')
                    ->orderBy($transactionMerchantId)
                    ->orderBy($transactionId)
                    ->get();

        return $txns;
    }

    public function fetchUnsettledTransactionsForMerchant($timestamp, $merchant)
    {
        return $this->newQuery()
                    ->where(Transaction\Entity::ON_HOLD, 0)
                    ->where(Transaction\Entity::SETTLED_AT, '<', $timestamp)
                    ->where(Transaction\Entity::SETTLED, '=', 0)
                    ->where(Transaction\Entity::TYPE, '!=', Type::SETTLEMENT)
                    ->merchantId($merchant->getId())
                    ->orderBy(Transaction\Entity::ID)
                    ->get();
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

        $batchedIds = array_chunk($ids, 20000);

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

    public function fetchFeesAndTaxForTransactionsByType(
        string $merchantId, int $start, int $end, string $filterType)
    {
        $createdAtCol = $this->dbColumn(Entity::CREATED_AT);

        $merchantIdCol = $this->dbColumn(Entity::MERCHANT_ID);

        $amountCol = $this->dbColumn(Entity::AMOUNT);

        $feeCol = $this->dbColumn(Entity::FEE);

        $taxCol = $this->dbColumn(Entity::TAX);

        $paymentIdCol = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $paymentCardIdCol = $this->repo->payment->dbColumn(Payment\Entity::CARD_ID);

        $transactionData = $this->dbColumn('*');

        $query = $this->newQuery()
                      ->selectRaw(
                            'SUM(' . $taxCol .') AS tax, SUM(' . $feeCol . ') AS fee')
                      ->join(Table::PAYMENT, Entity::ENTITY_ID, '=', $paymentIdCol)
                      ->whereBetween($createdAtCol, [$start, $end])
                      ->merchantId($merchantId)
                      ->whereNotNull(Payment\Entity::CAPTURED_AT)
                      ->where(Entity::TYPE, Type::PAYMENT)
                      ->groupBy($merchantIdCol);

        switch ($filterType)
        {
            case InvoiceType::NON_CARD:
                $query = $query->whereNull($paymentCardIdCol);
                break;

            case InvoiceType::CARD_LTE_2K:
                $query = $query->whereNotNull($paymentCardIdCol)
                               ->where($amountCol, '<=', FeeCalculator::CARD_TAX_CUT_OFF);
                break;

            case InvoiceType::CARD_GT_2K:
                $query = $query->whereNotNull($paymentCardIdCol)
                               ->where($amountCol, '>', FeeCalculator::CARD_TAX_CUT_OFF);
                break;

            default:
                throw new Exception\LogicException('Invalid merchant invoice type: ', $filterType);
        }

        return $query->first();
    }
}
