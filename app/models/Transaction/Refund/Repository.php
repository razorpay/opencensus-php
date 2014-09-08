<?php

namespace Models\Transaction\Refund;

use EE\Exception;
use Models\Base;
use Models\Transaction\Refund;

class Repository extends Base\Repository
{
    protected $entity = 'Refund';

    public function findOrFailPublicByParams($id, $merchantId, $txnId = null)
    {
        $repo = $this->repo;

        $query = $repo::where(Refund\Entity::MERCHANT_ID, '=', $merchantId);

        if ($txnId !== null)
        {
            $query->where(Refund\Entity::TRANSACTION_ID, '=', $txnId);
        }

        $query->findOrFailPublic($id);
    }

    public function findForTransaction($txnId)
    {
        $repo = $this->repo;

        return $repo::where(Refund\Entity::TRANSACTION_ID, '=', $txnId);
    }
}