<?php

namespace RZP\Models\Payment\PaymentMeta;

use RZP\Base\ConnectionType;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'payment_meta';

    public function fetchByParams($params)
    {
        $query = $this->newQuery();

        foreach ($params as $key => $value)
        {
            $query->where($key, $value);
        }

        return $query->get();
    }

    public function findByPaymentIdAction($paymentId, $actionType)
    {
        return $this->newQuery()
            ->where(Entity::PAYMENT_ID, $paymentId)
            ->where(Entity::ACTION, $actionType)
            ->first();
    }

    public function findByPaymentId($paymentId)
    {
        return $this->newQuery()
            ->where(Entity::PAYMENT_ID, $paymentId)
            ->first();
    }

    public function findByReferenceId($transactionId)
    {
        return $this->newQuery()
            ->where(Entity::REFERENCE_ID,$transactionId)
            ->first();
    }
}
