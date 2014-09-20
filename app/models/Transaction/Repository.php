<?php

namespace Models\Transaction;

use EE\Exception;
use Models\Base;
use Models\Transaction;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Transaction';

    public function findByStatusBetweenTimestamps($status, $from, $to)
    {
        $repo = $this->repo;

        return $repo::where(Transaction\Entity::STATUS, '=', $status)
                    ->where(Common::CREATED_AT, '>=', $from)
                    ->where(Common::CREATED_AT, '<=', $to)
                    ->get();
    }

    public function fetchCapturedForGatewayBetweenTimestamp($from, $to, $gateway)
    {
        $repo = $this->repo;

        return $repo::whereBetween(Transaction\Entity::CAPTURED_AT, array($from, $to))
                    ->where(Transaction\Entity::STATUS, '=', Transaction\Status::CAPTURED)
                    ->where('gateway', '=', $gateway)
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