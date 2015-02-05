<?php

namespace Models\Payment;

use EE\Exception;
use Models\Base;
use Models\Payment;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Payment';

    public function findByStatusBetweenTimestamps($status, $from, $to)
    {
        $repo = $this->repo;

        return $repo::where(Payment\Entity::STATUS, '=', $status)
                    ->where(Common::CREATED_AT, '>=', $from)
                    ->where(Common::CREATED_AT, '<=', $to)
                    ->get();
    }

    public function fetchCapturedForGatewayBetweenTimestamp($from, $to, $gateway)
    {
        $repo = $this->repo;

        return $repo::whereBetween(Payment\Entity::CAPTURED_AT, array($from, $to))
                    ->where(Payment\Entity::STATUS, '=', Payment\Status::CAPTURED)
                    ->where(Payment\Entity::GATEWAY, '=', $gateway)
                    ->get();
    }

    public function lockForUpdate($id)
    {
        $repo = $this->repo;

        $repo::lockForUpdate()->findOrFail($id);
    }

    public function expireAuthorizedPayments($timestmap)
    {
        $repo = $this->repo;

        return $repo::where(Payment\Entity::STATUS, '=', Payment\Status::AUTHORIZED)
                    ->where(Payment\Entity::CREATED_AT, '<', $timestamp)
                    ->update(array(Payment\Entity::STATUS => 'authorization_expired'));
    }
}