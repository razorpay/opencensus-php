<?php

namespace RZP\Gateway\Mobikwik;

use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'mobikwik';

    public function findRefundByPaymentId($paymentId)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $paymentId)
                    ->where('action', '=', 'refund')
                    ->firstOrFail();
    }
}
