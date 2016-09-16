<?php

namespace RZP\Gateway\UPI\Base;

use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'UPI';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID    => 'sometimes|string|min:14|max:18',
        Entity::BANK          => 'sometimes|in:icici'
    );

    public function fetchGatewayPaymentIdByPaymentId($paymentId)
    {
        return $this->newQuery()
                    ->where('payment_id' , '=', $paymentId)
                    ->pluck('gateway_payment_id');
    }

    public function fetchByPaymentId($paymentId)
    {
        return $this->newQuery()
                    ->where('payment_id' , '=', $paymentId)
                    ->first();
    }
}
