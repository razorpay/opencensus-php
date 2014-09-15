<?php

namespace Models\Ledger;

use Models\Base;
use Models\Ledger;

class Repository extends Base\Repository
{
    use Base\RepositoryFetchMultiple;

    protected $entity = 'Ledger';

    public function findByIdAndMerchantId($id, $merchantId, $failPublic = true)
    {
        $repo = $this->repo;

        $query = $repo::where(Transaction\Entity::MERCHANT_ID, $merchantId);

        if ($failPublic)
        {
            return $query->findOrFailPublic($id);
        }
        else
        {
            return $query->findOrFail($id);
        }
    }

    public function fetchTransactionsExpectedToSettle($timestamp)
    {
        $repo = $this->repo;

        return $repo::where(Ledger\Entity::SETTLED_AT, '=', $timestamp)
                    ->whereNull(Ledger\Entity::RECONCILED_AT)
                    ->orderBy(Ledger\Entity::MERCHANT_ID)
                    ->orderBy(Ledger\Entity::ID);
    }

    public function settled($lgrs, $settledAt)
    {
        $repo = $this->repo;

        $ids = array_slice($lgrs, 'id');

        $values = array(
            Ledger\Entity::SETTLED_AT => $settledAt,
            Ledger\Entity::SETTLED => true);

        $count = $repo::whereIn(Ledger\Entity::ID, $ids)
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
}