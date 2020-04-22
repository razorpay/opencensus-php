<?php

namespace RZP\Models\Payment\UpiMetadata;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'upi_metadata';

    protected function addQueryOrder($query)
    {
        // Index only in payment_id
        $query->orderBy(Entity::PAYMENT_ID, 'desc');
    }

    public function fetchByPaymentId($paymentId)
    {
        return $this->newQuery()
            ->where(Entity::PAYMENT_ID , '=', $paymentId)
            ->first();
    }
}
