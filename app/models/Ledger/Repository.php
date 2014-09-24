<?php

namespace Models\Ledger;

use Models\Base;
use Models\Ledger;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Ledger';

    public function fetchPaymentsExpectedToSettle($timestamp)
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

    public function findByEntityId($entityId, $fail = false)
    {
        $repo = $this->repo;

        $lgr = $repo::where(Ledger\Entity::ENTITY_ID, '=', $entityId)
                    ->first();

        if (($lgr === null) and
            ($fail))
        {
            throw new Exception\LogicException(
                'Failed to find ledger with entity_id: ' . $entityId);
        }

        return $lgr;
    }
}