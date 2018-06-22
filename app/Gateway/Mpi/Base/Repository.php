<?php

namespace RZP\Gateway\Mpi\Base;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'mpi';

    public function findByPaymentIdAndActionOrFail($paymentId, $action)
    {
            return $this->newQuery()
                        ->where(Entity::PAYMENT_ID, '=', $paymentId)
                        ->where(Entity::ACTION, '=', $action)
                        ->firstOrFail();
    }
}
