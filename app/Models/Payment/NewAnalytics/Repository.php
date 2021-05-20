<?php

namespace RZP\Models\Payment\NewAnalytics;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'new_payment_analytics';

    public function findByPaymentId($paymentId)
    {
        return $this->newQuery()
            ->where(Entity::PAYMENT_ID, $paymentId)
            ->first();
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::PAYMENT_ID, 'desc');
    }

}
