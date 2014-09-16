<?php

namespace Models\Transaction\Refund;

use EE\Exception;
use Models\Base;
use Models\Transaction\Refund;

class Repository extends Base\Repository
{
    use Base\RepositoryFetchMultiple;

    protected $entity = 'Refund';

    public function findOrFailPublicByParams($id, $merchantId, $txnId = null)
    {
        $repo = $this->repo;

        $query = $repo::where(Refund\Entity::MERCHANT_ID, '=', $merchantId);

        if ($txnId !== null)
        {
            $query->where(Refund\Entity::TRANSACTION_ID, '=', $txnId);
        }

        return $query->findOrFailPublic($id);
    }

    public function findForTransaction($txnId)
    {
        $repo = $this->repo;

        return $repo::where(Refund\Entity::TRANSACTION_ID, '=', $txnId)
                    ->get();
    }

    public function findBetweenTimestamps($from, $to)
    {
        $repo = $this->repo;

        return $repo::where(Common::CREATED_AT, '>=', $from)
                    ->where(Common::CREATED_AT, '<=', $to)
                    ->get();
    }

    public function fetchByIdTxnIdMerchantId($id, $txnId, $merchantId)
    {
        $repo = $this->repo;

        return $repo::where(Refund\Entity::TRANSACTION_ID, '=', $txnId)
                    ->where(Refund\Entity::MERCHANT_ID, '=', $merchantId)
                    ->find($id);
    }
}