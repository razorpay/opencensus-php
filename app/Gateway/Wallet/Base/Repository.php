<?php

namespace RZP\Gateway\Wallet\Base;

use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Wallet';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID    => 'sometimes|string|min:14|max:18',
        Entity::WALLET        => 'sometimes|in:payumoney,payzapp,olamoney,aritelmoney',
    );

    public function fetchGatewayPaymentId2ByPaymentId($paymentId)
    {
        return $this->newQuery()
                    ->where('payment_id' , '=', $paymentId)
                    ->lists('gateway_payment_id_2');
    }

    public function fetchWalletByPaymentId($paymentId)
    {
        return $this->newQuery()
                    ->where('payment_id' , '=', $paymentId)
                    ->first();
    }

    public function findByGatewayRefundId($gatewayRefundId)
    {
        return $this->newQuery()
                    ->where('gateway_refund_id', '=', $gatewayRefundId)
                    ->firstOrFail();
    }
}
