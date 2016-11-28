<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Base\Entity;

class Repository extends Base\Repository
{
    protected $entity = 'upi';

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

    public function fetchAllForProviderUpdate($limit = 100, $lastId = 0)
    {
        return $this->newQuery()
                    ->select(Entity::ID, Entity::VPA)
                    ->where('id', '>', $lastId)
                    ->whereNull(Entity::PROVIDER)
                    ->limit($limit)
                    ->get();
    }

}
