<?php

namespace Models\Transaction;

use EE\Exception;
use Models\Base;
use Models\Transaction;

class Repository extends Base\Repository
{
    use Base\RepositoryFetchMultiple;

    protected $entity = 'Transaction';

    public function findByIdAndMerchantId($id, $merchantId, $failPublic = true)
    {
        $repo = $this->repo;

        $query = $repo::where(Transaction\Entity::MERCHANT_ID, $merchantId);

        if ($failPublic)
            return $query->findOrFailPublic($id);
        else
            return $query->findOrFail($id);
    }

    public function findByStatusBetweenTimestamps($status, $from, $to)
    {
        $repo = $this->repo;

        return $repo::where(Transaction\Entity::STATUS, '=', $status)
                    ->where(Common::CREATED_AT, '>=', $from)
                    ->where(Common::CREATED_AT, '<=', $to)
                    ->get();
    }

    public function reloadAndLockForUpdate($txn)
    {
        $repo = $this->repo;

        $reloadedEntity = $repo::lockForUpdate()->findOrFail($txn->getKey());

        $attributes = $reloadedEntity->getAttributes();

        $txn->setRawAttributes($attributes, true);
    }

    public function lockForUpdate($id)
    {
        $repo = $this->repo;

        $repo::lockForUpdate()->findOrFail($id);
    }
}