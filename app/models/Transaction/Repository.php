<?php

namespace Models\Transaction;

use Carbon\Carbon;
use Models\Base;
use Models\Transaction;
use Models\Settlement;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Transaction';

    protected $appFetchParamRules = array(
        Entity::SETTLED         => 'sometimes|in:0,1',
        Entity::TYPE            => 'sometimes|in:payment,refund,settlement,adjustment',
        Entity::SETTLEMENT_ID   => 'sometimes|alpha_num',
        Entity::ENTITY_ID       => 'sometimes|string|min:14',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
    );

    public function fetchTxnsExpectedToSettle($timestamp)
    {
        $repo = $this->repo;

        return $repo::where(Transaction\Entity::SETTLED_AT, '=', $timestamp)
                    ->where(Transaction\Entity::SETTLED, '=', 0)
//                    ->whereNotNull(Transaction\Entity::RECONCILED_AT)
                    ->where(Transaction\Entity::TYPE, '!=', Type::SETTLEMENT)
                    ->orderBy(Transaction\Entity::MERCHANT_ID)
                    ->orderBy(Transaction\Entity::ID)
                    ->get();
    }

    public function fetchUnsettledTransactions($timestamp)
    {
        $repo = $this->repo;

        return $repo::where(Transaction\Entity::SETTLED_AT, '<', $timestamp)
                    ->where(Transaction\Entity::SETTLED, '=', 0)
//                    ->whereNotNull(Transaction\Entity::RECONCILED_AT)
                    ->where(Transaction\Entity::TYPE, '!=', Type::SETTLEMENT)
                    ->orderBy(Transaction\Entity::MERCHANT_ID)
                    ->orderBy(Transaction\Entity::ID)
                    ->get();
    }

    public function fetchEntitiesForReport($merchantId, $from, $to)
    {
        $setls = (new Settlement\Repository)->fetchBetweenTimestamp($merchantId, $from, $to);

        $setlIds = $setls->fetch(Settlement\Entity::ID)->all();

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
                      ->orderByCreatedAt()
                      ->get();

        $txns = $this->fetchAssociatedRelations($txns, 'source');

        return $txns;
    }

    public function fetchDataForInvoice($merchantId, $from, $to)
    {
        $fee = $this->newQuery()
                    ->merchantId($merchantId)
                    ->where('type', 'payment')
                    ->betweenTime($from, $to)
                    ->sum('fee');

        $serviceTax = $this->newQuery()
                           ->merchantId($merchantId)
                           ->where('type', 'payment')
                           ->betweenTime($from, $to)
                           ->sum('service_tax');

        // Total fee includes our cut + service tax
        return [
            'total_fee'         =>  $fee,
            // This is a combined tax column
            // and includes more than just service_tax (sb cess, kk cess)
            'tax'               =>  $serviceTax
        ];
    }

    public function fetchTransactionsForAuthorizedRefundedPayments()
    {
        $repo = $this->repo;

        $txns = $repo::where(Transaction\Entity::TYPE, '=', Type::REFUND)
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

    public function settled($txns, $settledAt)
    {
        $repo = $this->repo;

        if ($txns->count() === 0)
        {
            return;
        }

        $ids = $txns->getIds();

        $values = array(
            Transaction\Entity::SETTLED_AT  => $settledAt,
            Transaction\Entity::SETTLED     => true);

        $count = $repo::whereIn(Transaction\Entity::ID, $ids)
                      ->update($values);

        $expected = count($ids);

        if ($count !== $expected)
        {
            throw new Exception\LogicException(
                'Failed to update expected number of rows. \n' .
                'Expected: ' . $expected . ' Updated: ' . $count);
        }

        return $count;
    }

    public function updateSettlementId($txns, $settlementId)
    {
        $repo = $this->repo;

        if ($txns->count() === 0)
        {
            return;
        }

        $ids = $txns->getIds();

        $values = [Transaction\Entity::SETTLEMENT_ID  => $settlementId];

        $count = $repo::whereIn(Transaction\Entity::ID, $ids)
                      ->update($values);

        $expected = count($ids);

        if ($count !== $expected)
        {
            throw new Exception\LogicException(
                'Failed to update expected number of rows. \n' .
                'Expected: ' . $expected . ' Updated: ' . $count);
        }

        return $count;
    }

    public function findByEntityId($entityId, $fail = false)
    {
        $repo = $this->repo;

        $txn = $repo::where(Transaction\Entity::ENTITY_ID, '=', $entityId)
                    ->first();

        if (($txn === null) and
            ($fail))
        {
            throw new Exception\LogicException(
                'Failed to find transaction with entity_id: ' . $entityId);
        }

        return $txn;
    }

    public function fetchBySettlementId($setlId)
    {
        $repo = $this->repo;

        return $repo::where(Transaction\Entity::SETTLEMENT_ID, '=', $setlId)
                    ->get();
    }

    protected function addQueryParamEntityId($query, $params)
    {
        $entityId = $params[Entity::ENTITY_ID];

        Entity::stripSignWithoutValidation($entityId);

        $query->where(Entity::ENTITY_ID, '=', $entityId);
    }
}
