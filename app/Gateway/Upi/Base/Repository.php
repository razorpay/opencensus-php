<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Base\Entity as UPI;

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

    public function fetchAllForProviderUpdate($limit = 100)
    {
        return $this->newQuery()
                    ->select(UPI::ID, UPI::VPA)
                    ->whereNull(UPI::PROVIDER)
                    ->limit($limit)
                    ->get();
    }

}
