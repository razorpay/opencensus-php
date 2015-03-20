<?php

namespace Models\Transaction;

use Models\Base;
use Models\Transaction;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Transaction';

    public function fetchTxnsExpectedToSettle($timestamp)
    {
        $repo = $this->repo;

        return $repo::where(Transaction\Entity::SETTLED_AT, '=', $timestamp)
                    ->where(Transaction\Entity::SETTLED, '=', 0)
                    ->whereNotNull(Transaction\Entity::RECONCILED_AT)
                    ->orderBy(Transaction\Entity::MERCHANT_ID)
                    ->orderBy(Transaction\Entity::ID)
                    ->get();
    }

    public function fetchUnsettledTransactions($timestamp)
    {
        $repo = $this->repo;

        return $repo::where(Transaction\Entity::SETTLED_AT, '<', $timestamp)
                    ->where(Transaction\Entity::SETTLED, '=', 0)
                    ->whereNotNull(Transaction\Entity::RECONCILED_AT)
                    ->orderBy(Transaction\Entity::MERCHANT_ID)
                    ->orderBy(Transaction\Entity::ID)
                    ->get();
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
}