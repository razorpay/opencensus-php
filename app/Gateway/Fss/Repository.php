<?php

namespace RZP\Gateway\Fss;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'fss';

    /**
     * Find or fail by action and paymentId
     * @param $paymentId
     * @param $action
     *
     * @return mixed
     */
    public function findByPaymentIdAndActionOrFail($paymentId, $action)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', $action)
                    ->firstOrFail();
    }
}